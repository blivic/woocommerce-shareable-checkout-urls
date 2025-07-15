<?php
	/**
 * Plugin Name:       Shareable Checkout URLs
 * Description:       Build, save & edit shareable checkout URLs (products + coupon) under Products.
 * Version:           1.4.0
 * Author:            Media X
 * Author URI:        https://media-x.hr
 * Text Domain:       shareable-checkout-urls
 * Requires at least: WordPress 5.5
 * Tested up to:      WordPress 6.8
 * WC requires at least: WooCommerce 10.0
 * WC tested up to:      WooCommerce 10.0
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'plugins_loaded', 'mx_scu_load_textdomain' );
function mx_scu_load_textdomain() {
    load_plugin_textdomain(
        'shareable-checkout-urls',    
        false,                        
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}


define( 'SCU_PLUGIN_FILE', __FILE__ );
// Core includes
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/post-types.php';
require_once __DIR__ . '/includes/meta-boxes.php';
require_once __DIR__ . '/includes/admin-scripts.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/checkout-handlers.php';
require_once __DIR__ . '/includes/order-tracking.php';
require_once __DIR__ . '/includes/rest-api.php';
require_once __DIR__ . '/includes/ajax-handlers.php';
require_once __DIR__ . '/includes/shortcodes.php';



add_action( 'admin_notices', 'mx_wc_version_notice' );
function mx_wc_version_notice() {
    if ( is_admin() && ( ! class_exists( 'WooCommerce' ) || version_compare( WC()->version, '10.0', '<' ) ) ) {
        echo '<div class="error"><p>'
            . esc_html__( 'Shareable Checkout URLs requires WooCommerce 10.0 or higher. Please update WooCommerce.', 'shareable-checkout-urls' )
            . '</p></div>';
    }
}

add_action( 'admin_notices', 'mx_scu_show_access_denied_notice' );
function mx_scu_show_access_denied_notice() {
	if ( ! is_admin() || mx_scu_current_user_has_access() ) {
		return;
		}

	global $pagenow;

	$screen = get_current_screen();
	if ( ! $screen ) return;

	if (
		$pagenow === 'post.php' || $pagenow === 'post-new.php'
	) {
		$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : get_post_type();
		if ( $post_type === 'scu_link' ) {
			echo '<div class="notice notice-error is-dismissible"><p>'
				. esc_html__( 'You do not have permission to manage Shareable Checkout URLs.', 'shareable-checkout-urls' )
				. '</p></div>';
		}
	}
}

add_filter(
    'plugin_action_links_' . plugin_basename( SCU_PLUGIN_FILE ),
    'mx_scu_add_action_links'
);
function mx_scu_add_action_links( $links ) {
    $settings_url  = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=scu_settings' );
    $settings_link = sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url( $settings_url ),
        __( 'Settings', 'shareable-checkout-urls' )
    );

    $manage_url  = admin_url( 'edit.php?post_type=scu_link' );
    $manage_link = sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url( $manage_url ),
        __( 'Shareable URLs', 'shareable-checkout-urls' )
    );

    array_unshift( $links, $manage_link, $settings_link );

    return $links;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
} );

register_activation_hook( __FILE__, 'mx_activation_check' );
function mx_activation_check() {
    if ( ! class_exists( 'WooCommerce' )
      || version_compare( WC()->version, '10.0', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'Shareable Checkout URLs requires WooCommerce 10.0+', 'shareable-checkout-urls' ),
            esc_html__( 'Plugin Activation Error',        'shareable-checkout-urls' ),
            [ 'back_link' => true ]
        );
    }
}
