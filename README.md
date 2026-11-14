# Custom Order Statuses for WooCommerce

Custom WooCommerce order statuses with colours, emails, automatic transitions and per-status rules. No upsells.

- **Version:** 3.1.0
- **Requires:** WordPress 6.5+, WooCommerce 8.2+, PHP 7.4+
- **Tested up to:** WordPress 7.1, WooCommerce 11.3 (HPOS and legacy order storage), PHP 8.2 and 8.5
- **License:** GPLv2 or later

The WordPress.org readme is [`readme.txt`](readme.txt).

## Features

- Add, edit and delete custom order statuses under **WooCommerce → Order Status Manager**.
- Choose where each status sits in the status list.
- Colour per status in the orders list; text colour adjusts for readability.
- Email per status, using the WooCommerce email template, with order details and placeholders (`{customer_name}`, `{order_number}`, `{order_date}`, `{site_title}`). Send to customer, admin or both, plus CC. Managed under **WooCommerce → Settings → Emails**.
- Per-status rules: counts as paid, allow downloads, reduce stock (once), keep editable, one-click action button.
- Automatic move after N hours/days to another status (Action Scheduler; skipped if the order moved on; adds a note).
- Customer description shown on the order page.
- Status after checkout per payment method.
- "Change status to …" bulk actions.
- Statuses with orders can't be deleted, so orders never go missing.
- REST API (requires `manage_woocommerce`):
  - `GET /wp-json/wpb-osm/v1/statuses`
  - `POST /wp-json/wpb-osm/v1/statuses` with any status field (`key`, `label`, `after`, `color`, `description`, `email`, `recipient`, `cc`, `paid`, `downloads`, `reduce_stock`, `editable`, `action_button`, `auto_after`, `auto_unit`, `auto_to`); creates, or updates when `key` exists
  - `DELETE /wp-json/wpb-osm/v1/statuses/{key}` (409 while orders use it)
- HPOS and block checkout compatible.

## Status keys

Up to 17 characters (`a-z`, `0-9`, `-`, `_`). WooCommerce stores `wc-{key}` in a 20-character column. Keys can't be renamed after creation.

## Email template override

Copy `templates/emails/custom-status.php` (or `plain/custom-status.php`) to `yourtheme/woocommerce/emails/`.

## Installation

1. Install and activate WooCommerce.
2. Upload `exivon-custom-order-statuses-for-woocommerce` to `/wp-content/plugins/` and activate.
3. Go to **WooCommerce → Order Status Manager**.

## Uninstall

Deleting the plugin removes its settings. Orders are not changed: move orders out of custom statuses first, or they won't show in WooCommerce's order lists.

## Changelog

See [`readme.txt`](readme.txt).
