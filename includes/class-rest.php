<?php
namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

class REST {

    private static $manager;

    public static function init( $manager ) {
        self::$manager = $manager;
        add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
    }

    public static function routes() {
        register_rest_route(
            'wpb-osm/v1',
            '/statuses',
            [
                'methods'             => 'GET',
                'callback'            => function () {
                    return self::$manager->get_statuses();
                },
                'permission_callback' => '__return_true',
            ]
        );
    }
}
