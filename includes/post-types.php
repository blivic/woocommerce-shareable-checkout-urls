<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'mx_scu_register_cpt' );
function mx_scu_register_cpt() {
    $labels = [
        'name'               => __( 'Shareable Checkout URLs', 'shareable-checkout-urls' ),
        'singular_name'      => __( 'Shareable Checkout URL',  'shareable-checkout-urls' ),
        'menu_name'          => __( 'Shareable checkout URLs', 'shareable-checkout-urls' ),
        'add_new_item'       => __( 'Add New Shareable URL',  'shareable-checkout-urls' ),
        'edit_item'          => __( 'Edit Shareable URL',     'shareable-checkout-urls' ),
        'all_items'          => __( 'Shareable URLs',         'shareable-checkout-urls' ),
    ];

    register_post_type( 'scu_link', [
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=product',
        'show_in_nav_menus'  => false,
        'exclude_from_search'=> true,
        'has_archive'        => false,
        'rewrite'            => false,
        'query_var'          => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'supports'           => [ 'title' ],
        'show_in_rest'       => false,
        'menu_icon'          => 'dashicons-admin-links',
        'menu_position'      => 56,
    ] );
}

add_filter( 'manage_scu_link_posts_columns', 'mx_scu_columns' );
function mx_scu_columns( $cols ) {
    return [
        'cb'         => $cols['cb'],
        'title'      => __( 'Name',               'shareable-checkout-urls' ),
        'products'   => __( 'Products × Qty',     'shareable-checkout-urls' ),
        'coupon'     => __( 'Coupon',             'shareable-checkout-urls' ),
        'uses'       => __( 'Usage',              'shareable-checkout-urls' ),
        'orders'     => __( 'Orders',             'shareable-checkout-urls' ),
        'conversion' => __( 'Conversion Rate',    'shareable-checkout-urls' ),
        'revenue'    => __( 'Revenue',            'shareable-checkout-urls' ),
        'url'        => __( 'Checkout URL',       'shareable-checkout-urls' ),
    ];
}

add_action( 'manage_scu_link_posts_custom_column', 'mx_scu_custom_columns', 10, 2 );
function mx_scu_custom_columns( $column, $post_id ) {
    $data = get_post_meta( $post_id, 'mx_scu_data', true );
    switch ( $column ) {
        case 'products':
            if ( ! empty( $data['products'] ) ) {
                $list = array_map(
                    function( $p ) { return $p['id'] . '×' . $p['qty']; },
                    $data['products']
                );
                echo esc_html( implode( ', ', $list ) );
            }
            break;
        case 'coupon':
            echo ! empty( $data['coupon'] ) ? esc_html( $data['coupon'] ) : '—';
            break;
        case 'uses':
            $count    = (int) get_post_meta( $post_id, 'mx_scu_uses',     true );
            $max      = (int) get_post_meta( $post_id, 'mx_scu_max_uses', true );
            echo esc_html( $max > 0 ? "{$count} / {$max}" : $count );
            break;
        case 'orders':
            echo intval( get_post_meta( $post_id, 'mx_scu_order_count', true ) );
            break;
        case 'conversion':
            $uses   = max( 1, (int) get_post_meta( $post_id, 'mx_scu_uses',        true ) );
            $orders = (int) get_post_meta( $post_id, 'mx_scu_order_count', true );
            if ( $uses === 0 ) {
                echo '—';
            } else {
                echo esc_html( round( ( $orders / $uses ) * 100, 1 ) . '%' );
            }
            break;
        case 'revenue':
            echo wc_price( get_post_meta( $post_id, 'mx_scu_order_total', true ) );
            break;
        case 'url':
            $url = get_post_meta( $post_id, 'mx_scu_url', true );
            if ( $url ) {
                printf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url( $url ),
                    esc_html__( 'View', 'shareable-checkout-urls' )
                );
            }
            break;
    }
}

add_filter( 'manage_edit-scu_link_sortable_columns', function( $columns ) {
    $columns['uses']       = 'mx_scu_uses';
    $columns['orders']     = 'mx_scu_order_count';
    $columns['revenue']    = 'mx_scu_order_total';
    $columns['conversion'] = 'mx_scu_conversion_rate';
    return $columns;
} );

add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'scu_link' ) {
        return;
    }

    $orderby = $query->get( 'orderby' );
    if ( $orderby === 'mx_scu_conversion_rate' ) {
        $query->set( 'meta_query', [
            'relation'      => 'AND',
            'uses_clause'   => [ 'key' => 'mx_scu_uses',        'type' => 'NUMERIC' ],
            'orders_clause' => [ 'key' => 'mx_scu_order_count', 'type' => 'NUMERIC' ],
        ] );
        $query->set( 'orderby', [
            'orders_clause' => 'DESC',
            'uses_clause'   => 'ASC',
        ] );
    } elseif ( in_array( $orderby, [ 'mx_scu_uses', 'mx_scu_order_count', 'mx_scu_order_total' ], true ) ) {
        $query->set( 'meta_key', $orderby );
        $query->set( 'orderby', 'meta_value_num' );
    }
} );
