=== Woo Add Order Status ===
Contributors: dhirenpatel22
Tags: woocommerce, order status, custom order status, order management
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 3.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add and manage custom WooCommerce order statuses, with colours, customer emails and bulk actions.

== Description ==

Add statuses such as "Awaiting Shipment" or "Ready for Collection" to your WooCommerce store, without code.

* Add, edit and delete custom order statuses under WooCommerce → Order Status Manager.
* Choose where each status sits in the status list.
* Pick a colour for each status in the orders list. Text colour adjusts automatically for readability.
* Optional customer email per status, sent with your WooCommerce email template and order details. Placeholders: {customer_name}, {order_number}, {order_date}, {site_title}. Subject, heading and on/off live under WooCommerce → Settings → Emails.
* Mark a status as "paid" so WooCommerce treats those orders as paid.
* "Change status to …" bulk actions in the orders list.
* Statuses that still have orders can't be deleted, so orders never go missing.
* REST API for store managers: `GET` / `POST /wp-json/wpb-osm/v1/statuses`, `DELETE /wp-json/wpb-osm/v1/statuses/{key}`.
* Compatible with High-Performance Order Storage (HPOS) and the block checkout.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `woo-add-order-status` folder to `/wp-content/plugins/` and activate it.
3. Go to WooCommerce → Order Status Manager.

== Frequently Asked Questions ==

= Does it need WooCommerce? =

Yes. WordPress won't activate it without WooCommerce.

= How long can a status key be? =

Up to 17 characters: lowercase letters, numbers, hyphens and underscores. WooCommerce stores the status with a "wc-" prefix in a 20-character column. Keys can't be changed after creation, because orders store the key.

= What happens to orders if I delete the plugin? =

Deleting the plugin removes its settings but doesn't change any orders. Orders still in a custom status keep it in the database but won't show in WooCommerce's order lists until you reinstall the plugin or move them to another status first. Move them before uninstalling.

= Who can use the REST API? =

Users with the `manage_woocommerce` capability (shop managers and administrators).

== Changelog ==

= 3.1.0 =
* Fix: saving a status no longer deletes the others. The admin page now lists every status with edit and delete.
* Fix: status colours now show in the orders list.
* Fix: apostrophes no longer saved with backslashes.
* Fix: emails no longer attempted when an order has no billing email.
* Security: REST API now requires the `manage_woocommerce` capability (it was public).
* New: validation. Keys are limited to 17 characters, can't clash with existing statuses, and "Show after" is a dropdown.
* New: statuses that still have orders can't be deleted.
* New: each status email is a real WooCommerce email (template, order details, placeholders, settings).
* New: bulk "Change status to …" actions, "counts as paid" option, REST create/update/delete.
* New: HPOS and block checkout compatibility declared; requires WooCommerce; uninstall cleanup.
* Tested with WordPress 7.1 and WooCommerce 11.3.

= 3.0.0 =
* First public version.

== Upgrade Notice ==

= 3.1.0 =
Fixes a bug where saving a status deleted all other statuses, and locks down the REST API. Update recommended.
