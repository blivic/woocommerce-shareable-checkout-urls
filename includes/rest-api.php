<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ------------------------------------------------------------------------
 * REST API: /wp-json/scu/v1/links
 * ------------------------------------------------------------------------
 */
add_action( 'rest_api_init', 'mx_scu_register_rest_routes' );
function mx_scu_register_rest_routes() {
    $namespace = 'scu/v1';

    register_rest_route( $namespace, '/links', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'mx_scu_rest_list_links',
            'permission_callback' => 'mx_scu_current_user_has_access',
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'mx_scu_rest_create_link',
            'permission_callback' => 'mx_scu_current_user_has_access',
            'args'                => [
                'products'            => [ 'required' => true ],
                'coupon'              => [],
                'promo_message'       => [],
                'max_uses'            => [ 'sanitize_callback' => 'absint' ],
                'promo_display_mode'  => [ 'sanitize_callback' => 'sanitize_key' ],
                'title'               => [ 'required' => false ],
            ],
        ],
    ] );

    register_rest_route( $namespace, '/links/(?P<id>\d+)', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'mx_scu_rest_get_link',
            'permission_callback' => 'mx_scu_current_user_has_access',
            'args'                => [
                'id' => [
                    'validate_callback' => function( $value, $request, $param ) {
                        return is_numeric( $value );
                    },
                ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'mx_scu_rest_update_link',
            'permission_callback' => 'mx_scu_current_user_has_access',
            'args'                => [
                'id'                   => [
                    'validate_callback' => function( $value, $request, $param ) {
                        return is_numeric( $value );
                    },
                ],
                'products'             => [],
                'coupon'               => [],
                'promo_message'        => [],
                'max_uses'             => [ 'sanitize_callback' => 'absint' ],
                'promo_display_mode'   => [ 'sanitize_callback' => 'sanitize_key' ],
                'title'                => [],
            ],
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'mx_scu_rest_delete_link',
            'permission_callback' => 'mx_scu_current_user_has_access',
            'args'                => [
                'id' => [
                    'validate_callback' => function( $value, $request, $param ) {
                        return is_numeric( $value );
                    },
                ],
            ],
        ],
    ] );
}


/**
 * GET /links
 */
function mx_scu_rest_list_links( WP_REST_Request $req ) {
    $posts = get_posts( [
        'post_type'   => 'scu_link',
        'post_status' => [ 'publish', 'draft' ],
        'numberposts' => -1,
    ] );

    $data = array_map( 'mx_scu_rest_prepare_link', $posts );
    return rest_ensure_response( $data );
}

/**
 * GET /links/{id}
 */
function mx_scu_rest_get_link( WP_REST_Request $req ) {
    $id   = (int) $req['id'];
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'scu_link' ) {
        return new WP_Error( 'scu_not_found', __( 'Link not found', 'shareable-checkout-urls' ), [ 'status' => 404 ] );
    }
    return rest_ensure_response( mx_scu_rest_prepare_link( $post ) );
}

/**
 * POST /links
 */
function mx_scu_rest_create_link( WP_REST_Request $req ) {
    $body = $req->get_json_params();

    $new_id = wp_insert_post( [
        'post_type'   => 'scu_link',
        'post_status' => 'publish',
        'post_title'  => sanitize_text_field( $body['title'] ?? 'SCU Link' ),
    ], true );

    if ( is_wp_error( $new_id ) ) {
        return $new_id;
    }

    mx_scu_rest_save_meta( $new_id, $body );

    $post = get_post( $new_id );
    return rest_ensure_response( mx_scu_rest_prepare_link( $post ) );
}

/**
 * PUT /links/{id}
 */
function mx_scu_rest_update_link( WP_REST_Request $req ) {
    $id   = (int) $req['id'];
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'scu_link' ) {
        return new WP_Error( 'scu_not_found', __( 'Link not found', 'shareable-checkout-urls' ), [ 'status' => 404 ] );
    }

    $body = $req->get_json_params();

    if ( isset( $body['title'] ) ) {
        wp_update_post( [
            'ID'         => $id,
            'post_title' => sanitize_text_field( $body['title'] ),
        ] );
    }

    mx_scu_rest_save_meta( $id, $body );

    return rest_ensure_response( mx_scu_rest_prepare_link( get_post( $id ) ) );
}

/**
 * DELETE /links/{id}
 */
function mx_scu_rest_delete_link( WP_REST_Request $req ) {
    $id   = (int) $req['id'];
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'scu_link' ) {
        return new WP_Error( 'scu_not_found', __( 'Link not found', 'shareable-checkout-urls' ), [ 'status' => 404 ] );
    }
    wp_delete_post( $id, true );
    return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
}

/**
 * Helper: prepare a single link’s data for REST output
 */
function mx_scu_rest_prepare_link( WP_Post $post ) {
    $meta = get_post_meta( $post->ID );
    return [
        'id'                 => $post->ID,
        'title'              => $post->post_title,
        'status'             => $post->post_status,
        'products'           => $meta['mx_scu_data'][0]['products'] ?? [],
        'coupon'             => $meta['mx_scu_data'][0]['coupon']   ?? '',
        'promo_message'      => $meta['mx_scu_promo_message'][0]     ?? '',
        'promo_display_mode' => $meta['mx_scu_promo_display_mode'][0]?? 'notice',
        'max_uses'           => (int) ( $meta['mx_scu_max_uses'][0] ?? 0 ),
        'uses'               => (int) ( $meta['mx_scu_uses'][0]      ?? 0 ),
        'url'                => get_post_meta( $post->ID, 'mx_scu_url', true ),
    ];
}

/**
 * Helper: save SCU meta after create/update
 */
function mx_scu_rest_save_meta( $post_id, array $body ) {
    $existing = get_post_meta( $post_id, 'mx_scu_data', true );
    if ( ! is_array( $existing ) ) {
        $existing = [
            'products' => [],
            'coupon'   => '',
        ];
    }

    $products = array_key_exists( 'products', $body )
        ? $body['products']
        : $existing['products'];

    $coupon = array_key_exists( 'coupon', $body )
        ? sanitize_text_field( $body['coupon'] )
        : $existing['coupon'];

    update_post_meta( $post_id, 'mx_scu_data', [
        'products' => $products,
        'coupon'   => $coupon,
    ] );

    if ( array_key_exists( 'promo_message', $body ) ) {
        update_post_meta( $post_id, 'mx_scu_promo_message', wp_kses_post( $body['promo_message'] ) );
    }
    if ( array_key_exists( 'promo_display_mode', $body ) ) {
        update_post_meta( $post_id, 'mx_scu_promo_display_mode', sanitize_key( $body['promo_display_mode'] ) );
    }
    if ( array_key_exists( 'max_uses', $body ) ) {
        update_post_meta( $post_id, 'mx_scu_max_uses', absint( $body['max_uses'] ) );
    }

    if ( array_key_exists( 'products', $body ) || array_key_exists( 'coupon', $body ) ) {
        $parts = array_map( function( $p ){ return $p['id'] . ':' . $p['qty']; }, $products );
        $slug  = mx_scu_get_endpoint_slug();
        $url   = trailingslashit( home_url() ) . $slug . '/?products=' . implode( ',', $parts );
        if ( $coupon ) {
            $url .= '&coupon=' . rawurlencode( $coupon );
        }
        update_post_meta( $post_id, 'mx_scu_url', $url );
    }
}
