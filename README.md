# Woo Add Order Status

A lightweight plugin to easily add and manage custom order statuses in your WooCommerce store.

## Plugin Details

- Author: Dhiren Patel
- Author URI: https://dhirenpatel.com
- Version: 3.0.0
- Text Domain: woo-add-order-status
- Tags: woocommerce, order status, custom order status, woo order
- Requires at least: 5.8
- Tested up to: 6.7
- Requires PHP: 7.4
- Stable tag: 3.0.0
- License: GPLv2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Description

**Woo Add Order Status** is a lightweight and easy-to-use plugin that lets you add unlimited custom order statuses to your WooCommerce store — no coding required.

Whether you need statuses like "Awaiting Shipment", "Being Processed", or anything specific to your workflow, this plugin makes it simple to create and manage them directly from your WooCommerce admin panel.

### Key Features

- Lightweight — minimal footprint, no bloat
- Easily add new custom order statuses from the admin panel
- Set a custom background color for each status (visible in the orders list)
- Control where each status appears in the order flow (insert after any existing status)
- Send a custom email notification to the customer when an order moves to a custom status
- REST API support for managing statuses programmatically
- Works seamlessly with WooCommerce's native order management

### Why use Woo Add Order Status?

Most order status plugins are heavy and packed with features you never use. This plugin does one thing and does it well — lets you add new WooCommerce order statuses quickly and cleanly, without slowing down your store.

## Installation

1. Upload the `woo-add-order-status` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce > Order Status Manager** to add and manage your custom statuses.

## Frequently Asked Questions

### Does this plugin require WooCommerce?

Yes. WooCommerce must be installed and active.

### Can I add as many custom statuses as I need?

Yes, there is no limit to the number of custom order statuses you can add.

### Will custom statuses appear in the WooCommerce orders list?

Yes. Each status is registered with WooCommerce and appears in the orders list with its custom color.

### Can I send email notifications for custom statuses?

Yes. You can set a custom email body per status that is sent to the customer when their order is updated to that status.

### Is there a REST API?

Yes. The plugin includes a REST API endpoint for managing statuses programmatically.

## Screenshots

1. Admin panel — add and manage custom order statuses
2. WooCommerce orders list — custom statuses displayed with colors

## Changelog

### 3.0.0

- Production-ready release
- Added color picker for status labels
- Added custom email notification per status
- Added REST API support
- Fixed CSS output to use proper style tags
- Fixed textdomain loading to comply with WordPress 6.7+

## Upgrade Notice

### 3.0.0

Initial stable release. No upgrade steps required.
