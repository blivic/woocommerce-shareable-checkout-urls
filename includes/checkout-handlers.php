<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'query_vars', 'mx_scu_query_vars' );
function mx_scu_query_vars( $vars ) {
    $vars[] = 'scu';
    return $vars;
}

add_action( 'init', 'mx_scu_add_rewrite_rule' );
function mx_scu_add_rewrite_rule() {
    $slug = mx_scu_get_endpoint_slug();
    add_rewrite_rule(
        '^' . preg_quote( $slug, '/' ) . '/?$',
        'index.php?scu=1',
        'top'
    );
}

add_action( 'template_redirect', 'mx_scu_handle_endpoint' );
function mx_scu_handle_endpoint() {
    if ( intval( get_query_var( 'scu' ) ) !== 1 ) {
        return;
    }

    if ( empty( $_GET['products'] ) ) {
        wp_die( __( 'No products specified.', 'shareable-checkout-urls' ) );
    }

    WC()->cart->empty_cart( true );

    $raw_string = sanitize_text_field( wp_unslash( $_GET['products'] ) );
    $validated  = mx_scu_validate_products( $raw_string );
    foreach ( $validated as $entry ) {
        if ( $entry['valid'] ) {
            WC()->cart->add_to_cart( $entry['id'], $entry['qty'] );
        }
    }

    $scu_id    = 0;
    $full_url  = home_url( $_SERVER['REQUEST_URI'] );
    $posts     = get_posts([
        'post_type'   => 'scu_link',
        'post_status' => [ 'publish','draft' ],
        'numberposts' => 1,
        'meta_query'  => [[
            'key'     => 'mx_scu_url',
            'value'   => $full_url,
            'compare' => 'LIKE',
        ]],
    ]);
    if ( $posts ) {
        $scu_id = (int) $posts[0]->ID;


        $max = (int) get_post_meta( $scu_id, 'mx_scu_max_uses', true );
        $cnt = (int) get_post_meta( $scu_id, 'mx_scu_uses',      true );
        if ( $max && $cnt >= $max ) {
            wp_die( __( 'This checkout link has expired.', 'shareable-checkout-urls' ) );
        }
        update_post_meta( $scu_id, 'mx_scu_uses', $cnt + 1 );
        if ( $max && $cnt + 1 >= $max ) {
            wp_update_post([ 'ID'=>$scu_id,'post_status'=>'draft' ]);
        }

        // store in session for promo + order tracking
        WC()->session->set( 'mx_scu_link_id',     $scu_id );
        WC()->session->set( 'mx_scu_cart_scu_id', $scu_id );
		
		// Webhook
		if ( $scu_id ) {
			$webhooks = mx_scu_get_webhook_urls();
			if ( ! empty( $webhooks ) ) {
				// Build a payload
				$payload = [
					'scu_link_id' => $scu_id,
					'products'    => explode( ',', sanitize_text_field( wp_unslash( $_GET['products'] ) ) ),
					'coupon'      => isset( $_GET['coupon'] ) ? sanitize_text_field( wp_unslash( $_GET['coupon'] ) ) : '',
					'timestamp'   => gmdate( 'c' ),
					'user_ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
				];

				foreach ( $webhooks as $url ) {
					wp_remote_post( $url, [
						'timeout'   => 2,
						'body'      => wp_json_encode( $payload ),
						'headers'   => [ 'Content-Type' => 'application/json' ],
						'blocking'  => false,
					] );
				}
			}
		}
    }

    if ( ! empty( $_GET['coupon'] ) ) {
        WC()->cart->apply_coupon( sanitize_text_field( wp_unslash( $_GET['coupon'] ) ) );
    }

    $checkout_url = wc_get_checkout_url();

    $mode      = get_post_meta( $scu_id, 'mx_scu_tracking_mode', true ) ?: 'global';
    $global_on = get_option( 'scu_enable_tracking', 'no' ) === 'yes';

    if ( ( $mode === 'custom' ) || ( $mode === 'global' && $global_on ) ) {

        $utm = [];
        foreach ( [ 'source','medium','campaign','term','content' ] as $f ) {
            if ( $mode === 'custom' ) {
                $v = get_post_meta( $scu_id, "mx_scu_utm_{$f}", true );
            } else {
                $v = get_option( "scu_utm_{$f}", '' );
            }
            if ( $v ) {
                $utm[ "utm_{$f}" ] = sanitize_text_field( $v );
            }
        }

        if ( ! empty( $utm ) ) {
            $checkout_url = add_query_arg( $utm, $checkout_url );
        }


        if ( $mode === 'custom' ) {
            $pixel = get_post_meta( $scu_id, 'mx_scu_pixel_id', true );
        } else {
            $pixel = get_option( 'scu_pixel_id', '' );
        }
        if ( $pixel ) {
            WC()->session->set( 'mx_scu_pixel_id', sanitize_text_field( $pixel ) );
        }
    }

    wp_safe_redirect( $checkout_url );
    exit;
}

