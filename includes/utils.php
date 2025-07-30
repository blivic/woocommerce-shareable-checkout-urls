<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mx_scu_get_endpoint_slug() {
    $opt = get_option( 'scu_endpoint_slug', '' );
    if ( $opt ) {
        return sanitize_title( $opt );
    }
    return 'checkout-link';
}

function mx_scu_current_user_has_access() {
    $min_role = get_option( 'scu_minimum_role', 'shop_manager' );
    $roles_hierarchy = [
        'administrator' => 7,
        'shop_manager'  => 6,
        'editor'        => 5,
        'author'        => 4,
        'contributor'   => 3,
    ];
    $min_level = $roles_hierarchy[ $min_role ] ?? 6;
    if ( ! is_user_logged_in() ) {
        return false;
    }
    $user = wp_get_current_user();
    foreach ( $user->roles as $role ) {
        if ( isset( $roles_hierarchy[ $role ] ) && $roles_hierarchy[ $role ] >= $min_level ) {
            return true;
        }
    }
    return false;
}

function mx_scu_debug_enabled() {
    return 'yes' === get_option( 'scu_debug_mode', 'no' );
}

add_action( 'admin_menu', function() {
    if ( ! mx_scu_current_user_has_access() ) {
        remove_menu_page( 'edit.php?post_type=scu_link' );
    }
}, 99 );

function mx_scu_get_webhook_urls() {
    $raw = get_option( 'scu_webhook_urls', '' );
    if ( ! $raw ) {
        return [];
    }
    $lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
    return array_filter( array_map( 'trim', $lines ) );
}

function mx_scu_send_email_to( array $to, array $cc, array $bcc, $id, $subject_override = '', $body_override = '' ) {
    // 0) Fetch the generated URL
    $url = get_post_meta( $id, 'mx_scu_url', true );
    if ( ! $url ) {
        return false;
    }

    $site_name   = get_bloginfo( 'name' );
    $data        = get_post_meta( $id, 'mx_scu_data', true );
    $products    = $data['products'] ?? [];
    $coupon_code = $data['coupon']   ?? '';
    $max_uses    = get_post_meta( $id, 'mx_scu_max_uses', true ) ?: 'unlimited';

    $product_list = [];
    foreach ( $products as $p ) {
        $title = html_entity_decode(
            get_the_title( $p['id'] ),
            ENT_QUOTES | ENT_HTML5,
            get_bloginfo( 'charset' )
        );
        $product_list[] = "{$title} ×{$p['qty']}";
    }
    $product_list = implode( ', ', $product_list );

    $default_subject = __( 'Your {site_name} Quick-Checkout Link is Ready! 🚀', 'shareable-checkout-urls' );
    $raw_subject    = trim( $subject_override )
        ? $subject_override
        : get_option( 'scu_email_subject', '' );
    if ( ! trim( $raw_subject ) ) {
        $raw_subject = $default_subject;
    }

    $default_body =
        "Hi there,\n\n" .
        "Your personal checkout link on {site_name} is here:\n\n" .
        "{link}\n\n" .
        "What’s in your cart: {product_list}\n\n" .
        "Coupon: {coupon_code}\n\n" .
        "Hurry – only {max_uses} use(s) left! Don’t miss out.\n\n" .
        "Thanks for choosing {site_name},\n" .
        "The {site_name} Team";
    $raw_body = trim( $body_override )
        ? $body_override
        : get_option( 'scu_email_body', '' );
    if ( ! trim( $raw_body ) ) {
        $raw_body = $default_body;
    }

    $replace = [
        '{link}'         => esc_url( $url ),
        '{site_name}'    => $site_name,
        '{product_list}' => $product_list,
        '{max_uses}'     => $max_uses,
        '{coupon_code}'  => $coupon_code,
    ];
    $subject = strtr( $raw_subject, $replace );
    $body    = strtr( $raw_body,    $replace );

    $body = wp_kses_post( $body );
    $body = wpautop( $body );

    $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
    if ( $cc )  {
        $headers[] = 'Cc: '  . implode( ',', $cc );
    }
    if ( $bcc ) {
        $headers[] = 'Bcc: ' . implode( ',', $bcc );
    }

    $mailer        = WC()->mailer();
    $email_heading = $subject;                     
    $wrapped_body  = $mailer->wrap_message( $email_heading, $body );

    return $mailer->send(
        $to,              
        $subject,        
        $wrapped_body,     
        $headers,         
        []              
    );
}
