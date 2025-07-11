<?php
if ( ! defined( 'ABSPATH' ) ) exit;


add_action( 'wp_ajax_mx_scu_search_products', 'mx_scu_search_products' );
function mx_scu_search_products() {

    if ( ! mx_scu_current_user_has_access() ) {
        wp_send_json( [] );
    }

    $term = isset( $_REQUEST['q'] ) 
          ? sanitize_text_field( wp_unslash( $_REQUEST['q'] ) ) 
          : '';
    if ( ! $term ) {
        wp_send_json( [] );
    }

    $results   = [];
    $found_ids = [];

    $product_ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 10,
        'fields'         => 'ids',
    ]);
    foreach ( $product_ids as $pid ) {
        $prod = wc_get_product( $pid );
        if ( $prod && $prod->get_type() === 'simple' ) {
            $results[]   = [
                'id'   => $pid,
                'text' => sprintf( '%s (ID:%d)', $prod->get_name(), $pid ),
                'stock'=> $prod->managing_stock() ? $prod->get_stock_quantity() : -1,
            ];
            $found_ids[] = $pid;
        }
    }

    if ( $product_ids ) {
        $variations = wc_get_products([
            'type'   => 'variation',
            'parent' => $product_ids,
            'limit'  => -1,
            'status' => 'publish',
        ]);
        foreach ( $variations as $var ) {
            $vid   = $var->get_id();
            if ( ! in_array( $vid, $found_ids, true ) 
              && stripos( $var->get_name(), $term ) !== false ) {
                $results[]   = [
                    'id'   => $vid,
                    'text' => sprintf( '%s (ID:%d)', $var->get_name(), $vid ),
                    'stock'=> $var->managing_stock() ? $var->get_stock_quantity() : -1,
                ];
                $found_ids[] = $vid;
            }
        }
    }

    foreach ( wc_get_attribute_taxonomies() as $tax ) {
        $meta_key = sanitize_text_field( $tax->attribute_name );
        $var_ids  = get_posts([
            'post_type'      => 'product_variation',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'fields'         => 'ids',
            'meta_query'     => [[
                'key'     => $meta_key,
                'value'   => $term,
                'compare' => 'LIKE',
            ]],
        ]);
        foreach ( $var_ids as $vid ) {
            if ( ! in_array( $vid, $found_ids, true ) ) {
                $var = wc_get_product( $vid );
                if ( $var ) {
                    $results[]   = [
                        'id'   => $vid,
                        'text' => sprintf( '%s (ID:%d)', $var->get_name(), $vid ),
                        'stock'=> $var->managing_stock() ? $var->get_stock_quantity() : -1,
                    ];
                    $found_ids[] = $vid;
                }
            }
        }
    }

    wp_send_json( $results );
}

add_action( 'wp_ajax_mx_scu_search_coupons', 'mx_scu_search_coupons' );
function mx_scu_search_coupons() {
    if ( ! mx_scu_current_user_has_access() ) {
        wp_send_json( [] );
    }

    $term = isset( $_REQUEST['q'] )
          ? sanitize_text_field( wp_unslash( $_REQUEST['q'] ) )
          : '';
    if ( ! $term ) {
        wp_send_json( [] );
    }

    $results = [];
    $coupons = get_posts([
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 15,
        'fields'         => 'ids',
    ]);
    foreach ( $coupons as $cid ) {
        $code      = get_the_title( $cid );
        $results[] = [
            'id'    => $code,
            'label' => sprintf( '%s (ID:%d)', $code, $cid ),
        ];
    }

    wp_send_json( $results );
}
