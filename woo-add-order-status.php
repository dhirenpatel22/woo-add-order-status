<?php
/**
 * Plugin Name:          Woo Add Order Status
 * Plugin URI:           https://github.com/dhirenpatel22/woo-add-order-status
 * Description:          A lightweight plugin to easily add and manage custom order statuses in WooCommerce.
 * Version:              3.1.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Dhiren Patel
 * Author URI:           https://dhirenpatel.me
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          woo-add-order-status
 * WC requires at least: 8.2
 * WC tested up to:      11.3
 *
 * @package WPB_OSM
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
require_once WPB_OSM_PATH . 'includes/class-features.php';

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		\WPB_OSM\Manager::instance();
	}
);
