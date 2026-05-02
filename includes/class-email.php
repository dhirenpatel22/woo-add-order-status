<?php
namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

class Email {

    public static function init( $manager ) {
        add_action(
            'woocommerce_order_status_changed',
            function ( $order_id, $old_status, $new_status, $order ) use ( $manager ) {

                foreach ( $manager->get_statuses() as $status ) {
                    if ( $new_status === $status['key'] && ! empty( $status['email'] ) ) {

                        WC()->mailer()->send(
                            $order->get_billing_email(),
                            'Order Update: ' . $status['label'],
                            wpautop( $status['email'] )
                        );
                    }
                }
            },
            10,
            4
        );
    }
}
