<?php
/**
 * Status storage, validation and WooCommerce registration.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the list of custom statuses.
 */
class Manager {

	const OPTION = 'wpb_osm_statuses';

	/**
	 * Order status columns are varchar(20) and WooCommerce adds "wc-".
	 */
	const MAX_KEY_LENGTH = 17;

	/**
	 * Singleton instance.
	 *
	 * @var Manager|null
	 */
	private static $instance = null;

	/**
	 * Saved statuses (list of arrays).
	 *
	 * @var array
	 */
	private $statuses = array();

	/**
	 * Get the instance.
	 *
	 * @return Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load statuses and hook into WooCommerce.
	 */
	private function __construct() {
		$this->statuses = array_map( array( $this, 'normalize' ), (array) get_option( self::OPTION, array() ) );

		add_action( 'init', array( $this, 'register_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'inject_statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $this, 'paid_statuses' ) );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'bulk_actions' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions' ) );

		Admin::init( $this );
		REST::init( $this );
		Email::init( $this );
	}

	/**
	 * Fill missing fields (statuses saved by v3.0.0 have no "paid").
	 *
	 * @param array $status Raw status.
	 * @return array
	 */
	private function normalize( $status ) {
		return wp_parse_args(
			(array) $status,
			array(
				'key'   => '',
				'label' => '',
				'after' => 'wc-processing',
				'color' => '#2271b1',
				'email' => '',
				'paid'  => false,
			)
		);
	}

	/**
	 * All statuses.
	 *
	 * @return array
	 */
	public function get_statuses() {
		return $this->statuses;
	}

	/**
	 * One status by key.
	 *
	 * @param string $key Status key without "wc-".
	 * @return array|null
	 */
	public function get_status( $key ) {
		foreach ( $this->statuses as $status ) {
			if ( $status['key'] === $key ) {
				return $status;
			}
		}
		return null;
	}

