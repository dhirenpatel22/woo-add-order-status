<?php
/**
 * Registers one WooCommerce email per custom status.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * Email wiring.
 */
class Email {

	/**
	 * Hook up.
	 *
	 * @param Manager $manager Manager.
	 */
	public static function init( $manager ) {
		// Let WooCommerce fire "{hook}_notification" for each status, like its own emails.
		add_filter(
			'woocommerce_email_actions',
			function ( $actions ) use ( $manager ) {
				foreach ( $manager->get_statuses() as $status ) {
					$actions[] = 'woocommerce_order_status_' . $status['key'];
				}
				return $actions;
			}
		);

		add_filter(
			'woocommerce_email_classes',
			function ( $emails ) use ( $manager ) {
				if ( $manager->get_statuses() ) {
					require_once WPB_OSM_PATH . 'includes/class-status-email.php';
					$emails['WPB_OSM_Status_Email'] = new Status_Email( $manager );
				}
				return $emails;
			}
		);
	}
}
