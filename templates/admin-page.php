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
	'paid'          => array( __( 'Counts as paid', 'woo-add-order-status' ), __( 'WooCommerce treats orders in this status as paid (e.g. a status after Processing).', 'woo-add-order-status' ) ),
	'downloads'     => array( __( 'Allow downloads', 'woo-add-order-status' ), __( 'Customers can download purchased files while the order is in this status.', 'woo-add-order-status' ) ),
	'reduce_stock'  => array( __( 'Reduce stock', 'woo-add-order-status' ), __( 'Reduce product stock when an order enters this status (only once per order).', 'woo-add-order-status' ) ),
	'editable'      => array( __( 'Order editable', 'woo-add-order-status' ), __( 'Items and totals can still be edited while the order is in this status.', 'woo-add-order-status' ) ),
	'action_button' => array( __( 'Action button', 'woo-add-order-status' ), __( 'Show a one-click "Change to this status" button in the orders list.', 'woo-add-order-status' ) ),
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Order Status Manager', 'woo-add-order-status' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( 'error' === $notice[0] ? 'error' : 'success' ); ?> is-dismissible"><p><?php echo esc_html( $notice[1] ); ?></p></div>
	<?php endif; ?>

	<table class="widefat striped" style="max-width:1100px;margin-top:1em">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Key', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'After', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Options', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'woo-add-order-status' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $statuses ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No custom statuses yet. Add one below.', 'woo-add-order-status' ); ?></td></tr>
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
				$wpb_osm_options[] = __( 'Email', 'woo-add-order-status' );
			}
			if ( $wpb_osm_status['auto_after'] > 0 && isset( $wpb_osm_all[ $wpb_osm_status['auto_to'] ] ) ) {
				/* translators: 1: number, 2: hours/days, 3: status label. */
				$wpb_osm_options[] = sprintf( __( 'Auto → %3$s after %1$d %2$s', 'woo-add-order-status' ), $wpb_osm_status['auto_after'], 'hours' === $wpb_osm_status['auto_unit'] ? __( 'hours', 'woo-add-order-status' ) : __( 'days', 'woo-add-order-status' ), $wpb_osm_all[ $wpb_osm_status['auto_to'] ] );
			}
			?>
			<tr>
				<td><mark class="order-status" style="background:<?php echo esc_attr( $wpb_osm_status['color'] ); ?>;color:<?php echo esc_attr( \WPB_OSM\Admin::text_color( $wpb_osm_status['color'] ) ); ?>"><span><?php echo esc_html( $wpb_osm_status['label'] ); ?></span></mark></td>
				<td><code><?php echo esc_html( $wpb_osm_status['key'] ); ?></code></td>
				<td><?php echo esc_html( isset( $wpb_osm_all[ $wpb_osm_status['after'] ] ) ? $wpb_osm_all[ $wpb_osm_status['after'] ] : $wpb_osm_status['after'] ); ?></td>
				<td><?php echo $wpb_osm_options ? esc_html( implode( ' · ', $wpb_osm_options ) ) : '&mdash;'; ?></td>
				<td><?php echo esc_html( number_format_i18n( $wpb_osm_count ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( add_query_arg( 'edit', $wpb_osm_status['key'], $wpb_osm_page ) ); ?>"><?php esc_html_e( 'Edit', 'woo-add-order-status' ); ?></a>
					<?php if ( 0 === $wpb_osm_count ) : ?>
						| <a class="submitdelete" href="<?php echo esc_url( $wpb_osm_delete ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this status?', 'woo-add-order-status' ) ); ?>');"><?php esc_html_e( 'Delete', 'woo-add-order-status' ); ?></a>
					<?php else : ?>
						| <span title="<?php esc_attr_e( 'Move these orders to another status before deleting.', 'woo-add-order-status' ); ?>"><?php esc_html_e( 'In use', 'woo-add-order-status' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<h2><?php echo $editing ? esc_html__( 'Edit status', 'woo-add-order-status' ) : esc_html__( 'Add status', 'woo-add-order-status' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpb_osm_save' ); ?>
		<input type="hidden" name="action" value="wpb_osm_save" />
		<input type="hidden" name="original_key" value="<?php echo esc_attr( $editing ? $editing['key'] : '' ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="wpb-osm-key"><?php esc_html_e( 'Key', 'woo-add-order-status' ); ?></label></th>
				<td>
					<input id="wpb-osm-key" name="status[key]" value="<?php echo esc_attr( $wpb_osm_form['key'] ); ?>" maxlength="<?php echo esc_attr( \WPB_OSM\Manager::MAX_KEY_LENGTH ); ?>" pattern="[a-z0-9_\-]+" required <?php disabled( (bool) $editing ); ?> />
					<p class="description">
						<?php
						/* translators: %d: maximum key length. */
						echo esc_html( sprintf( __( 'Lowercase letters, numbers, hyphens. Up to %d characters. Can\'t be changed later.', 'woo-add-order-status' ), \WPB_OSM\Manager::MAX_KEY_LENGTH ) );
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-label"><?php esc_html_e( 'Label', 'woo-add-order-status' ); ?></label></th>
				<td><input id="wpb-osm-label" class="regular-text" name="status[label]" value="<?php echo esc_attr( $wpb_osm_form['label'] ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="wpb-osm-after"><?php esc_html_e( 'Show after', 'woo-add-order-status' ); ?></label></th>
				<td>
					<select id="wpb-osm-after" name="status[after]">
						<?php foreach ( $wpb_osm_rest as $wpb_osm_key => $wpb_osm_label ) : ?>
							<option value="<?php echo esc_attr( $wpb_osm_key ); ?>" <?php selected( $wpb_osm_form['after'], $wpb_osm_key ); ?>><?php echo esc_html( $wpb_osm_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-color"><?php esc_html_e( 'Colour', 'woo-add-order-status' ); ?></label></th>
				<td><input id="wpb-osm-color" type="color" name="status[color]" value="<?php echo esc_attr( $wpb_osm_form['color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="wpb-osm-description"><?php esc_html_e( 'Customer description', 'woo-add-order-status' ); ?></label></th>
				<td>
					<textarea id="wpb-osm-description" class="large-text" rows="2" name="status[description]"><?php echo esc_textarea( $wpb_osm_form['description'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Optional. Shown to the customer on their order page while the order is in this status.', 'woo-add-order-status' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Behaviour', 'woo-add-order-status' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $wpb_osm_bool as $wpb_osm_field => $wpb_osm_text ) : ?>
							<label style="display:block;margin-bottom:6px"><input type="checkbox" name="status[<?php echo esc_attr( $wpb_osm_field ); ?>]" value="1" <?php checked( $wpb_osm_form[ $wpb_osm_field ] ); ?> /> <strong><?php echo esc_html( $wpb_osm_text[0] ); ?></strong> — <?php echo esc_html( $wpb_osm_text[1] ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-auto-after"><?php esc_html_e( 'Automatic move', 'woo-add-order-status' ); ?></label></th>
				<td>
					<?php esc_html_e( 'After', 'woo-add-order-status' ); ?>
					<input id="wpb-osm-auto-after" type="number" min="0" step="1" style="width:5em" name="status[auto_after]" value="<?php echo esc_attr( $wpb_osm_form['auto_after'] ); ?>" />
					<select name="status[auto_unit]" aria-label="<?php esc_attr_e( 'Unit', 'woo-add-order-status' ); ?>">
						<option value="days" <?php selected( $wpb_osm_form['auto_unit'], 'days' ); ?>><?php esc_html_e( 'days', 'woo-add-order-status' ); ?></option>
						<option value="hours" <?php selected( $wpb_osm_form['auto_unit'], 'hours' ); ?>><?php esc_html_e( 'hours', 'woo-add-order-status' ); ?></option>
					</select>
					<?php esc_html_e( 'move the order to', 'woo-add-order-status' ); ?>
					<select name="status[auto_to]" aria-label="<?php esc_attr_e( 'Target status', 'woo-add-order-status' ); ?>">
						<option value=""><?php esc_html_e( '— choose —', 'woo-add-order-status' ); ?></option>
						<?php foreach ( $wpb_osm_rest as $wpb_osm_key => $wpb_osm_label ) : ?>
							<option value="<?php echo esc_attr( $wpb_osm_key ); ?>" <?php selected( $wpb_osm_form['auto_to'], $wpb_osm_key ); ?>><?php echo esc_html( $wpb_osm_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Set 0 to turn off. Runs only if the order is still in this status when the time comes; adds an order note.', 'woo-add-order-status' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpb-osm-email"><?php esc_html_e( 'Email', 'woo-add-order-status' ); ?></label></th>
				<td>
					<textarea id="wpb-osm-email" class="large-text" rows="5" name="status[email]"><?php echo esc_textarea( $wpb_osm_form['email'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave empty for no email. Placeholders: {customer_name}, {order_number}, {order_date}, {site_title}. Subject, heading and on/off are under WooCommerce → Settings → Emails.', 'woo-add-order-status' ); ?></p>
					<p>
						<label for="wpb-osm-recipient"><?php esc_html_e( 'Send to', 'woo-add-order-status' ); ?></label>
						<select id="wpb-osm-recipient" name="status[recipient]">
							<option value="customer" <?php selected( $wpb_osm_form['recipient'], 'customer' ); ?>><?php esc_html_e( 'Customer', 'woo-add-order-status' ); ?></option>
							<option value="admin" <?php selected( $wpb_osm_form['recipient'], 'admin' ); ?>><?php esc_html_e( 'Store admin', 'woo-add-order-status' ); ?></option>
							<option value="both" <?php selected( $wpb_osm_form['recipient'], 'both' ); ?>><?php esc_html_e( 'Customer and store admin', 'woo-add-order-status' ); ?></option>
						</select>
						<label for="wpb-osm-cc" style="margin-left:1em"><?php esc_html_e( 'CC', 'woo-add-order-status' ); ?></label>
						<input id="wpb-osm-cc" class="regular-text" name="status[cc]" value="<?php echo esc_attr( $wpb_osm_form['cc'] ); ?>" placeholder="warehouse@example.com, …" />
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( $editing ? __( 'Update status', 'woo-add-order-status' ) : __( 'Add status', 'woo-add-order-status' ) ); ?>
		<?php if ( $editing ) : ?>
			<a href="<?php echo esc_url( $wpb_osm_page ); ?>"><?php esc_html_e( 'Cancel', 'woo-add-order-status' ); ?></a>
		<?php endif; ?>
	</form>

	<?php if ( ! $editing && $gateways ) : ?>
		<hr />
		<h2><?php esc_html_e( 'Status after checkout, by payment method', 'woo-add-order-status' ); ?></h2>
		<p><?php esc_html_e( 'Choose the status new orders get for each payment method. Works for bank transfer, cheque, cash on delivery, and gateways that complete payment through WooCommerce (most card gateways).', 'woo-add-order-status' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'wpb_osm_gateways' ); ?>
			<input type="hidden" name="action" value="wpb_osm_gateways" />
			<table class="form-table" role="presentation">
				<?php foreach ( $gateways as $wpb_osm_gateway_id => $wpb_osm_gateway ) : ?>
					<tr>
						<th><label for="wpb-osm-gw-<?php echo esc_attr( $wpb_osm_gateway_id ); ?>"><?php echo esc_html( $wpb_osm_gateway->get_method_title() ); ?></label></th>
						<td>
							<select id="wpb-osm-gw-<?php echo esc_attr( $wpb_osm_gateway_id ); ?>" name="gateway[<?php echo esc_attr( $wpb_osm_gateway_id ); ?>]">
								<option value=""><?php esc_html_e( 'WooCommerce default', 'woo-add-order-status' ); ?></option>
								<?php foreach ( $wpb_osm_all as $wpb_osm_key => $wpb_osm_label ) : ?>
									<option value="<?php echo esc_attr( substr( $wpb_osm_key, 3 ) ); ?>" <?php selected( isset( $gateway_map[ $wpb_osm_gateway_id ] ) ? $gateway_map[ $wpb_osm_gateway_id ] : '', substr( $wpb_osm_key, 3 ) ); ?>><?php echo esc_html( $wpb_osm_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( 'yes' !== $wpb_osm_gateway->enabled ) : ?>
								<span class="description"><?php esc_html_e( '(not enabled)', 'woo-add-order-status' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( __( 'Save payment method statuses', 'woo-add-order-status' ), 'secondary' ); ?>
		</form>
	<?php endif; ?>
</div>
