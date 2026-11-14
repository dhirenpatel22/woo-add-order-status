<?php
/**
 * Customer email for one custom status.
 *
 * @package WPB_OSM
 */

namespace WPB_OSM;

defined( 'ABSPATH' ) || exit;

/**
 * Shows under WooCommerce → Settings → Emails as "Order status: {label}".
 */
class Status_Email extends \WC_Email {

	/**
	 * The custom status.
	 *
	 * @var array
	 */
	private $status;

	/**
	 * Set up.
	 *
	 * @param array $status Custom status.
	 */
	public function __construct( $status ) {
		$this->status         = $status;
		$this->id             = 'wpb_osm_' . str_replace( '-', '_', $status['key'] );
		$this->customer_email = 'admin' !== $status['recipient'];
		/* translators: %s: status label. */
		$this->title          = sprintf( __( 'Order status: %s', 'exivon-custom-order-statuses-for-woocommerce' ), $status['label'] );
		$this->description    = __( 'Sent when an order moves to this custom status. Body and recipients are set in WooCommerce → Order Status Manager; leave the body empty to send nothing.', 'exivon-custom-order-statuses-for-woocommerce' );
		$this->template_html  = 'emails/custom-status.php';
		$this->template_plain = 'emails/plain/custom-status.php';
		$this->template_base  = WPB_OSM_PATH . 'templates/';
		$this->placeholders   = array(
			'{order_date}'    => '',
			'{order_number}'  => '',
			'{customer_name}' => '',
			'{status}'        => $status['label'],
		);

		add_action( 'woocommerce_order_status_' . $status['key'] . '_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
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
	 * Off when the status has no email body.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return parent::is_enabled() && '' !== trim( $this->status['email'] );
	}

	/**
	 * Send it.
	 *
	 * @param int            $order_id Order ID.
	 * @param \WC_Order|bool $order    Order.
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                          = $order;
			$this->recipient                       = $this->build_recipients( $order );
			$this->placeholders['{order_date}']    = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}']  = $order->get_order_number();
			$this->placeholders['{customer_name}'] = $order->get_billing_first_name();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
