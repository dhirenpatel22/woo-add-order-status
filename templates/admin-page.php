<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
    <h1>Order Status Manager</h1>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'wpb_osm_nonce' ); ?>
        <input type="hidden" name="action" value="wpb_osm_save" />

        <table class="form-table">
            <tr>
                <th>Key</th>
                <td><input name="statuses[0][key]" required /></td>
            </tr>
            <tr>
                <th>Label</th>
                <td><input name="statuses[0][label]" required /></td>
            </tr>
            <tr>
                <th>After</th>
                <td><input name="statuses[0][after]" value="wc-processing" /></td>
            </tr>
            <tr>
                <th>Color</th>
                <td><input type="color" name="statuses[0][color]" /></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><textarea name="statuses[0][email]"></textarea></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
