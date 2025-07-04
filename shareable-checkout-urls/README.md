# Shareable Checkout URLs

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
6. [File Structure](#file-structure)  
7. [Contributing](#contributing)  
8. [License](#license)  

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

### Generated URL & Coupon

- The **Generated URL** field displays a link like:  
  ```
  https://your-site.com/checkout-link/?products=123:2,456:1&coupon=SUMMER20
  ```
- Click **Copy** to copy the URL to your clipboard.  

### Embedable Shortcode

1. Under **Embedable Shortcode**, enter your **Link Text**.  
2. The generated shortcode `[scu_link id="789" text="Buy Now"]` appears; click **Copy Shortcode**.  

### QR Code Generator

- A QR code of the checkout URL renders automatically.  
- In **Output mode**, choose:
  - **Data-URI Image**: inline `<img src="data:image/png…">`  
  - **Embed Snippet**: exact HTML in a textarea  
  - **Download PNG**: saves `qr-code-{POST_ID}.png`  

### Shortcode Output

Use the shortcode anywhere to render a link:
```php
echo do_shortcode( '[scu_link id="789" text="Buy Now"]' );
```

---

## Endpoint Slug

By default, shareable URLs use the slug `checkout-link`.  
To customize, go to **WooCommerce → Settings → Advanced → Shareable URLs → Endpoint Slug**.

---

## File Structure

```
shareable-checkout-urls.php           Main plugin file  
includes/
  ├ js/
  │   ├ scu-admin.js                 Admin builder logic  
  │   └ qr-generator.js              QR generation helper  
  └ css/
      └ scu-admin.css                Admin styles  
readme.md                             This file  
```

---

## Contributing

1. Fork the repository.  
2. Create your feature branch (`git checkout -b my-feature`).  
3. Commit your changes (`git commit -am 'Add new feature'`).  
4. Push to the branch (`git push origin my-feature`).  
5. Submit a pull request.  

---

## License

This plugin is licensed under the **GPL v2 or later**. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

