<?php
/**
 * Per-status behaviour: editing, downloads, stock, action buttons, automatic
 * transitions, customer description, and status by payment method.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * Order behaviour hooks.
 */
class Features {

	const GATEWAY_OPTION = 'wpb_osm_gateway_statuses';
	const AUTO_HOOK      = 'wpb_osm_auto_transition';
	const AUTO_GROUP     = 'wpb-osm';

	/**
	 * Manager.
	 *
	 * @var Manager
	 */
	private static $manager;

	/**
	 * Hook up.
	 *
	 * @param Manager $manager Manager.
	 */
	public static function init( $manager ) {
		self::$manager = $manager;

		add_filter( 'wc_order_is_editable', array( __CLASS__, 'editable' ), 10, 2 );
		add_filter( 'woocommerce_order_is_download_permitted', array( __CLASS__, 'downloads' ), 10, 2 );
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'action_buttons' ), 10, 2 );
		add_action( 'woocommerce_order_details_before_order_table', array( __CLASS__, 'customer_description' ) );
		add_action( self::AUTO_HOOK, array( __CLASS__, 'auto_transition' ), 10, 2 );

		foreach ( $manager->get_statuses() as $status ) {
			if ( $status['reduce_stock'] ) {
				add_action( 'woocommerce_order_status_' . $status['key'], 'wc_maybe_reduce_stock_levels' );
			}
			if ( $status['auto_after'] > 0 ) {
				add_action( 'woocommerce_order_status_' . $status['key'], array( __CLASS__, 'schedule_transition' ) );
			}
		}

		// Status after checkout, per payment method.
		foreach ( array( 'bacs', 'cheque', 'cod' ) as $gateway ) {
			add_filter(
				'woocommerce_' . $gateway . '_process_payment_order_status',
				function ( $status ) use ( $gateway ) {
					return self::gateway_status( $status, $gateway );
				}
			);
		}
		add_filter(
			'woocommerce_payment_complete_order_status',
			function ( $status, $order_id = 0, $order = null ) {
				$order = $order instanceof \WC_Order ? $order : wc_get_order( $order_id );
				return $order ? self::gateway_status( $status, $order->get_payment_method() ) : $status;
			},
			10,
			3
		);
	}

	/**
	 * Custom status for an order, if it has one.
	 *
	 * @param \WC_Order $order Order.
	 * @return array|null
	 */
	private static function order_status( $order ) {
		return $order instanceof \WC_Order ? self::$manager->get_status( $order->get_status() ) : null;
	}

	/**
	 * Allow editing orders in statuses marked editable.
	 *
	 * @param bool      $editable Editable.
	 * @param \WC_Order $order    Order.
	 * @return bool
	 */
	public static function editable( $editable, $order ) {
		$status = self::order_status( $order );
		return $status && $status['editable'] ? true : $editable;
	}

	/**
	 * Allow downloads in statuses marked so.
	 *
	 * @param bool      $permitted Permitted.
	 * @param \WC_Order $order     Order.
	 * @return bool
	 */
	public static function downloads( $permitted, $order ) {
		$status = self::order_status( $order );
		return $status && $status['downloads'] ? true : $permitted;
	}

	/**
	 * One-click buttons in the orders list "Actions" column (WooCommerce handles the click).
	 *
	 * @param array     $actions Actions.
	 * @param \WC_Order $order   Order.
	 * @return array
	 */
	public static function action_buttons( $actions, $order ) {
		foreach ( self::$manager->get_statuses() as $status ) {
			if ( ! $status['action_button'] || $order->has_status( $status['key'] ) ) {
				continue;
			}
			$actions[ 'wpb_osm_' . $status['key'] ] = array(
				'url'    => wp_nonce_url(
					add_query_arg(
						array(
							'action'   => 'woocommerce_mark_order_status',
							'status'   => $status['key'],
							'order_id' => $order->get_id(),
						),
						admin_url( 'admin-ajax.php' )
					),
					'woocommerce-mark-order-status'
				),
				/* translators: %s: status label. */
				'name'   => sprintf( __( 'Change to %s', 'woo-add-order-status' ), $status['label'] ),
				'action' => 'wpb-osm-' . $status['key'],
			);
		}
		return $actions;
	}

	/**
	 * Show the status description to the customer (My Account → order, thank-you page).
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function customer_description( $order ) {
		$status = self::order_status( $order );
		if ( $status && '' !== $status['description'] ) {
			echo '<p class="wpb-osm-status-description">' . nl2br( esc_html( $status['description'] ) ) . '</p>';
		}
	}

	/**
	 * Queue the automatic move when an order enters a status with a rule.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function schedule_transition( $order_id ) {
		$order  = wc_get_order( $order_id );
		$status = self::order_status( $order );
		if ( ! $status || $status['auto_after'] < 1 || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}
		$args = array( (int) $order_id, $status['key'] );
		as_unschedule_all_actions( self::AUTO_HOOK, $args, self::AUTO_GROUP );
		$delay = $status['auto_after'] * ( 'hours' === $status['auto_unit'] ? HOUR_IN_SECONDS : DAY_IN_SECONDS );
		as_schedule_single_action( time() + $delay, self::AUTO_HOOK, $args, self::AUTO_GROUP );
	}

	/**
	 * Run the automatic move, only if the order is still in the same status.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $from     Status key the rule belongs to.
	 */
	public static function auto_transition( $order_id, $from ) {
		$order  = wc_get_order( $order_id );
		$status = self::$manager->get_status( $from );
		if ( ! $order || ! $status || ! $order->has_status( $from ) || '' === $status['auto_to'] ) {
			return;
		}
		$to = substr( $status['auto_to'], 3 );
		if ( ! wc_is_order_status( $status['auto_to'] ) ) {
			return;
		}
		/* translators: %s: status label. */
		$order->update_status( $to, sprintf( __( 'Moved automatically by the "%s" status rule.', 'woo-add-order-status' ), $status['label'] ) );
	}

	/**
	 * Status chosen for a payment method, or WooCommerce's default.
	 *
	 * @param string $status  Default status (no "wc-").
	 * @param string $gateway Payment method ID.
	 * @return string
	 */
	public static function gateway_status( $status, $gateway ) {
		$map = (array) get_option( self::GATEWAY_OPTION, array() );
		$key = isset( $map[ $gateway ] ) ? $map[ $gateway ] : '';
		return '' !== $key && wc_is_order_status( 'wc-' . $key ) ? $key : $status;
	}

	/**
	 * Save the payment method map, keeping only real statuses.
	 *
	 * @param array $input Gateway ID => status key ('' = default).
	 */
	public static function save_gateway_statuses( $input ) {
		$map = array();
		foreach ( (array) $input as $gateway => $key ) {
			$gateway = sanitize_key( $gateway );
			$key     = sanitize_key( is_scalar( $key ) ? $key : '' );
			if ( '' !== $gateway && '' !== $key && wc_is_order_status( 'wc-' . $key ) ) {
				$map[ $gateway ] = $key;
			}
		}
		update_option( self::GATEWAY_OPTION, $map );
	}
}
