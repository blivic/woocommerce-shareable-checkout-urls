<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'woocommerce_get_sections_advanced', 'mx_scu_add_advanced_section' );
function mx_scu_add_advanced_section( $sections ) {
    if ( ! function_exists( 'mx_scu_current_user_has_access' ) || ! mx_scu_current_user_has_access() ) {
        return $sections;
    }
    $sections['scu_settings'] = __( 'Shareable URLs', 'shareable-checkout-urls' );
    return $sections;
}

add_filter( 'woocommerce_get_settings_advanced', 'mx_scu_advanced_settings', 10, 2 );
function mx_scu_advanced_settings( $settings, $current_section ) {
    if ( 'scu_settings' !== $current_section ) {
        return $settings;
    }

    $scu_settings = [];

    $scu_settings[] = [
        'title' => __( 'Shareable URLs Settings', 'shareable-checkout-urls' ),
        'type'  => 'title',
        'desc'  => __( 'Customize the options used by the Shareable Checkout URLs plugin.', 'shareable-checkout-urls' ),
        'id'    => 'scu_endpoint_options',
    ];

    $scu_settings[] = [
        'title'    => __( 'Endpoint Slug', 'shareable-checkout-urls' ),
        'id'       => 'scu_endpoint_slug',
        'type'     => 'text',
        'desc'     => __( 'The URL path segment for shareable-checkout links (e.g., <code>checkout-link</code>, <code>fast-checkout</code>).' , 'shareable-checkout-urls' ),
        'default'  => 'checkout-link',
        'autoload' => false,
    ];

    $scu_settings[] = [
        'title'   => __( 'Enable Product Validation Caching', 'shareable-checkout-urls' ),
        'id'      => 'scu_enable_cache',
        'type'    => 'checkbox',
        'default' => 'no',
        'desc'    => __( 'Improves performance by avoiding repeated validation for popular links. Cached results expire after 60 minutes and are also cleared automatically when a product is updated, unpublished, deleted, or its stock status changes.', 'shareable-checkout-urls' ),
        'autoload' => false,
    ];

    $scu_settings[] = [
        'type'               => 'custom_button',
        'id'                 => 'scu_clear_cache_button',
        'title'              => __( 'Clear Validation Cache', 'shareable-checkout-urls' ),
        'desc'               => __( 'Manually clears all cached product validation results for checkout links.', 'shareable-checkout-urls' ),
        'custom_attributes'  => [
            'class'   => 'button button-secondary',
            'onclick' => "location.href='" . esc_url( admin_url( 'admin.php?scu_clear_cache=1' ) ) . "'",
        ],
    ];
	
	$scu_settings[] = [
		'title'    => __( 'Link‐Use Webhook URLs', 'shareable-checkout-urls' ),
		'id'       => 'scu_webhook_urls',
		'type'     => 'textarea',
		'css'      => 'min-width:400px; height:80px;',
		'desc'     => __( 'Enter one or more URLs (one per line) to POST link‐use data to when a shareable URL is accessed.', 'shareable-checkout-urls' ),
		'default'  => '',
		'autoload' => false,
	];

    $scu_settings[] = [
        'title'   => __( 'Enable Debug Mode', 'shareable-checkout-urls' ),
        'id'      => 'scu_debug_mode',
        'type'    => 'checkbox',
        'default' => 'no',
        'desc'    => __( 'Logs product validation, cache hits/misses, applied coupons, and redirect URLs to debug.log. Helps diagnose checkout issues.', 'shareable-checkout-urls' ),
        'autoload' => false,
    ];

    $scu_settings[] = [
        'title'   => __( 'Minimum Role to Access SCU', 'shareable-checkout-urls' ),
        'id'      => 'scu_minimum_role',
        'type'    => 'select',
        'default' => 'administrator',
        'desc'    => __( 'Minimum role required to manage links. Administrators always have access regardless of this setting.', 'shareable-checkout-urls' ),
        'options' => [
            'administrator' => __( 'Administrator', 'shareable-checkout-urls' ),
            'shop_manager'  => __( 'Shop Manager',    'shareable-checkout-urls' ),
            'editor'        => __( 'Editor',          'shareable-checkout-urls' ),
            'author'        => __( 'Author',          'shareable-checkout-urls' ),
            'contributor'   => __( 'Contributor',     'shareable-checkout-urls' ),
        ],
    ];

    $scu_settings[] = [
        'type' => 'sectionend',
        'id'   => 'scu_endpoint_options',
    ];
	
    $scu_settings[] = [
        'title' => __( 'UTM & Pixel Tracking', 'shareable-checkout-urls' ),
        'type'  => 'title',
        'desc'  => __( 'When enabled,  your shareable URLs remain free of any query strings – default UTM tags and your Meta Pixel ID will only be appended at checkout (for analytics)', 'shareable-checkout-urls' ),
        'id'    => 'scu_tracking_options',
    ];
    $scu_settings[] = [
        'title'    => __( 'Enable Tracking', 'shareable-checkout-urls' ),
        'id'       => 'scu_enable_tracking',
        'type'     => 'checkbox',
        'default'  => 'no',
        'desc'     => __( 'Globally append UTM tags & pixel on every shareable link.', 'shareable-checkout-urls' ),
        'autoload' => false,
    ];

    $scu_settings[] = [
        'title'       => __( 'Default UTM Source', 'shareable-checkout-urls' ),
        'id'          => 'scu_utm_source',
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __( 'e.g. newsletter, facebook, affiliate', 'shareable-checkout-urls' ),
        'default'     => '',
        'autoload'    => false,
    ];
    $scu_settings[] = [
        'title'       => __( 'Default UTM Medium', 'shareable-checkout-urls' ),
        'id'          => 'scu_utm_medium',
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __( 'e.g. email, cpc, social', 'shareable-checkout-urls' ),
        'default'     => '',
        'autoload'    => false,
    ];
    $scu_settings[] = [
        'title'       => __( 'Default UTM Campaign', 'shareable-checkout-urls' ),
        'id'          => 'scu_utm_campaign',
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __( 'e.g. spring-sale, launch', 'shareable-checkout-urls' ),
        'default'     => '',
        'autoload'    => false,
    ];
    $scu_settings[] = [
        'title'       => __( 'Default UTM Term', 'shareable-checkout-urls' ),
        'id'          => 'scu_utm_term',
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __( 'e.g. shoes, blue-widget', 'shareable-checkout-urls' ),
        'default'     => '',
        'autoload'    => false,
    ];
    $scu_settings[] = [
        'title'       => __( 'Default UTM Content', 'shareable-checkout-urls' ),
        'id'          => 'scu_utm_content',
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __( 'e.g. banner-1, textlink', 'shareable-checkout-urls' ),
        'default'     => '',
        'autoload'    => false,
    ];

    $scu_settings[] = [
        'title'       => __( 'Default Meta Pixel ID', 'shareable-checkout-urls' ),
        'id'          => 'scu_pixel_id',
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __( 'Your Facebook/Meta pixel ID to fire on checkout‐link visit.', 'shareable-checkout-urls' ),
        'default'     => '',
        'autoload'    => false,
    ];
    $scu_settings[] = [ 'type' => 'sectionend', 'id' => 'scu_tracking_options' ];

    return $scu_settings;
}

