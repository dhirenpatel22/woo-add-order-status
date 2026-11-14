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
 */

defined( 'ABSPATH' ) || exit;

$wpb_osm_form  = $editing ? $editing : array(
	'key'   => '',
	'label' => '',
	'after' => 'wc-processing',
	'color' => '#2271b1',
	'email' => '',
	'paid'  => false,
);
$wpb_osm_after = wc_get_order_statuses();
unset( $wpb_osm_after[ 'wc-' . $wpb_osm_form['key'] ] );
$wpb_osm_page = add_query_arg( 'page', \WPB_OSM\Admin::PAGE, admin_url( 'admin.php' ) );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Order Status Manager', 'woo-add-order-status' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( 'error' === $notice[0] ? 'error' : 'success' ); ?> is-dismissible"><p><?php echo esc_html( $notice[1] ); ?></p></div>
	<?php endif; ?>

	<table class="widefat striped" style="max-width:960px;margin-top:1em">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Key', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'After', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Paid', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Email', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'woo-add-order-status' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'woo-add-order-status' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $statuses ) ) : ?>
			<tr><td colspan="7"><?php esc_html_e( 'No custom statuses yet. Add one below.', 'woo-add-order-status' ); ?></td></tr>
		<?php endif; ?>
		<?php foreach ( $statuses as $wpb_osm_status ) : ?>
			<?php
			$wpb_osm_count  = $manager->order_count( $wpb_osm_status['key'] );
			$wpb_osm_delete = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'wpb_osm_delete',
						'key'    => $wpb_osm_status['key'],
					),
					admin_url( 'admin-post.php' )
				),
				'wpb_osm_delete'
			);
			?>
			<tr>
				<td><mark class="order-status" style="background:<?php echo esc_attr( $wpb_osm_status['color'] ); ?>;color:<?php echo esc_attr( \WPB_OSM\Admin::text_color( $wpb_osm_status['color'] ) ); ?>"><span><?php echo esc_html( $wpb_osm_status['label'] ); ?></span></mark></td>
				<td><code><?php echo esc_html( $wpb_osm_status['key'] ); ?></code></td>
				<td><?php echo esc_html( isset( $wpb_osm_after[ $wpb_osm_status['after'] ] ) ? $wpb_osm_after[ $wpb_osm_status['after'] ] : $wpb_osm_status['after'] ); ?></td>
				<td><?php echo $wpb_osm_status['paid'] ? esc_html__( 'Yes', 'woo-add-order-status' ) : '&mdash;'; ?></td>
				<td><?php echo '' !== trim( $wpb_osm_status['email'] ) ? esc_html__( 'Yes', 'woo-add-order-status' ) : '&mdash;'; ?></td>
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
						<?php foreach ( $wpb_osm_after as $wpb_osm_key => $wpb_osm_label ) : ?>
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
				<th><?php esc_html_e( 'Paid', 'woo-add-order-status' ); ?></th>
				<td><label><input type="checkbox" name="status[paid]" value="1" <?php checked( $wpb_osm_form['paid'] ); ?> /> <?php esc_html_e( 'Orders in this status count as paid (e.g. a status after Processing).', 'woo-add-order-status' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="wpb-osm-email"><?php esc_html_e( 'Customer email', 'woo-add-order-status' ); ?></label></th>
				<td>
					<textarea id="wpb-osm-email" class="large-text" rows="5" name="status[email]"><?php echo esc_textarea( $wpb_osm_form['email'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave empty for no email. Placeholders: {customer_name}, {order_number}, {order_date}, {site_title}. Subject, heading and on/off are under WooCommerce → Settings → Emails.', 'woo-add-order-status' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( $editing ? __( 'Update status', 'woo-add-order-status' ) : __( 'Add status', 'woo-add-order-status' ) ); ?>
		<?php if ( $editing ) : ?>
			<a href="<?php echo esc_url( $wpb_osm_page ); ?>"><?php esc_html_e( 'Cancel', 'woo-add-order-status' ); ?></a>
		<?php endif; ?>
	</form>
</div>
