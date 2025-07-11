<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', 'mx_scu_assets_loader' );
add_action( 'admin_head',            'mx_scu_assets_loader' );
function mx_scu_assets_loader() {
    global $pagenow;
    
    if ( ! in_array( $pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }

    $pt = '';
    if ( 'post-new.php' === $pagenow && ! empty( $_GET['post_type'] ) ) {
        $pt = sanitize_key( wp_unslash( $_GET['post_type'] ) );
    } elseif ( 'post.php' === $pagenow && ! empty( $_GET['post'] ) ) {
        $post = get_post( intval( $_GET['post'] ) );
        $pt   = $post ? $post->post_type : '';
    }
    if ( 'scu_link' !== $pt ) {
        return;
    }

    wp_enqueue_style(  'select2' );
    wp_enqueue_script( 'select2' );
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );

    if ( did_action( 'admin_enqueue_scripts' ) ) {
        wp_enqueue_script(
            'mx-scu-admin',
            plugins_url( 'includes/js/scu-admin.js', SCU_PLUGIN_FILE ),
            [ 'jquery', 'select2', 'jquery-ui-sortable', 'wp-color-picker' ],
            '1.1.0',
            true
        );
        wp_localize_script( 'mx-scu-admin', 'mx_scu_data', [
            'site_url'       => trailingslashit( home_url() ),
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'post_id'        => get_the_ID(),
            'endpoint_slug'  => mx_scu_get_endpoint_slug(),
            'qr_size'        => intval( get_post_meta( get_the_ID(), 'mx_scu_qr_size', true ) ?: 200 ),
            'qr_colorDark'   => get_post_meta( get_the_ID(), 'mx_scu_qr_colorDark', true ) ?: '#000000',
            'qr_colorLight'  => get_post_meta( get_the_ID(), 'mx_scu_qr_colorLight', true ) ?: '#ffffff',
        ] );

        wp_enqueue_script(
            'qrcode-svg',
            'https://cdn.jsdelivr.net/npm/qrcode-svg@1.1.0/dist/qrcode.min.js',
            [],
            '1.1.0',
            true
        );
        wp_enqueue_script(
            'qr-generator',
            plugins_url( 'includes/js/qr-generator.js', SCU_PLUGIN_FILE ),
            [ 'qrcode-svg' ],
            '1.2.0',
            true
        );
    }

    if ( did_action( 'admin_head' ) ) {
        $css_url = plugins_url( 'includes/css/scu-admin.css', SCU_PLUGIN_FILE );
        echo '<link rel="stylesheet" id="mx-scu-admin-css" href="'
           . esc_url( $css_url ) . '?ver=1.1.0" type="text/css" media="all" />' . "\n";
    }
}
