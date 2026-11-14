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
	 * Whether this WooCommerce has its own Cc/Bcc settings on emails
	 * (the "email improvements" feature, WooCommerce 9.8+).
	 *
	 * @return bool
	 */
	public static function native_cc_available() {
		return class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class )
			&& \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'email_improvements' )
			&& property_exists( 'WC_Email', 'cc' );
	}

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
