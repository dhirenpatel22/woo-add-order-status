<?php
/**
 * REST API: wpb-osm/v1/statuses.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * List, create/update and delete statuses. Requires manage_woocommerce.
 */
class REST {

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
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Only store managers.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
	}

	/**
	 * Register routes.
	 */
	public static function routes() {
		register_rest_route(
			'wpb-osm/v1',
			'/statuses',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => function () {
						return rest_ensure_response( self::$manager->get_statuses() );
					},
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'save' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'wpb-osm/v1',
			'/statuses/(?P<key>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'delete' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			)
		);
	}

	/**
	 * Create, or update when the key exists.
	 *
	 * @param \WP_REST_Request $request Request with key, label, after, color, email, paid.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function save( $request ) {
		$params   = $request->get_params();
		$key      = isset( $params['key'] ) && is_scalar( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
		$existing = $key ? self::$manager->get_status( $key ) : null;
		$input    = $existing ? array_merge( $existing, $params ) : $params;
		$status   = self::$manager->validate( $input, $existing ? $key : '' );

		if ( is_wp_error( $status ) ) {
			$status->add_data( array( 'status' => 400 ) );
			return $status;
		}

		self::$manager->save_status( $status );
		return new \WP_REST_Response( $status, $existing ? 200 : 201 );
	}

	/**
	 * Delete (refused while orders use the status).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function delete( $request ) {
		$result = self::$manager->delete_status( $request['key'] );
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 'not_found' === $result->get_error_code() ? 404 : 409 ) );
			return $result;
		}
		return new \WP_REST_Response( array( 'deleted' => true ), 200 );
	}
}
