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
   - [Shortcode Output](#shortcode-output)
   - [QR Code Generator](#qr-code-generator)  
   - [Custom promo message](#custom-message)
   - [Usage limit](#usage-limit)
   - [Usage tracking](#usage-tracking)
   - [UTM & Pixel tracking tracking](#utm-tracking)
5. [General settings](#general-settings)
6.  [To Do](#to-do)
7.  [Changelog](#changelog)

---

## Features

- Private CPT under Products for managing shareable links  
- Drag-and-drop product selection with AJAX search and stock badges  
- Coupon code support  
- One-click copy of generated checkout URL and shortcode  
- Embeddable QR Code Generator  </br>
  - Adjust size and light/dark colors  </br>
  - Output modes: Data-URI image, embed HTML snippet, PNG download, true SVG download (print-ready)  
- Optional product validation caching (60 min; auto-cleared on updates)  
- “Clear Validation Cache” button in settings  
- Usage tracking & analytics: link usage, orders, conversion rate, and revenue per link  
- Sortable admin columns: Usage, Orders, Conversion Rate, Revenue  
- Debug Mode: logs cache hits, validation results, applied coupons, and redirects to `debug.log`  
- Custom promo message on checkout: WooCommerce notice or custom block (supports HTML)  
- Usage Limits: set a maximum number of uses per link; links auto-draft when expired  
- Modular file structure for easier maintenance and scalability  
- UTM & Pixel Tracking  </br>
  - Clean-URL tracking: UTM tags appended only on redirect  </br>
  - Meta-pixel support: specify a Pixel ID globally or per-link; fires client-side  </br>
  - Granular control: per-link “Global / Custom / None” toggle; master “Enable Tracking” switch in Woo → Advanced  
- REST API endpoints under `/wp-json/scu/v1/links`  </br>
  - CRUD operations for shareable-checkout links  </br>
  - All routes respect the “Minimum Role to Access SCU” setting via `mx_scu_current_user_has_access()`  
- Coupon Search Field in Builder Metabox (replaces textarea)  
- Embedable Shortcodes with advanced attributes: `text`, `class`, `style`, `target`, `rel`, `button`, `align`, `aria-label`, `title`  



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

### Shortcode Output

Use the shortcode anywhere to render a link:
```php
echo do_shortcode( '[scu_link id="789" text="Buy Now"]' );
```

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

### Custom message

Show message on checkout (per URL), as a WooCommerce notice or inside a custom block (supports HTML)

---

### Usage limit

- Admins can now set a maximum allowed uses per link. Once the limit is reached:<br/>
  – The link is automatically set to draft (disabled).<br/>
  – Visitors see a "This checkout link is no longer available." message when accessing expired links.<br/>
  – Usage count is tracked and displayed in the SCU admin list.<br/>
  – Unlimited usage remains the default (when field is empty or zero).

---

### Usage tracking

- Usage tracking & analytics: link usage, orders, conversion rate, revenue per link
- Sortable admin columns: Usage, Orders, Conversion Rate, Revenue

![Usage tracking](https://media-x.hr/wp-content/uploads/2025/07/share02.jpg)

---

### UTM tracking

- If activated and set in Global settings, you can use global defaults for UTM tracking, use custom UTM/pixel for this link or disable UTM tracking completely
- NOTE: When UTM & Pixel Tracking IS enabled, your shareable URLs remain free of any query strings – default UTM tags and your Meta Pixel ID will only be appended at checkout (for analytics)

---

## General settings

By default, shareable URLs use the static slug `checkout-link`.  Until the endpoint becomes dynamic in core, this slug will be used.
To customize, go to **WooCommerce → Settings → Advanced → Shareable URLs → Endpoint Slug**.

You can enable Product Validation Caching which should improve performance on busy sites by avoiding repeated validation for popular links. Cached results expire after 60 minutes and are also cleared automatically when a product is updated, unpublished, deleted, or its stock status changes.
You can flusg the cache (transients) manually by clicking the Clear Validation Cache button.

If you need to diagnose checkout issues, enable Debug Mode which logs product validation, cache hits/misses, applied coupons, and redirect URLs to debug.log.

Minimum Role to Access SCU” option in WooCommerce Advanced → Shareable URLs<br/>
mx_scu_current_user_has_access() enforces that setting across:<br/>
  – Admin menu, post list, meta-boxes, save_post, AJAX search endpoints, settings screen  <br/>
  – Shows a dismissible notice on SCU edit/add pages when access is denied  <br/>
  – Removes SCU menu for users below the selected role


![General Settings](https://media-x.hr/wp-content/uploads/2025/07/share-03.jpg)

---

## To Do

Here's a list of upcoming enhancements and features planned for future releases:

- Ensure the compatibility with dynamic endpoint slug (If/when the option gets into WooCommerce core)
- Add localization and translation support

---

## Changelog

= 1.4 =
* New: Send Shareable URL by Email<br/>
  – Envelope icon in the SCU list table opens a modal with To / CC / BCC fields<br/>
  – Global Email Subject & Body templates in settings (available placeholders: {link}, {site_name}, {product_list}, {max_uses}, {coupon_section}; fall back to friendly defaults)
* New: Per-Send Subject & Body Overrides<br/>
  – In the “Send by Email” modal you can check “Override defaults” and enter a custom subject & message for each recipient, without touching your global templates.
* New: Optional Email History logs<br/>
  – Email History toggle in settings<br/>
  – Record each send (recipients + timestamp) in post-meta<br/>
  – Email History metabox on the edit screen (with pagination)
* Localization and translation support<br/>
  – All UI strings, settings labels and default templates are fully translatable via the shareable-checkout-urls textdomain.
  
= 1.3 =
* New: Modular file structure
* UTM & Pixel Tracking<br/>
  – Clean-URL tracking – UTM tags don't bloat the shareable link; appended only on redirect.<br/>
  – Meta-pixel support – Specify a Pixel ID globally or per-link; fires client-side from session.<br/>
  – Granular control – New “Global / Custom / None” toggle under each link + master “Enable Tracking” switch in Woo → Advanced.
* New: REST API endpoints under `/wp-json/scu/v1/links`  <br/>
  – CRUD: Manage shareable-checkout links via REST endpoints<br/>
  – All routes check against the “Minimum Role to Access SCU” setting via `mx_scu_current_user_has_access()`.
* New: Link-Use Webhook URLs  
 – Configure one or more webhook endpoints (one per line) ; when a shareable URL is hit, SCU POSTs usage data (link ID, products, coupon, timestamp) to each URL.
* Fix: General bug-fixes & cleanup

= 1.2 =
* New: Optional product validation caching (60 min, auto-cleared on product updates)
* New: "Clear Validation Cache" button in settings
* New: Tracks link usage, calculates conversion rate and revenue per SCU link
* New: Added sortable admin columns: Usage, Orders, Conversion Rate, Revenue
* New: Debug Mode logs cache hits, validation results, coupons, and redirects to debug.log
* New: Added support for displaying a custom promo message on the checkout page when a shareable URL is used.<br/>
  – Users can choose between showing message as a WooCommerce notice or a custom block.<br/>
  – Promo message supports basic HTML formatting (e.g. `<strong>`, `<em>`, `<a href="">`).<br/>
  – Custom block is automatically moved above all WooCommerce notices for better visibility.
* Added: Usage limit feature for Shareable Checkout URLs. Admins can now set a maximum allowed uses per link. Once the limit is reached:<br/>
  – The link is automatically set to draft (disabled).<br/>
  – Visitors see a "This checkout link is no longer available." message when accessing expired links.<br/>
  – Usage count is tracked and displayed in the SCU admin list.<br/>
  – Unlimited usage remains the default (when field is empty or zero).
* Improved: Centralized validation logic for better reliability
* New: “Minimum Role to Access SCU” option in WooCommerce Advanced → Shareable URLs
* New: mx_scu_current_user_has_access() enforces that setting across:<br/>
  – Admin menu, post list, meta-boxes, save_post, AJAX search endpoints, settings screen  <br/>
  – Shows a dismissible notice on SCU edit/add pages when access is denied  <br/>
  – Removes SCU menu for users below the selected role



= 1.1 =
* NEW: Added a coupon search field in the builder metabox to replace the current textarea
* NEW: Added settings for customizing QR code size & colors
* NEW: Added true SVG QR code downloads (fully scalable, print-ready).
* NEW: Switched to QRCodeSVG library for native vector output.
* FIX: Preview and download logic improved for size accuracy.
* FIX: Improved event handler logic prevents multiple PNG files from being downloaded due to duplicate event listeners.

= 1.0  =
* initial release
