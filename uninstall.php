<?php
/**
 * Remove plugin settings. Orders are left untouched: any order still in a custom
 * status keeps it in the database but won't show in WooCommerce's lists.
 *
 * @package WPB_OSM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( (array) get_option( 'wpb_osm_statuses', array() ) as $wpb_osm_status ) {
	if ( ! empty( $wpb_osm_status['key'] ) ) {
		delete_option( 'woocommerce_wpb_osm_' . str_replace( '-', '_', sanitize_key( $wpb_osm_status['key'] ) ) . '_settings' );
	}
}
delete_option( 'wpb_osm_statuses' );
