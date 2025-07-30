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

add_filter( 'manage_scu_link_posts_columns', 'mx_scu_add_send_column' );
function mx_scu_add_send_column( $columns ) {
    if ( 'yes' !== get_option( 'scu_enable_email', 'no' ) ) {
        return $columns;
    }

    $columns['send_email'] = __( 'Send', 'shareable-checkout-urls' );
    return $columns;
}
add_action( 'manage_scu_link_posts_custom_column', function( $column, $post_id ) {
    if ( 'send_email' !== $column ) {
        return;
    }
    if ( ! mx_scu_current_user_has_access() ) {
        echo '—';
        return;
    }
    printf(
        '<a href="#" class="mx-scu-send-email" data-id="%1$d" title="%2$s">'
      . '<span class="dashicons dashicons-email-alt"></span></a>',
        esc_attr( $post_id ),
        esc_attr__( 'Send SCU link by email', 'shareable-checkout-urls' )
    );
}, 10, 2 );

add_action( 'admin_footer-edit.php', 'mx_scu_email_modal_html' );
function mx_scu_email_modal_html() {
    if ( get_current_screen()->post_type !== 'scu_link' ) {
        return;
    }
	
    $default_subject = get_option(
        'scu_email_subject',
        __( 'Your {site_name} Quick-Checkout Link is Ready! 🚀', 'shareable-checkout-urls' )
    );
    $default_body = get_option(
	  'scu_email_body',
	  __(
		"Hi there,\n\n" .
		"Your personal checkout link on {site_name} is here:\n\n" .
		"{link}\n\n" .
		"What’s in your cart: {product_list}\n\n" .
		"Hurry – only {max_uses} use(s) left! Don’t miss out.\n\n" .
		"Thanks for choosing {site_name},\n" .
		"The {site_name} Team",
		'shareable-checkout-urls'
	  )
	);
    ?>
    <div id="mx-scu-email-modal" title="<?php esc_attr_e( 'Send Shareable Link', 'shareable-checkout-urls' ); ?>" style="display:none;">
      <p><?php esc_html_e( 'To (comma-separate multiple):', 'shareable-checkout-urls' ); ?></p>
      <textarea id="mx-scu-email-to" style="width:100%;height:4.5em;"></textarea>
      <p><?php esc_html_e( 'CC (optional, comma-separate):', 'shareable-checkout-urls' ); ?></p>
      <textarea id="mx-scu-email-cc" style="width:100%;height:2.5em;"></textarea>
      <p><?php esc_html_e( 'BCC (optional, comma-separate):', 'shareable-checkout-urls' ); ?></p>
      <textarea id="mx-scu-email-bcc" style="width:100%;height:2.5em;"></textarea>
      <div id="mx-scu-email-error" style="color:#b94a48;margin-top:4px;"></div>
	  <p><label><input type="checkbox" id="mx-scu-email-override-toggle" /><?php esc_html_e( 'Use custom Subject & Message?', 'shareable-checkout-urls' ); ?></label></p>
	  <div id="mx-scu-email-override-fields" style="display:none;">
		<p><?php esc_html_e( 'Subject override (leave blank to use default):', 'shareable-checkout-urls' ); ?></p>
		<input type="text" id="mx-scu-email-subject" style="width:100%;" data-default="<?php echo esc_attr( $default_subject ); ?>" />
		<p><?php esc_html_e( 'Message override (leave blank to use default):', 'shareable-checkout-urls' ); ?></p>
		<textarea id="mx-scu-email-body" style="width:100%;height:6em;" data-default="<?php echo esc_textarea( $default_body ); ?>"></textarea>
	  </div>
    </div>
    <?php
}