add_action( 'woocommerce_admin_field_custom_button', 'mx_scu_render_custom_button_field' );
function mx_scu_render_custom_button_field( $value ) {
    $id    = esc_attr( $value['id'] );
    $title = esc_html( $value['title'] );
    $desc  = isset( $value['desc'] ) ? wp_kses_post( $value['desc'] ) : '';
    $attrs = '';

    if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
        foreach ( $value['custom_attributes'] as $key => $val ) {
            $attrs .= ' ' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
        }
    }

    echo '<tr valign="top">';
    echo '<th scope="row" class="titledesc">';
    echo "<label for='{$id}'>{$title}</label>";
    echo '</th><td class="forminp">';
    echo "<button id='{$id}'{$attrs}>{$title}</button>";
    if ( $desc ) {
        echo "<p class='description'>{$desc}</p>";
    }
    echo '</td></tr>';
}


add_action( 'admin_footer', function() {
    if ( 'advanced' === $_GET['tab'] && 'scu_settings' === $_GET['section'] ) {
        ?>
        <script>
        jQuery(function($) {
            function toggleCacheButton() {
                const visible = $('#scu_enable_cache').is(':checked');
                $('#scu_clear_cache_button').closest('tr').toggle(visible);
            }
            toggleCacheButton();
            $('#scu_enable_cache').on('change', toggleCacheButton);

            function toggleTrackingFields() {
                const enabled = $('#scu_enable_tracking').is(':checked');
                $(
                  '#scu_utm_source, ' +
                  '#scu_utm_medium, ' +
                  '#scu_utm_campaign, ' +
                  '#scu_utm_term, ' +
                  '#scu_utm_content, ' +
                  '#scu_pixel_id'
                ).closest('tr').toggle(enabled);
            }
            toggleTrackingFields();
            $('#scu_enable_tracking').on('change', toggleTrackingFields);
        });
        </script>
        <?php
    }
} );