	/**
	 * Validate and sanitize input for one status.
	 *
	 * @param array  $input        Unslashed input: key, label, after, color, email, paid.
	 * @param string $original_key Key being edited, or '' when adding. Keys can't be renamed.
	 * @return array|\WP_Error Clean status or error.
	 */
	public function validate( $input, $original_key = '' ) {
		$input = is_array( $input ) ? $input : array();
		$get   = function ( $field ) use ( $input ) {
			return isset( $input[ $field ] ) && is_scalar( $input[ $field ] ) ? (string) $input[ $field ] : '';
		};

		$key   = '' !== $original_key ? $original_key : sanitize_key( $get( 'key' ) );
		$label = sanitize_text_field( $get( 'label' ) );
		$after = sanitize_text_field( $get( 'after' ) );
		$error = new \WP_Error();

		if ( '' === $key || ( '' === $original_key && sanitize_key( $get( 'key' ) ) !== trim( $get( 'key' ) ) ) ) {
			$error->add( 'key_empty', __( 'Key is required and may only contain lowercase letters, numbers, hyphens and underscores.', 'woo-add-order-status' ) );
		} elseif ( strlen( $key ) > self::MAX_KEY_LENGTH ) {
			/* translators: %d: maximum key length. */
			$error->add( 'key_length', sprintf( __( 'Key must be %d characters or fewer.', 'woo-add-order-status' ), self::MAX_KEY_LENGTH ) );
		} elseif ( '' === $original_key && ( $this->get_status( $key ) || array_key_exists( 'wc-' . $key, wc_get_order_statuses() ) || get_post_status_object( 'wc-' . $key ) ) ) {
			$error->add( 'key_taken', __( 'That key is already used by another order status.', 'woo-add-order-status' ) );
		}

		if ( '' === $label ) {
			$error->add( 'label_empty', __( 'Label is required.', 'woo-add-order-status' ) );
		}

		if ( 'wc-' . $key === $after || ! array_key_exists( $after, wc_get_order_statuses() ) ) {
			$error->add( 'after_invalid', __( 'Choose an existing status to place this one after.', 'woo-add-order-status' ) );
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		$color = sanitize_hex_color( $get( 'color' ) );

		return array(
			'key'   => $key,
			'label' => $label,
			'after' => $after,
			'color' => $color ? $color : '#2271b1',
			'email' => wp_kses_post( $get( 'email' ) ),
			'paid'  => ! empty( $input['paid'] ),
		);
	}

	/**
	 * Add or update a validated status, keeping all others.
	 *
	 * @param array $status Output of validate().
	 */
	public function save_status( $status ) {
		$saved = false;
		foreach ( $this->statuses as $i => $existing ) {
			if ( $existing['key'] === $status['key'] ) {
				$this->statuses[ $i ] = $status;
				$saved                = true;
			}
		}
		if ( ! $saved ) {
			$this->statuses[] = $status;
		}
		update_option( self::OPTION, $this->statuses );
	}

	/**
	 * Delete a status. Refused while orders still use it, so they never disappear.
	 *
	 * @param string $key Status key.
	 * @return true|\WP_Error
	 */
	public function delete_status( $key ) {
		if ( ! $this->get_status( $key ) ) {
			return new \WP_Error( 'not_found', __( 'Status not found.', 'woo-add-order-status' ) );
		}
		$count = $this->order_count( $key );
		if ( $count > 0 ) {
			return new \WP_Error(
				'in_use',
				/* translators: %d: number of orders. */
				sprintf( _n( '%d order still has this status. Move it to another status before deleting.', '%d orders still have this status. Move them to another status before deleting.', $count, 'woo-add-order-status' ), $count )
			);
		}
		$this->statuses = array_values(
			array_filter(
				$this->statuses,
				function ( $status ) use ( $key ) {
					return $status['key'] !== $key;
				}
			)
		);
		update_option( self::OPTION, $this->statuses );
		return true;
	}

	/**
	 * Number of orders in a status (HPOS and legacy storage).
	 *
	 * @param string $key Status key.
	 * @return int
	 */
	public function order_count( $key ) {
		return (int) wc_orders_count( $key );
	}

	/**
	 * Register each status as a post status.
	 */
	public function register_statuses() {
		foreach ( $this->statuses as $status ) {
			$count = $status['label'] . ' <span class="count">(%s)</span>';
			register_post_status(
				'wc-' . $status['key'],
				array(
					'label'                     => $status['label'],
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => array(
						0          => $count,
						1          => $count,
						'singular' => $count,
						'plural'   => $count,
						'context'  => null,
						'domain'   => null,
					),
				)
			);
		}
	}

	/**
	 * Insert each status after its chosen status, or at the end if that one is gone.
	 *
	 * @param array $statuses WooCommerce statuses.
	 * @return array
	 */
	public function inject_statuses( $statuses ) {
		foreach ( $this->statuses as $status ) {
			$new_key = 'wc-' . $status['key'];
			if ( ! array_key_exists( $status['after'], $statuses ) ) {
				$statuses[ $new_key ] = $status['label'];
				continue;
			}
			$new_statuses = array();
			foreach ( $statuses as $key => $label ) {
				$new_statuses[ $key ] = $label;
				if ( $key === $status['after'] ) {
					$new_statuses[ $new_key ] = $status['label'];
				}
			}
			$statuses = $new_statuses;
		}
		return $statuses;
	}

	/**
	 * Treat statuses marked "Counts as paid" as paid.
	 *
	 * @param array $statuses Paid status keys without "wc-".
	 * @return array
	 */
	public function paid_statuses( $statuses ) {
		foreach ( $this->statuses as $status ) {
			if ( $status['paid'] ) {
				$statuses[] = $status['key'];
			}
		}
		return $statuses;
	}

	/**
	 * Add "Change status to …" bulk actions. WooCommerce handles mark_* itself.
	 *
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	public function bulk_actions( $actions ) {
		foreach ( $this->statuses as $status ) {
			/* translators: %s: status label. */
			$actions[ 'mark_' . $status['key'] ] = sprintf( __( 'Change status to %s', 'woo-add-order-status' ), $status['label'] );
		}
		return $actions;
	}
}