function mx_scu_validate_products( $product_string ) {
    $use_cache = 'yes' === get_option( 'scu_enable_cache', 'no' );
    $cache_key = 'checkout_link_products_' . md5( $product_string );

    if ( $use_cache ) {
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            if ( mx_scu_debug_enabled() ) {
                error_log( "[SCU] Cache HIT: {$cache_key}" );
            }
            return $cached;
        }
        if ( mx_scu_debug_enabled() ) {
            error_log( "[SCU] Cache MISS: {$cache_key}" );
        }
    }

    $products = [];
    $pairs    = explode( ',', $product_string );
    foreach ( $pairs as $pair ) {
        list( $id, $qty ) = explode( ':', $pair . ':1' );
        $id  = absint( $id );
        $qty = absint( $qty );

        if ( ! $id ) {
            if ( mx_scu_debug_enabled() ) {
                error_log( "[SCU] Skipped: invalid pair '{$pair}'" );
            }
            continue;
        }

        $prod     = wc_get_product( $id );
        $is_valid = $prod && $prod->is_purchasable();
        $name     = $prod ? $prod->get_name() : '(not found)';

        if ( mx_scu_debug_enabled() ) {
            $mark = $is_valid ? '✅' : '❌';
            error_log( "[SCU] {$mark} Product ID {$id} × {$qty} – {$name}" );
        }

        $products[] = [
            'id'    => $id,
            'qty'   => $qty ?: 1,
            'valid' => $is_valid,
        ];
    }

    if ( $use_cache ) {
        set_transient( $cache_key, $products, HOUR_IN_SECONDS );
    }

    return $products;
}

add_action( 'save_post_product',               'mx_scu_clear_product_cache' );
add_action( 'deleted_post',                    'mx_scu_clear_product_cache' );
add_action( 'woocommerce_product_set_stock_status', 'mx_scu_clear_product_cache' );

function mx_scu_clear_product_cache( $post_id ) {
    global $wpdb;

    $count1 = $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_checkout_link_products_%'"
    );
    $count2 = $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_checkout_link_products_%'"
    );

    $total = intval( $count1 ) + intval( $count2 );

    if ( function_exists( 'mx_scu_debug_enabled' ) && mx_scu_debug_enabled() ) {
        error_log( "[SCU] Cleared product validation cache: {$total} transients removed." );
    }

    return $total;
}

add_action( 'admin_init', 'mx_scu_maybe_clear_cache' );
function mx_scu_maybe_clear_cache() {
    if ( isset( $_GET['scu_clear_cache'] ) && current_user_can( 'manage_woocommerce' ) ) {
        $count = mx_scu_clear_product_cache( 0 );
        set_transient( 'mx_scu_cache_cleared_notice', $count, 30 );
        wp_safe_redirect( remove_query_arg( 'scu_clear_cache' ) );
        exit;
    }
}

add_action( 'admin_notices', 'mx_scu_show_clear_cache_notice' );
function mx_scu_show_clear_cache_notice() {
    $count = get_transient( 'mx_scu_cache_cleared_notice' );
    if ( $count !== false ) {
        delete_transient( 'mx_scu_cache_cleared_notice' );
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                esc_html__( 'SCU product validation cache cleared. %d transients removed.', 'shareable-checkout-urls' ),
                intval( $count )
            )
        );
    }
}

add_action( 'woocommerce_before_checkout_form', 'mx_scu_show_promo_message', 5 );
function mx_scu_show_promo_message() {
    if ( ! is_checkout() || WC()->cart->is_empty() ) {
        return;
    }

    $scu_id = WC()->session->get( 'mx_scu_link_id' );
    if ( ! $scu_id ) {
        return;
    }

    $message = get_post_meta( $scu_id, 'mx_scu_promo_message', true );
    $mode    = get_post_meta( $scu_id, 'mx_scu_promo_display_mode', true ) ?: 'notice';
    if ( ! $message ) {
        return;
    }

    if ( $mode === 'custom' ) {
        echo '<div class="mx-scu-promo-custom" style="margin-bottom:20px;border:1px solid #e1e1e1;background:#f9f9f9;padding:16px;border-radius:4px;">'
             . wp_kses_post( wpautop( $message ) )
             . '</div>';
    } else {
        wc_add_notice( wp_kses_post( $message ), 'notice' );
    }
}

add_action( 'wp_print_footer_scripts', 'mx_scu_reposition_promo_script', 100 );
function mx_scu_reposition_promo_script() {
    if ( ! is_checkout() || is_admin() ) {
        return;
    }
    $scu_id = WC()->session->get( 'mx_scu_link_id' );
    if ( ! $scu_id ) {
        return;
    }
    $message = get_post_meta( $scu_id, 'mx_scu_promo_message', true );
    $mode    = get_post_meta( $scu_id, 'mx_scu_promo_display_mode', true );
    if ( ! $message || $mode !== 'custom' ) {
        return;
    }
    ?>
    <script>
    jQuery(function($){
        const promo   = $('.mx-scu-promo-custom');
        const wrapper = $('.woocommerce-notices-wrapper').first();
        if ( promo.length && wrapper.length ) {
            wrapper.before(promo);
        } else if ( promo.length ) {
            $('.woocommerce').prepend(promo);
        }
    });
    </script>
    <?php
}

add_action( 'wp_footer', function() {
    if ( ! is_checkout() ) {
        return;
    }
    $pixel = WC()->session->get( 'mx_scu_pixel_id' );
    if ( ! $pixel ) {
        return;
    }
    ?>
    <!-- Meta Pixel Code injected by Shareable Checkout URLs -->
    <script>
    !function(f,b,e,v,n,t,s){
        if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s);
    }(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo esc_js( $pixel ); ?>');
    fbq('track', 'PageView');
    </script>
    <?php
} );