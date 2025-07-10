# WooCommerce Shareable Checkout URLs

Build, save & edit shareable checkout URLs (products + coupon) under **Products** in WooCommerce.

[![WordPress Version](https://img.shields.io/badge/Requires%20WP-5.5%2B-blue)]()
[![WooCommerce Version](https://img.shields.io/badge/Requires%20WC-10.0%2B-blue)]()
[![License: GPL v2 or later](https://img.shields.io/badge/License-GPLv2%2B-green)]()

---

## Table of Contents

1. [Features](#features)  
2. [Requirements](#requirements)  
3. [Installation](#installation)  
4. [Usage](#usage)  
   - [Builder Metabox](#builder-metabox)  
   - [Generated URL & Coupon](#generated-url--coupon)  
   - [Embedable Shortcode](#embedable-shortcode)  
   - [QR Code Generator](#qr-code-generator)  
   - [Shortcode Output](#shortcode-output)
   - [Custom promo message](#custom-message)
5. [General settings](#general-settings)
6.  [To Do](#to-do)
7.  [Changelog](#changelog)

---

## Features

- Private CPT under Products for managing shareable links
- Drag-and-drop product selection with AJAX search and stock badges
- Coupon code support
- One-click copy of generated checkout URL and shortcode
- Embedable QR code: Data-URI, HTML snippet or PNG download
- Optional product validation caching (60 min, auto-cleared on updates)
- Usage tracking & analytics: link usage, orders, conversion rate, revenue per link
- Sortable admin columns: Usage, Orders, Conversion Rate, Revenue
- Debug Mode: logs cache hits, validation, coupons, and redirects to debug.log
- Custom promo message on checkout: WooCommerce notice or custom block (supports HTML)
- Minimum Role to Access SCU: restrict SCU features by user role



---

## Requirements

- WordPress 5.5 or higher  
- WooCommerce 10.0 or higher  
- Tested up to WordPress 6.8 & WooCommerce 10.0  

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/shareable-checkout-urls/`.  
2. Activate through the **Plugins** screen in WordPress.  
3. Ensure WooCommerce 10.0+ is active (the plugin will deactivate itself otherwise).  

---

## Usage

### Builder Metabox

1. Under **Products → Shareable checkout URLs**, click **Add New**.  
2. In the **Build Shareable URL** metabox, search for products by name (with stock info), set quantities, and drag to reorder.  
3. (Optional) Enter a **Coupon code**.

![Edit Shareable URL Options](https://media-x.hr/wp-content/uploads/2025/07/share01.jpg)


---

### Generated URL & Coupon

- The **Generated URL** field displays a link like:  
  ```
  https://your-site.com/checkout-link/?products=123:2,456:1&coupon=SUMMER20
  ```
- Click **Copy** to copy the URL to your clipboard.  

---

### Embedable Shortcode

1. Under **Embedable Shortcode**, enter your **Link Text**.  
2. The generated shortcode `[scu_link id="789" text="Buy Now"]` appears; click **Copy Shortcode**.
3. For advanced use, shortcode now supports args: text, class, style, target, rel, button, align, aria-label, title.  

---

### QR Code Generator

- A QR code of the checkout URL renders automatically.
- Change QR code size and Light & Dark color of the code
- In **Output mode**, choose:
  - **Data-URI Image**: inline `<img src="data:image/png…">`  
  - **Embed Snippet**: exact HTML in a textarea  
  - **Download PNG**: saves `qr-code-{POST_ID}.png`
  - **Download SVG**: saves a high-quality, scalable .svg file `qr-code-{ID}.svg`  

---

### Shortcode Output

Use the shortcode anywhere to render a link:
```php
echo do_shortcode( '[scu_link id="789" text="Buy Now"]' );
```

---

### Custom message

Show message on checkout (per URL), as a WooCommerce notice or inside a custom block (supports HTML)

---

## General settings

By default, shareable URLs use the static slug `checkout-link`.  Until the endpoint becomes dynamic in core, this slug will be used.
To customize, go to **WooCommerce → Settings → Advanced → Shareable URLs → Endpoint Slug**.

You can enable Product Validation Caching which should improve performance on busy sites by avoiding repeated validation for popular links. Cached results expire after 60 minutes and are also cleared automatically when a product is updated, unpublished, deleted, or its stock status changes.
You can flusg the cache (transients) manually by clicking the Clear Validation Cache button.

If you need to diagnose checkout issues, enable Debug Mode which logs product validation, cache hits/misses, applied coupons, and redirect URLs to debug.log.

![Endpoint Slug Settings](https://media-x.hr/wp-content/uploads/2025/07/share03.jpg)

---

## To Do

Here's a list of upcoming enhancements and features planned for future releases:

- Ensure the compatibility with dynamic endpoint slug (If/when the option gets into WooCommerce core)
- Enhance shortcode attributes for advanced styling and behavior
- Optional product validation caching
- Track link usage, order conversions, and revenue per SCU link
- Debug Mode
- Add localization and translation support

---

## Changelog

### = 1.2 =
* New: Optional product validation caching (60 min, auto-cleared on product updates)
* New: "Clear Validation Cache" button in settings
* New: Tracks link usage, calculates conversion rate and revenue per SCU link
* New: Added sortable admin columns: Usage, Orders, Conversion Rate, Revenue
* New: Debug Mode logs cache hits, validation results, coupons, and redirects to debug.log
* New: Added support for displaying a custom promo message on the checkout page when a shareable URL is used.
  – Users can choose between showing message as a WooCommerce notice or a custom block.
  – Promo message supports basic HTML formatting (e.g. `<strong>`, `<em>`, `<a href="">`).
  – Custom block is automatically moved above all WooCommerce notices for better visibility.
* Improved: Centralized validation logic for better reliability
* New: Added optional shortcode args for advanced use: text, class, style, target, rel, button, align, aria-label, title.  



### 1.1.0 (08-07-2025)
* NEW: Added a coupon search field in the builder metabox to replace the current textarea
* NEW: Added settings for customizing QR code size & colors
* NEW: Added true SVG QR code downloads (fully scalable, print-ready).
* NEW: Switched to QRCodeSVG library for native vector output.
* FIX: Preview and download logic improved for size accuracy.
* FIX: Improved event handler logic prevents multiple PNG files from being downloaded due to duplicate event listeners.
  
### 1.0.0 (04-07-2025)
* initial release
