<?php
/**
 * One WooCommerce email shared by all custom statuses.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * Shows under WooCommerce → Settings → Emails as "Custom order status".
 *
 * Subject, heading, on/off and email type are shared; the body and recipients
 * are set per status in WooCommerce → Order Status Manager. A single class is
 * used because WooCommerce's email preview looks emails up by class name.
 */
class Status_Email extends \WC_Email {

	/**
	 * Manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Status being sent (or previewed).
	 *
	 * @var array
	 */
	private $status;

	/**
	 * Set up.
	 *
	 * @param Manager $manager Manager.
	 */
	public function __construct( $manager ) {
		$this->manager        = $manager;
		$this->id             = 'wpb_osm_custom_status';
		$this->customer_email = true;
		$this->title          = __( 'Custom order status', 'exivon-custom-order-statuses-for-woocommerce' );
		$this->description    = __( 'Sent when an order moves to a custom status. Subject and heading apply to every custom status; use {status} for the status name. The message and recipients are set per status in WooCommerce → Order Status Manager; statuses with no message send nothing.', 'exivon-custom-order-statuses-for-woocommerce' );
		$this->template_html  = 'emails/custom-status.php';
		$this->template_plain = 'emails/plain/custom-status.php';
		$this->template_base  = WPB_OSM_PATH . 'templates/';

		$this->use_status( $this->preview_status() );
		add_filter( 'woocommerce_email_preview_placeholders', array( $this, 'preview_placeholders' ), 10, 3 );

		foreach ( $manager->get_statuses() as $status ) {
			$key = $status['key'];
			add_action(
				'woocommerce_order_status_' . $key . '_notification',
				function ( $order_id, $order = false ) use ( $key ) {
					$this->trigger( $key, $order_id, $order );
				},
				10,
				2
			);
		}

		parent::__construct();
	}

	/**
	 * Status shown in WooCommerce's email preview: the first one with a message.
	 *
	 * @return array
	 */
	private function preview_status() {
		foreach ( $this->manager->get_statuses() as $status ) {
			if ( '' !== trim( $status['email'] ) ) {
				return $status;
			}
		}
		return array_merge(
			Manager::defaults(),
			array(
				'label' => __( 'Awaiting Shipment', 'exivon-custom-order-statuses-for-woocommerce' ),
				'email' => __( 'Hi {customer_name}, your order #{order_number} has moved to a new status.', 'exivon-custom-order-statuses-for-woocommerce' ),
			)
		);
	}

	/**
	 * Fill our own placeholders in WooCommerce's email preview.
	 *
	 * @param array  $placeholders Placeholders.
	 * @param string $email_type   Email class being previewed.
	 * @param mixed  $email_object Dummy order (WooCommerce 9.9+).
	 * @return array
	 */
	public function preview_placeholders( $placeholders, $email_type, $email_object = null ) {
		if ( self::class === $email_type && $email_object instanceof \WC_Order ) {
			$placeholders['{customer_name}'] = $email_object->get_billing_first_name();
		}
		return $placeholders;
	}

	/**
	 * Point the email at one status.
	 *
	 * @param array $status Status.
	 */
	private function use_status( $status ) {
		$this->status       = $status;
		$this->placeholders = array(
			'{order_date}'    => '',
			'{order_number}'  => '',
			'{customer_name}' => '',
			'{status}'        => $status['label'],
		);
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: Your order #{order_number} is now {status}', 'exivon-custom-order-statuses-for-woocommerce' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return '{status}';
	}

	/**
	 * Send for one status.
	 *
	 * @param string         $key      Status key.
	 * @param int            $order_id Order ID.
	 * @param \WC_Order|bool $order    Order.
	 */
	public function trigger( $key, $order_id, $order = false ) {
		$status = $this->manager->get_status( $key );
		if ( ! $status || '' === trim( $status['email'] ) || ! $this->is_enabled() ) {
			return;
		}

		$this->setup_locale();
		$this->use_status( $status );

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                          = $order;
			$this->recipient                       = $this->build_recipients( $order );
			$this->placeholders['{order_date}']    = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}']  = $order->get_order_number();
			$this->placeholders['{customer_name}'] = $order->get_billing_first_name();

			if ( $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
		}

		$this->restore_locale();
	}

	/**
	 * Customer and/or store admin, plus CC addresses. Empty when nobody applies.
	 *
	 * @param \WC_Order $order Order.
	 * @return string Comma-separated addresses.
	 */
	private function build_recipients( $order ) {
		$to = array();
		if ( 'admin' !== $this->status['recipient'] && $order->get_billing_email() ) {
			$to[] = $order->get_billing_email();
		}
		if ( 'customer' !== $this->status['recipient'] ) {
			$to[] = get_option( 'admin_email' );
		}
		if ( $to && '' !== $this->status['cc'] ) {
			$to = array_merge( $to, array_map( 'trim', explode( ',', $this->status['cc'] ) ) );
		}
		return implode( ',', array_unique( array_filter( $to ) ) );
	}

	/**
	 * Template variables.
	 *
	 * @param bool $plain_text True for the plain-text template.
	 * @return array
	 */
	private function template_args( $plain_text ) {
		return array(
			'order'              => $this->object,
			'email_heading'      => $this->get_heading(),
			'body'               => $this->format_string( $this->status['email'] ),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => $plain_text,
			'email'              => $this,
		);
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	/**
	 * Plain content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}
}
