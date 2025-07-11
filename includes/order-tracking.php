<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    $scu_id = WC()->session->get( 'mx_scu_link_id' );
    if ( $scu_id ) {
        $order->update_meta_data( '_mx_scu_link_id', $scu_id );
    }
}, 10, 2 );

add_action( 'woocommerce_order_status_completed', 'mx_scu_log_conversion', 20, 1 );
function mx_scu_log_conversion( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_status() !== 'completed' ) {
        return;
    }

    $scu_id = $order->get_meta( '_mx_scu_link_id' );
    if ( ! $scu_id ) {
        return;
    }

    // Prevent double counting
    if ( $order->get_meta( '_mx_scu_tracked' ) ) {
        return;
    }

    $total = $order->get_total();

    $count = (int) get_post_meta( $scu_id, 'mx_scu_order_count', true );
    update_post_meta( $scu_id, 'mx_scu_order_count', $count + 1 );

    $revenue = (float) get_post_meta( $scu_id, 'mx_scu_order_total', true );
    update_post_meta( $scu_id, 'mx_scu_order_total', $revenue + $total );

    $order->update_meta_data( '_mx_scu_tracked', 1 );
    $order->save();

    if ( function_exists( 'mx_scu_debug_enabled' ) && mx_scu_debug_enabled() ) {
        error_log( "[SCU] ✅ Tracked order #{$order_id} for SCU #{$scu_id}, revenue €{$total}" );
    }
}
