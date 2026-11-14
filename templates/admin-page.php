<?php
/**
 * Admin page.
 *
 * @package WPB_OSM
 *
 * @var \WPB_OSM\Manager $manager
 * @var array            $statuses
 * @var array|null       $editing
 * @var array|false      $notice
 * @var array            $gateways    Payment gateway objects keyed by ID.
 * @var array            $gateway_map Gateway ID => status key.
 */

defined( 'ABSPATH' ) || exit;

$wpb_osm_form = $editing ? $editing : \WPB_OSM\Manager::defaults();
$wpb_osm_all  = wc_get_order_statuses();
$wpb_osm_rest = $wpb_osm_all;
unset( $wpb_osm_rest[ 'wc-' . $wpb_osm_form['key'] ] );
$wpb_osm_page = add_query_arg( 'page', \WPB_OSM\Admin::PAGE, admin_url( 'admin.php' ) );
$wpb_osm_bool = array(
	'paid'          => array( __( 'Counts as paid', 'exivon-custom-order-statuses-for-woocommerce' ), __( 'WooCommerce treats orders in this status as paid (e.g. a status after Processing).', 'exivon-custom-order-statuses-for-woocommerce' ) ),
	'downloads'     => array( __( 'Allow downloads', 'exivon-custom-order-statuses-for-woocommerce' ), __( 'Customers can download purchased files while the order is in this status.', 'exivon-custom-order-statuses-for-woocommerce' ) ),
	'reduce_stock'  => array( __( 'Reduce stock', 'exivon-custom-order-statuses-for-woocommerce' ), __( 'Reduce product stock when an order enters this status (only once per order).', 'exivon-custom-order-statuses-for-woocommerce' ) ),
	'editable'      => array( __( 'Order editable', 'exivon-custom-order-statuses-for-woocommerce' ), __( 'Items and totals can still be edited while the order is in this status.', 'exivon-custom-order-statuses-for-woocommerce' ) ),
	'action_button' => array( __( 'Action button', 'exivon-custom-order-statuses-for-woocommerce' ), __( 'Show a one-click "Change to this status" button in the orders list.', 'exivon-custom-order-statuses-for-woocommerce' ) ),
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Order Status Manager', 'exivon-custom-order-statuses-for-woocommerce' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( 'error' === $notice[0] ? 'error' : 'success' ); ?> is-dismissible"><p><?php echo esc_html( $notice[1] ); ?></p></div>
	<?php endif; ?>

	<table class="widefat striped" style="max-width:1100px;margin-top:1em">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Key', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'After', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Options', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $statuses ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No custom statuses yet. Add one below.', 'exivon-custom-order-statuses-for-woocommerce' ); ?></td></tr>
		<?php endif; ?>
		<?php foreach ( $statuses as $wpb_osm_status ) : ?>
			<?php
			$wpb_osm_count   = $manager->order_count( $wpb_osm_status['key'] );
			$wpb_osm_delete  = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'wpb_osm_delete',
						'key'    => $wpb_osm_status['key'],
					),
					admin_url( 'admin-post.php' )
				),
				'wpb_osm_delete'
			);
			$wpb_osm_options = array();
			foreach ( $wpb_osm_bool as $wpb_osm_field => $wpb_osm_text ) {
				if ( $wpb_osm_status[ $wpb_osm_field ] ) {
					$wpb_osm_options[] = $wpb_osm_text[0];
				}
			}
			if ( '' !== trim( $wpb_osm_status['email'] ) ) {
				$wpb_osm_options[] = __( 'Email', 'exivon-custom-order-statuses-for-woocommerce' );
			}
			if ( $wpb_osm_status['auto_after'] > 0 && isset( $wpb_osm_all[ $wpb_osm_status['auto_to'] ] ) ) {
				/* translators: 1: number, 2: hours/days, 3: status label. */
				$wpb_osm_options[] = sprintf( __( 'Auto → %3$s after %1$d %2$s', 'exivon-custom-order-statuses-for-woocommerce' ), $wpb_osm_status['auto_after'], 'hours' === $wpb_osm_status['auto_unit'] ? __( 'hours', 'exivon-custom-order-statuses-for-woocommerce' ) : __( 'days', 'exivon-custom-order-statuses-for-woocommerce' ), $wpb_osm_all[ $wpb_osm_status['auto_to'] ] );
			}
			?>
			<tr>
				<td><mark class="order-status" style="background:<?php echo esc_attr( $wpb_osm_status['color'] ); ?>;color:<?php echo esc_attr( \WPB_OSM\Admin::text_color( $wpb_osm_status['color'] ) ); ?>"><span><?php echo esc_html( $wpb_osm_status['label'] ); ?></span></mark></td>
				<td><code><?php echo esc_html( $wpb_osm_status['key'] ); ?></code></td>
				<td><?php echo esc_html( isset( $wpb_osm_all[ $wpb_osm_status['after'] ] ) ? $wpb_osm_all[ $wpb_osm_status['after'] ] : $wpb_osm_status['after'] ); ?></td>
				<td><?php echo $wpb_osm_options ? esc_html( implode( ' · ', $wpb_osm_options ) ) : '&mdash;'; ?></td>
				<td><?php echo esc_html( number_format_i18n( $wpb_osm_count ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( add_query_arg( 'edit', $wpb_osm_status['key'], $wpb_osm_page ) ); ?>"><?php esc_html_e( 'Edit', 'exivon-custom-order-statuses-for-woocommerce' ); ?></a>
					<?php if ( 0 === $wpb_osm_count ) : ?>
						| <a class="submitdelete" href="<?php echo esc_url( $wpb_osm_delete ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this status?', 'exivon-custom-order-statuses-for-woocommerce' ) ); ?>');"><?php esc_html_e( 'Delete', 'exivon-custom-order-statuses-for-woocommerce' ); ?></a>
					<?php else : ?>
						| <span title="<?php esc_attr_e( 'Move these orders to another status before deleting.', 'exivon-custom-order-statuses-for-woocommerce' ); ?>"><?php esc_html_e( 'In use', 'exivon-custom-order-statuses-for-woocommerce' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<h2><?php echo $editing ? esc_html__( 'Edit status', 'exivon-custom-order-statuses-for-woocommerce' ) : esc_html__( 'Add status', 'exivon-custom-order-statuses-for-woocommerce' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpb_osm_save' ); ?>
		<input type="hidden" name="action" value="wpb_osm_save" />
		<input type="hidden" name="original_key" value="<?php echo esc_attr( $editing ? $editing['key'] : '' ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="wpb-osm-key"><?php esc_html_e( 'Key', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td>
					<input id="wpb-osm-key" name="status[key]" value="<?php echo esc_attr( $wpb_osm_form['key'] ); ?>" maxlength="<?php echo esc_attr( \WPB_OSM\Manager::MAX_KEY_LENGTH ); ?>" pattern="[a-z0-9_\-]+" required <?php disabled( (bool) $editing ); ?> />
					<p class="description">
						<?php
						/* translators: %d: maximum key length. */
						echo esc_html( sprintf( __( 'Lowercase letters, numbers, hyphens. Up to %d characters. Can\'t be changed later.', 'exivon-custom-order-statuses-for-woocommerce' ), \WPB_OSM\Manager::MAX_KEY_LENGTH ) );
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-label"><?php esc_html_e( 'Label', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td><input id="wpb-osm-label" class="regular-text" name="status[label]" value="<?php echo esc_attr( $wpb_osm_form['label'] ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="wpb-osm-after"><?php esc_html_e( 'Show after', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td>
					<select id="wpb-osm-after" name="status[after]">
						<?php foreach ( $wpb_osm_rest as $wpb_osm_key => $wpb_osm_label ) : ?>
							<option value="<?php echo esc_attr( $wpb_osm_key ); ?>" <?php selected( $wpb_osm_form['after'], $wpb_osm_key ); ?>><?php echo esc_html( $wpb_osm_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-color"><?php esc_html_e( 'Colour', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td><input id="wpb-osm-color" type="color" name="status[color]" value="<?php echo esc_attr( $wpb_osm_form['color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="wpb-osm-description"><?php esc_html_e( 'Customer description', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td>
					<textarea id="wpb-osm-description" class="large-text" rows="2" name="status[description]"><?php echo esc_textarea( $wpb_osm_form['description'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Optional. Shown to the customer on their order page while the order is in this status.', 'exivon-custom-order-statuses-for-woocommerce' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Behaviour', 'exivon-custom-order-statuses-for-woocommerce' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $wpb_osm_bool as $wpb_osm_field => $wpb_osm_text ) : ?>
							<label style="display:block;margin-bottom:6px"><input type="checkbox" name="status[<?php echo esc_attr( $wpb_osm_field ); ?>]" value="1" <?php checked( $wpb_osm_form[ $wpb_osm_field ] ); ?> /> <strong><?php echo esc_html( $wpb_osm_text[0] ); ?></strong> — <?php echo esc_html( $wpb_osm_text[1] ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-auto-after"><?php esc_html_e( 'Automatic move', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td>
					<?php esc_html_e( 'After', 'exivon-custom-order-statuses-for-woocommerce' ); ?>
					<input id="wpb-osm-auto-after" type="number" min="0" step="1" style="width:5em" name="status[auto_after]" value="<?php echo esc_attr( $wpb_osm_form['auto_after'] ); ?>" />
					<select name="status[auto_unit]" aria-label="<?php esc_attr_e( 'Unit', 'exivon-custom-order-statuses-for-woocommerce' ); ?>">
						<option value="days" <?php selected( $wpb_osm_form['auto_unit'], 'days' ); ?>><?php esc_html_e( 'days', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
						<option value="hours" <?php selected( $wpb_osm_form['auto_unit'], 'hours' ); ?>><?php esc_html_e( 'hours', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
					</select>
					<?php esc_html_e( 'move the order to', 'exivon-custom-order-statuses-for-woocommerce' ); ?>
					<select name="status[auto_to]" aria-label="<?php esc_attr_e( 'Target status', 'exivon-custom-order-statuses-for-woocommerce' ); ?>">
						<option value=""><?php esc_html_e( '— choose —', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
						<?php foreach ( $wpb_osm_rest as $wpb_osm_key => $wpb_osm_label ) : ?>
							<option value="<?php echo esc_attr( $wpb_osm_key ); ?>" <?php selected( $wpb_osm_form['auto_to'], $wpb_osm_key ); ?>><?php echo esc_html( $wpb_osm_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Set 0 to turn off. Runs only if the order is still in this status when the time comes; adds an order note.', 'exivon-custom-order-statuses-for-woocommerce' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-email"><?php esc_html_e( 'Email', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label></th>
				<td>
					<textarea id="wpb-osm-email" class="large-text" rows="5" name="status[email]"><?php echo esc_textarea( $wpb_osm_form['email'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave empty for no email. Placeholders: {customer_name}, {order_number}, {order_date}, {site_title}. Subject, heading and on/off are under WooCommerce → Settings → Emails.', 'exivon-custom-order-statuses-for-woocommerce' ); ?></p>
					<p>
						<label for="wpb-osm-recipient"><?php esc_html_e( 'Send to', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label>
						<select id="wpb-osm-recipient" name="status[recipient]">
							<option value="customer" <?php selected( $wpb_osm_form['recipient'], 'customer' ); ?>><?php esc_html_e( 'Customer', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
							<option value="admin" <?php selected( $wpb_osm_form['recipient'], 'admin' ); ?>><?php esc_html_e( 'Store admin', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
							<option value="both" <?php selected( $wpb_osm_form['recipient'], 'both' ); ?>><?php esc_html_e( 'Customer and store admin', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
						</select>
						<label for="wpb-osm-cc" style="margin-left:1em"><?php esc_html_e( 'CC', 'exivon-custom-order-statuses-for-woocommerce' ); ?></label>
						<input id="wpb-osm-cc" class="regular-text" name="status[cc]" value="<?php echo esc_attr( $wpb_osm_form['cc'] ); ?>" placeholder="warehouse@example.com, …" />
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( $editing ? __( 'Update status', 'exivon-custom-order-statuses-for-woocommerce' ) : __( 'Add status', 'exivon-custom-order-statuses-for-woocommerce' ) ); ?>
		<?php if ( $editing ) : ?>
			<a href="<?php echo esc_url( $wpb_osm_page ); ?>"><?php esc_html_e( 'Cancel', 'exivon-custom-order-statuses-for-woocommerce' ); ?></a>
		<?php endif; ?>
	</form>

	<?php if ( ! $editing && $gateways ) : ?>
		<hr />
		<h2><?php esc_html_e( 'Status after checkout, by payment method', 'exivon-custom-order-statuses-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Choose the status new orders get for each payment method. Works for bank transfer, cheque, cash on delivery, and gateways that complete payment through WooCommerce (most card gateways).', 'exivon-custom-order-statuses-for-woocommerce' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'wpb_osm_gateways' ); ?>
			<input type="hidden" name="action" value="wpb_osm_gateways" />
			<table class="form-table" role="presentation">
				<?php foreach ( $gateways as $wpb_osm_gateway_id => $wpb_osm_gateway ) : ?>
					<tr>
						<th><label for="wpb-osm-gw-<?php echo esc_attr( $wpb_osm_gateway_id ); ?>"><?php echo esc_html( $wpb_osm_gateway->get_method_title() ); ?></label></th>
						<td>
							<select id="wpb-osm-gw-<?php echo esc_attr( $wpb_osm_gateway_id ); ?>" name="gateway[<?php echo esc_attr( $wpb_osm_gateway_id ); ?>]">
								<option value=""><?php esc_html_e( 'WooCommerce default', 'exivon-custom-order-statuses-for-woocommerce' ); ?></option>
								<?php foreach ( $wpb_osm_all as $wpb_osm_key => $wpb_osm_label ) : ?>
									<option value="<?php echo esc_attr( substr( $wpb_osm_key, 3 ) ); ?>" <?php selected( isset( $gateway_map[ $wpb_osm_gateway_id ] ) ? $gateway_map[ $wpb_osm_gateway_id ] : '', substr( $wpb_osm_key, 3 ) ); ?>><?php echo esc_html( $wpb_osm_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( 'yes' !== $wpb_osm_gateway->enabled ) : ?>
								<span class="description"><?php esc_html_e( '(not enabled)', 'exivon-custom-order-statuses-for-woocommerce' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( __( 'Save payment method statuses', 'exivon-custom-order-statuses-for-woocommerce' ), 'secondary' ); ?>
		</form>
	<?php endif; ?>
</div>
