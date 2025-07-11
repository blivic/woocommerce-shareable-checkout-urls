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

