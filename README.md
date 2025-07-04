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
5. [Endpoint Slug](#endpoint-slug)
6.  [To Do](#to-do)  

---

## Features

- **Private CPT** under _Products_ for managing shareable links  
- **Drag-and-drop** product selection with AJAX search and stock badges  
- **Coupon code** support  
- **One-click** copy of generated checkout URL and shortcode  
- **Embedable QR code**: Data-URI, HTML snippet or PNG download  

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

![Edit Shareable URL Options](https://media-x.hr/wp-content/uploads/2025/07/Edit-Shareable-URL.jpg)

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

---

### QR Code Generator

- A QR code of the checkout URL renders automatically.  
- In **Output mode**, choose:
  - **Data-URI Image**: inline `<img src="data:image/png…">`  
  - **Embed Snippet**: exact HTML in a textarea  
  - **Download PNG**: saves `qr-code-{POST_ID}.png`  

---

### Shortcode Output

Use the shortcode anywhere to render a link:
```php
echo do_shortcode( '[scu_link id="789" text="Buy Now"]' );
```

---

## Endpoint Slug

By default, shareable URLs use the static slug `checkout-link`.  Until the endpoint becomes dynamic in core, this slug will be used.
To customize, go to **WooCommerce → Settings → Advanced → Shareable URLs → Endpoint Slug**.

![Endpoint Slug Settings](https://media-x.hr/wp-content/uploads/2025/07/Edit-Shareable-URL-endpoint.jpg)

---

## To Do

Here's a list of upcoming enhancements and features planned for future releases:

- Add a coupon search field in the builder metabox to replace the current textarea
- Implement dynamic endpoint slug in WooCommerce core
- Add settings for customizing QR code colors, size, and format
- Enhance shortcode attributes for advanced styling and behavior
- Add localization and translation support



