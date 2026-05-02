<?php
namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

class Admin {

    private static $manager;

    public static function init( $manager ) {
        self::$manager = $manager;

        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_post_wpb_osm_save', [ __CLASS__, 'save' ] );
        add_action( 'admin_head', [ __CLASS__, 'colors' ] );
    }

    public static function menu() {
        add_submenu_page(
            'woocommerce',
            'Order Status Manager',
            'Order Status Manager',
            'manage_woocommerce',
            'wpb-osm',
            [ __CLASS__, 'render' ]
        );
    }

    public static function render() {
        $statuses = self::$manager->get_statuses();
        include WPB_OSM_PATH . 'templates/admin-page.php';
    }

    public static function save() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'woo-add-order-status' ) );
        }

        check_admin_referer( 'wpb_osm_nonce' );

        $statuses = [];

        if ( isset( $_POST['statuses'] ) ) {
            foreach ( $_POST['statuses'] as $status ) {
                $statuses[] = [
                    'key'   => sanitize_key( $status['key'] ),
                    'label' => sanitize_text_field( $status['label'] ),
                    'after' => sanitize_text_field( $status['after'] ),
                    'color' => sanitize_hex_color( $status['color'] ),
                    'email' => wp_kses_post( $status['email'] ),
                ];
            }
        }

        self::$manager->save_statuses( $statuses );

        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function colors() {
        $statuses = self::$manager->get_statuses();
        if ( empty( $statuses ) ) {
            return;
        }
        echo '<style>';
        foreach ( $statuses as $status ) {
            echo '.status-wc-' . esc_attr( $status['key'] ) . '{background:' . esc_attr( $status['color'] ) . ';color:#fff;}';
        }
        echo '</style>';
    }
}
