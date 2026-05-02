<?php
namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

class Manager {

    private static $instance = null;
    private $statuses = [];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->statuses = get_option( 'wpb_osm_statuses', [] );

        add_action( 'init', [ $this, 'register_statuses' ] );
        add_filter( 'wc_order_statuses', [ $this, 'inject_statuses' ] );

        Admin::init( $this );
        REST::init( $this );
        Email::init( $this );
    }

    public function get_statuses() {
        return $this->statuses;
    }

    public function save_statuses( $statuses ) {
        update_option( 'wpb_osm_statuses', $statuses );
        $this->statuses = $statuses;
    }

    public function register_statuses() {
        foreach ( $this->statuses as $status ) {
            register_post_status(
                'wc-' . $status['key'],
                [
                    'label'                     => $status['label'],
                    'public'                    => true,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop(
                        $status['label'] . ' (%s)',
                        $status['label'] . ' (%s)'
                    ),
                ]
            );
        }
    }

    public function inject_statuses( $statuses ) {
        foreach ( $this->statuses as $status ) {
            $new_statuses = [];
            foreach ( $statuses as $key => $label ) {
                $new_statuses[ $key ] = $label;

                if ( $key === $status['after'] ) {
                    $new_statuses[ 'wc-' . $status['key'] ] = $status['label'];
                }
            }
            $statuses = $new_statuses;
        }
        return $statuses;
    }
}
