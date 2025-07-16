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
		$variations = wc_get_products( [
			'type'   => 'variation',
			'parent' => $product_ids,
			'limit'  => -1,
			'status' => 'publish',
		] );

		foreach ( $variations as $var ) {
			$vid = $var->get_id();

			if ( in_array( $vid, $found_ids, true ) ) {
				continue;
			}

			$parent    = wc_get_product( $var->get_parent_id() );
			$attr_bits = [];

			foreach ( $parent->get_attributes() as $attribute ) {
				if ( ! $attribute->get_variation() ) {
					continue;
				}

				$slug  = $attribute->get_name();
				$label = wc_attribute_label( $slug );

				$raw   = $var->get_attribute( $slug );

				$value = $raw !== '' ? $raw : "Any {$label}";

				$attr_bits[] = "{$label}: {$value}";
			}

			$name = $parent->get_name() . ' (' . implode( ', ', $attr_bits ) . ')';

			if ( stripos( $name, $term ) !== false ) {
				$results[]   = [
					'id'    => $vid,
					'text'  => sprintf( '%s (ID:%d)', $name, $vid ),
					'stock' => $var->managing_stock() ? $var->get_stock_quantity() : -1,
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

add_action( 'wp_ajax_mx_scu_send_email', 'mx_scu_ajax_send_email' );
function mx_scu_ajax_send_email() {
    check_ajax_referer( 'mx_scu_email', 'nonce' );

    if ( ! mx_scu_current_user_has_access() ) {
        wp_send_json_error( __( 'Permission denied', 'shareable-checkout-urls' ) );
    }

    $id       = intval( $_POST['id']    ?? 0 );
    $to_raw   = wp_unslash( $_POST['to']   ?? '' );
    $cc_raw   = wp_unslash( $_POST['cc']   ?? '' );
    $bcc_raw  = wp_unslash( $_POST['bcc']  ?? '' );

    $to  = array_filter( array_map( 'trim', explode( ',', $to_raw ) ) );
    $cc  = array_filter( array_map( 'trim', explode( ',', $cc_raw ) ) );
    $bcc = array_filter( array_map( 'trim', explode( ',', $bcc_raw ) ) );

    if ( $id <= 0 || empty( $to ) ) {
        wp_send_json_error( __( 'Invalid parameters', 'shareable-checkout-urls' ) );
    }

    $all     = array_merge( $to, $cc, $bcc );
    $invalid = array_filter( $all, function( $addr ) {
        return ! is_email( $addr );
    } );
    if ( ! empty( $invalid ) ) {
        wp_send_json_error( sprintf(
            __( 'Invalid email(s): %s', 'shareable-checkout-urls' ),
            implode( ', ', $invalid )
        ) );
    }

    $subject_override = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
    $body_override    = wp_kses_post(      wp_unslash( $_POST['body']    ?? '' ) );

    $sent = mx_scu_send_email_to(
        $to,
        $cc,
        $bcc,
        $id,
        $subject_override,
        $body_override
    );

    if ( ! $sent ) {
        wp_send_json_error( __( 'Failed to send email; check your mail setup.', 'shareable-checkout-urls' ) );
    }

    if ( 'yes' === get_option( 'scu_enable_email_history', 'no' ) ) {
        $history = get_post_meta( $id, 'mx_scu_email_history', true );
        if ( ! is_array( $history ) ) {
            $history = [];
        }
        $history[] = [
            'to'        => $to,
            'cc'        => $cc,
            'bcc'       => $bcc,
            'subject'   => $subject_override,
            'body'      => $body_override,
            'timestamp' => current_time( 'mysql' ),
        ];
        update_post_meta( $id, 'mx_scu_email_history', $history );
    }

    wp_send_json_success();
}
