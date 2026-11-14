=== Custom Order Statuses for WooCommerce ===
Contributors: dhirenpatel22
Tags: woocommerce, order status, custom order status, order management
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 3.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Custom WooCommerce order statuses with colours, emails, automatic transitions and per-status rules. No upsells.

== Description ==

Add statuses such as "Awaiting Shipment" or "Ready for Collection" to your WooCommerce store, without code. Everything is free and on one screen.

**Statuses**

* Add, edit and delete custom order statuses under WooCommerce → Order Status Manager.
* Choose where each status sits in the status list.
* Colour per status in the orders list; text colour adjusts automatically for readability.
* Statuses that still have orders can't be deleted, so orders never go missing.
* Keys are checked: no clashes with existing statuses and no keys too long for WooCommerce to store.

**Per-status rules**

* Counts as paid.
* Allow downloads of purchased files.
* Reduce stock when an order enters the status (once per order).
* Keep the order editable.
* One-click "Change to …" button in the orders list, plus bulk actions.
* Automatic move: after a number of hours or days, move the order to another status. Skipped if the order has moved on; adds an order note. Runs on WooCommerce's built-in scheduler.
* Customer description: a short message shown to the customer on their order page while the order is in that status.

**Emails**

* One email per status, sent with your WooCommerce email template and order details.
* Send to the customer, the store admin, or both, plus CC addresses.
* Placeholders: {customer_name}, {order_number}, {order_date}, {site_title}.
* One "Custom order status" entry under WooCommerce → Settings → Emails for subject, heading, on/off and preview. Use {status} for the status name.

**Checkout**

* Choose the status new orders get for each payment method (bank transfer, cheque, cash on delivery, and gateways that complete payment through WooCommerce).

**For developers**

* REST API for store managers: `GET` / `POST /wp-json/wpb-osm/v1/statuses`, `DELETE /wp-json/wpb-osm/v1/statuses/{key}`.
* Email templates can be overridden from your theme.
* Compatible with High-Performance Order Storage (HPOS) and the block checkout.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `exivon-custom-order-statuses-for-woocommerce` folder to `/wp-content/plugins/` and activate it.
3. Go to WooCommerce → Order Status Manager.

== Frequently Asked Questions ==

= Does it need WooCommerce? =

Yes. WordPress won't activate it without WooCommerce.

= How long can a status key be? =

Up to 17 characters: lowercase letters, numbers, hyphens and underscores. WooCommerce stores the status with a "wc-" prefix in a 20-character column. Keys can't be changed after creation, because orders store the key.

= What happens to orders if I delete the plugin? =

Deleting the plugin removes its settings but doesn't change any orders. Orders still in a custom status keep it in the database but won't show in WooCommerce's order lists until you reinstall the plugin or move them to another status first. Move them before uninstalling.

= How do automatic moves work? =

When an order enters a status with a rule, WooCommerce's scheduler (Action Scheduler, built into WooCommerce) queues the move. When the time comes, the order is moved only if it's still in that status, and a note is added to the order. See WooCommerce → Status → Scheduled Actions, group "wpb-osm".

= Who can use the REST API? =

Users with the `manage_woocommerce` capability (shop managers and administrators).

== Screenshots ==

1. Orders list with coloured custom statuses and one-click "Change to" buttons.
2. Order Status Manager: every status with its rules and order count.
3. Editing a status: position, colour, customer description, rules, automatic move and email.
4. The customer's order page showing the status and its description.
5. One "Custom order status" email under WooCommerce → Settings → Emails, with live preview of your status message.
6. Choose the status new orders get for each payment method.

== Changelog ==

= 3.1.0 =
* Renamed from "Woo Add Order Status" to "Custom Order Statuses for WooCommerce" (WordPress.org naming rules). The main plugin file is now `exivon-custom-order-statuses-for-woocommerce.php`: if you installed 3.0.0 manually, delete the old folder and activate the new one. Your statuses are kept.
* Fix: saving a status no longer deletes the others. The admin page now lists every status with edit and delete.
* Fix: status colours now show in the orders list.
* Fix: apostrophes no longer saved with backslashes.
* Fix: emails no longer attempted when an order has no billing email.
* Security: REST API now requires the `manage_woocommerce` capability (it was public).
* New: validation. Keys are limited to 17 characters, can't clash with existing statuses, and "Show after" is a dropdown.
* New: statuses that still have orders can't be deleted.
* New: each status email is a real WooCommerce email (template, order details, placeholders, settings).
* New: bulk "Change status to …" actions, "counts as paid" option, REST create/update/delete.
* New: per-status rules: allow downloads, reduce stock, keep editable, one-click action button.
* New: automatic move to another status after a set number of hours or days.
* New: customer-facing status description on the order page.
* New: email recipients (customer, admin or both) and CC addresses.
* New: one "Custom order status" email in WooCommerce → Settings → Emails (shared subject/heading with {status}); preview and test emails work.
* New: status after checkout per payment method.
* New: HPOS and block checkout compatibility declared; requires WooCommerce; uninstall cleanup.
* Tested with WordPress 7.1 and WooCommerce 11.3.

= 3.0.0 =
* First public version.

== Upgrade Notice ==

= 3.1.0 =
Renamed plugin; reactivate after updating. Fixes a bug where saving a status deleted all other statuses, and locks down the REST API.
