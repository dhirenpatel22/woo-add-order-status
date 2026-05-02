<?php
/**
 * Plugin Name: Woo Add Order Status
 * plugin URI: 
 * Description: A lightweight plugin to easily add and manage custom order statuses in WooCommerce.
 * Version: 3.0.0
 * Author: Dhiren Patel
 * Author URI: https://dhirenpatel.com
 * Text Domain: woo-add-order-status
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPB_OSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPB_OSM_URL', plugin_dir_url( __FILE__ ) );

require_once WPB_OSM_PATH . 'includes/class-manager.php';
require_once WPB_OSM_PATH . 'includes/class-admin.php';
require_once WPB_OSM_PATH . 'includes/class-rest.php';
require_once WPB_OSM_PATH . 'includes/class-email.php';

add_action(
    'init',
    function () {
        load_plugin_textdomain( 'woo-add-order-status', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
);

add_action(
    'plugins_loaded',
    function () {
        \WPB_OSM\Manager::instance();
    }
);
