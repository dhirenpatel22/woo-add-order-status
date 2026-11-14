<?php
/**
 * Admin screen: list, add, edit and delete statuses.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce → Order Status Manager.
 */
class Admin {

	const PAGE = 'wpb-osm';

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

		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_wpb_osm_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_wpb_osm_delete', array( __CLASS__, 'delete' ) );
		add_action( 'admin_post_wpb_osm_gateways', array( __CLASS__, 'save_gateways' ) );
		add_action( 'admin_head', array( __CLASS__, 'colors' ) );
	}

	/**
	 * Register the submenu page.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Order Status Manager', 'woo-add-order-status' ),
			__( 'Order Status Manager', 'woo-add-order-status' ),
			'manage_woocommerce', // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Page URL.
	 *
	 * @param array $args Extra query args.
	 * @return string
	 */
	private static function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Show the page.
	 */
	public static function render() {
		$manager  = self::$manager;
		$statuses = $manager->get_statuses();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen selection.
		$edit_key = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';
		$editing  = $edit_key ? $manager->get_status( $edit_key ) : null;
		$notice   = get_transient( self::notice_key() );
		delete_transient( self::notice_key() );
		$gateways    = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		$gateway_map = (array) get_option( Features::GATEWAY_OPTION, array() );

		include WPB_OSM_PATH . 'templates/admin-page.php';
	}

	/**
	 * Handle the "status after checkout" form.
	 */
	public static function save_gateways() {
		self::authorize();
		check_admin_referer( 'wpb_osm_gateways' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in Features::save_gateway_statuses().
		$input = isset( $_POST['gateway'] ) && is_array( $_POST['gateway'] ) ? wp_unslash( $_POST['gateway'] ) : array();
		Features::save_gateway_statuses( $input );
		self::notice( 'success', __( 'Payment method statuses saved.', 'woo-add-order-status' ) );
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Handle add/edit.
	 */
	public static function save() {
		self::authorize();
		check_admin_referer( 'wpb_osm_save' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized field by field in Manager::validate().
		$input        = isset( $_POST['status'] ) && is_array( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : array();
		$original_key = isset( $_POST['original_key'] ) ? sanitize_key( wp_unslash( $_POST['original_key'] ) ) : '';

		if ( '' !== $original_key && ! self::$manager->get_status( $original_key ) ) {
			$original_key = '';
		}

		$status = self::$manager->validate( $input, $original_key );

		if ( is_wp_error( $status ) ) {
			self::notice( 'error', implode( ' ', $status->get_error_messages() ) );
			wp_safe_redirect( self::url( $original_key ? array( 'edit' => $original_key ) : array() ) );
			exit;
		}

		self::$manager->save_status( $status );
		/* translators: %s: status label. */
		self::notice( 'success', sprintf( __( 'Status "%s" saved.', 'woo-add-order-status' ), $status['label'] ) );
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Handle delete.
	 */
	public static function delete() {
		self::authorize();
		check_admin_referer( 'wpb_osm_delete' );
		$key = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( $_GET['key'] ) ) : '';

		$result = self::$manager->delete_status( $key );
		if ( is_wp_error( $result ) ) {
			self::notice( 'error', $result->get_error_message() );
		} else {
			self::notice( 'success', __( 'Status deleted.', 'woo-add-order-status' ) );
		}
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Capability check.
	 */
	private static function authorize() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
			wp_die( esc_html__( 'You are not allowed to manage order statuses.', 'woo-add-order-status' ), 403 );
		}
	}

	/**
	 * Per-user notice storage key.
	 *
	 * @return string
	 */
	private static function notice_key() {
		return 'wpb_osm_notice_' . get_current_user_id();
	}

	/**
	 * Queue a notice for the next page load.
	 *
	 * @param string $type    success|error.
	 * @param string $message Message.
	 */
	private static function notice( $type, $message ) {
		set_transient( self::notice_key(), array( $type, $message ), MINUTE_IN_SECONDS );
	}

	/**
	 * Status colours on WooCommerce order screens only.
	 */
	public static function colors() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' ), true ) ) {
			return;
		}
		$statuses = self::$manager->get_statuses();
		if ( empty( $statuses ) ) {
			return;
		}
		echo '<style>';
		foreach ( $statuses as $status ) {
			printf(
				'.order-status.status-%1$s{background:%2$s;color:%3$s;}',
				esc_attr( $status['key'] ),
				esc_attr( $status['color'] ),
				esc_attr( self::text_color( $status['color'] ) )
			);
			if ( $status['action_button'] ) {
				printf(
					'.wc-action-button-wpb-osm-%1$s::after{font-family:Dashicons;content:"\f147";color:%2$s;}',
					esc_attr( $status['key'] ),
					esc_attr( $status['color'] )
				);
			}
		}
		echo '</style>';
	}

	/**
	 * Black or white text, whichever reads better on the background.
	 *
	 * @param string $hex Background colour, #rrggbb.
	 * @return string
	 */
	public static function text_color( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '#fff';
		}
		list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
		return ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) > 150 ? '#1e1e1e' : '#fff';
	}
}
