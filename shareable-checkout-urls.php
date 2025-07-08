<?php
/**
 * Plugin Name:       Shareable Checkout URLs
 * Description:       Build, save & edit shareable checkout URLs (products + coupon) under Products.
 * Version:           1.1.0
 * Author:            Media X
 * Author URI:        https://media-x.hr
 * Text Domain:       shareable-checkout-urls
 * Requires at least: WordPress 5.5
 * Tested up to:      WordPress 6.8
 * WC requires at least: WooCommerce 10.0
 * WC tested up to:      WooCommerce 10.0
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
} );

register_activation_hook( __FILE__, 'mx_activation_check' );
function mx_activation_check() {
    if ( ! class_exists( 'WooCommerce' ) || version_compare( WC()->version, '10.0', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'Shareable Checkout URLs requires WooCommerce 10.0 or higher.', 'shareable-checkout-urls' ),
            esc_html__( 'Plugin Activation Error', 'shareable-checkout-urls' ),
            [ 'back_link' => true ]
        );
    }
}

add_action( 'admin_notices', 'mx_wc_version_notice' );
function mx_wc_version_notice() {
    if ( is_admin() && ( ! class_exists( 'WooCommerce' ) || version_compare( WC()->version, '10.0', '<' ) ) ) {
        echo '<div class="error"><p>'
            . esc_html__( 'Shareable Checkout URLs requires WooCommerce 10.0 or higher. Please update WooCommerce.', 'shareable-checkout-urls' )
            . '</p></div>';
    }
}

add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    'mx_scu_add_settings_link'
);
function mx_scu_add_settings_link( $links ) {
    $settings_url = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=scu_endpoint' );
    $settings_link = sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url( $settings_url ),
        __( 'Settings', 'shareable-checkout-urls' )
    );
    array_unshift( $links, $settings_link );
    return $links;
}

add_action( 'init', 'mx_scu_register_cpt' );
function mx_scu_register_cpt() {
    $labels = [
        'name'               => __( 'Shareable Checkout URLs', 'shareable-checkout-urls' ),
        'singular_name'      => __( 'Shareable Checkout URL',  'shareable-checkout-urls' ),
        'menu_name'          => __( 'Shareable checkout URLs', 'shareable-checkout-urls' ),
        'add_new_item'       => __( 'Add New Shareable URL',  'shareable-checkout-urls' ),
        'edit_item'          => __( 'Edit Shareable URL',     'shareable-checkout-urls' ),
        'all_items'          => __( 'Shareable URLs',     'shareable-checkout-urls' ),
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

add_action( 'add_meta_boxes', 'mx_scu_add_meta_boxes' );
function mx_scu_add_meta_boxes() {
    remove_post_type_support( 'scu_link', 'editor' );
    add_meta_box(
        'mx-scu-builder',
        __( 'Build Shareable URL', 'shareable-checkout-urls' ),
        'mx_scu_builder_meta_box',
        'scu_link',
        'normal',
        'high'
    );
}

function mx_scu_builder_meta_box( $post ) {
    wp_nonce_field( 'mx_scu_save', 'mx_scu_nonce' );

    $data   = get_post_meta( $post->ID, 'mx_scu_data', true );
    $items  = isset( $data['products'] ) ? $data['products'] : [];
    $coupon = isset( $data['coupon'] )   ? $data['coupon']   : '';
	$qr_size       = intval( get_post_meta( $post->ID, 'mx_scu_qr_size',      true ) ) ?: 200;
    $qr_colorDark  = get_post_meta( $post->ID, 'mx_scu_qr_colorDark', true ) ?: '#000000';
    $qr_colorLight = get_post_meta( $post->ID, 'mx_scu_qr_colorLight', true ) ?: '#ffffff';

    $render_row = function( $item_id = '', $qty = 1 ) {
        $title = $item_id
            ? get_the_title( $item_id ) . ' (ID:' . $item_id . ')'
            : '';
        ?>
        <div class="mx-scu-product-row">
            <span class="mx-scu-drag-handle dashicons dashicons-move" style="cursor:move; margin-right:8px;"></span>
            <input
                type="text"
                class="mx-scu-product-search"
                placeholder="<?php esc_attr_e( 'Search product…', 'shareable-checkout-urls' ); ?>"
                value="<?php echo esc_attr( $title ); ?>"
            />
            <input
                type="hidden"
                name="mx_scu_products_ids[]"
                class="mx-scu-product-id"
                value="<?php echo esc_attr( $item_id ); ?>"
            />
            <input
                type="number"
                class="mx-scu-product-qty"
                name="mx_scu_products_qtys[]"
                value="<?php echo esc_attr( $qty ); ?>"
                min="1"
                placeholder="<?php esc_attr_e( 'Qty', 'shareable-checkout-urls' ); ?>"
            />
            <button class="button mx-remove-product">
                <?php esc_html_e( 'Remove', 'shareable-checkout-urls' ); ?>
            </button>
        </div>
        <?php
    };

    ?>
    <div id="mx-scu-builder">
        <div id="mx-scu-products">
            <?php
            if ( ! empty( $items ) ) {
                foreach ( $items as $item ) {
                    $id  = isset( $item['id'] )  ? intval( $item['id'] )  : '';
                    $qty = isset( $item['qty'] ) ? intval( $item['qty'] ) : 1;
                    $render_row( $id, $qty );
                }
            } else {
                $render_row();
            }
            ?>
        </div>

        <p>
            <button class="button" id="mx-scu-add-product">
                <?php esc_html_e( 'Add another product', 'shareable-checkout-urls' ); ?>
            </button>
        </p>

        <p>
		  <label for="mx-scu-coupon"><?php esc_html_e( 'Coupon (optional)', 'shareable-checkout-urls' ); ?></label>
		  <input
			type="text"
			id="mx-scu-coupon"
			name="mx_scu_coupon"
			value="<?php echo esc_attr( $coupon ); ?>"
			placeholder="<?php esc_attr_e( 'Search coupon…', 'shareable-checkout-urls' ); ?>"
			style="width:300px;"
		  />
		</p>


        <p>
            <label><?php esc_html_e( 'Generated URL', 'shareable-checkout-urls' ); ?></label>
            <pre class="mx-scu-generated-url"><code id="mx-scu-generated-text"></code></pre>
            <input type="hidden" id="mx-scu-generated-url" />
            <button class="button" id="mx-scu-copy-url">
                <?php esc_html_e( 'Copy', 'shareable-checkout-urls' ); ?>
            </button>
        </p>
    </div>
    <?php
    $saved_text = isset( $data['shortcode_text'] ) ? $data['shortcode_text'] : '';
    ?>
    <div id="mx-scu-shortcode-builder">
        <h2><?php esc_html_e( 'Embedable Shortcode', 'shareable-checkout-urls' ); ?></h2>

        <p>
            <label for="mx-scu-shortcode-text">
                <?php esc_html_e( 'Link Text', 'shareable-checkout-urls' ); ?>
            </label>
            <input
                type="text"
                id="mx-scu-shortcode-text"
                name="mx_scu_shortcode_text"
                value="<?php echo esc_attr( $saved_text ); ?>"
                placeholder="<?php esc_attr_e( 'Enter link text…', 'shareable-checkout-urls' ); ?>"
                style="width:300px; margin-left:8px;"
            />
        </p>

        <p>
            <label><?php esc_html_e( 'Generated Shortcode', 'shareable-checkout-urls' ); ?></label>
            <pre class="mx-scu-generated-shortcode"><code id="mx-scu-generated-shortcode-text">
				[scu_link id="<?php echo esc_attr( $post->ID ); ?>"<?php
				  if ( $saved_text ) {
					echo ' text="' . esc_attr( $saved_text ) . '"';
				  }
				?>]
            </code></pre>
            <button class="button" id="mx-scu-copy-shortcode">
                <?php esc_html_e( 'Copy Shortcode', 'shareable-checkout-urls' ); ?>
            </button>
        </p>
		
		<p class="description" style="margin-top:10px;">
			<?php esc_html_e('This is the basic shortcode. You can add any of the following attributes for advanced usage:', 'shareable-checkout-urls'); ?>
		</p>
		<ul style="font-size:13px;margin-bottom:8px;line-height:1.7;">
			<li><strong>class</strong>: <?php esc_html_e('Add custom CSS class', 'shareable-checkout-urls'); ?></li>
			<li><strong>style</strong>: <?php esc_html_e('Inline CSS styles', 'shareable-checkout-urls'); ?></li>
			<li><strong>target</strong>: <code>_blank</code> <?php esc_html_e('to open in new tab', 'shareable-checkout-urls'); ?></li>
			<li><strong>rel</strong>: <code>nofollow</code>, <code>noopener</code>, etc.</li>
			<li><strong>button</strong>: <code>yes</code> <?php esc_html_e('to output as a button', 'shareable-checkout-urls'); ?></li>
			<li><strong>align</strong>: <code>left</code>, <code>center</code>, <code>right</code></li>
			<li><strong>aria-label</strong> / <strong>title</strong>: <?php esc_html_e('Accessibility label or tooltip', 'shareable-checkout-urls'); ?></li>
		</ul>
		<p style="font-size:13px;">
			<?php esc_html_e('Full example:', 'shareable-checkout-urls'); ?><br>
			<code style="background:#f7f7f7;display:inline-block;padding:6px 10px;">
				[scu_link id="<?php echo esc_attr($post->ID); ?>" text="<?php echo esc_attr($saved_text ?: 'Buy Now'); ?>" class="my-btn" style="color:#fff;background:#222;" target="_blank" rel="nofollow" button="yes" align="center" aria-label="Fast checkout" title="Go to checkout"]
			</code>
		</p>

    </div>

    <div id="mx-scu-qr-builder">
		<h2><?php esc_html_e( 'QR Code Options', 'shareable-checkout-urls' ); ?></h2>

		<p>
		  <label for="mx-scu-qr-size"><strong><?php esc_html_e('QR Size (px)', 'shareable-checkout-urls'); ?></strong></label>
		  <input type="number" id="mx-scu-qr-size" name="mx_scu_qr_size"
				 value="<?php echo esc_attr( $qr_size ); ?>" min="50" max="1000" style="width:80px;" />
		</p>

		<p>
		  <label for="mx-scu-qr-colorDark"><strong><?php esc_html_e('Dark Color', 'shareable-checkout-urls'); ?></strong></label>
		  <input type="text" id="mx-scu-qr-colorDark" name="mx_scu_qr_colorDark"
				 class="mx-scu-qr-color-field" value="<?php echo esc_attr( $qr_colorDark ); ?>" />
		</p>

		<p>
		  <label for="mx-scu-qr-colorLight"><strong><?php esc_html_e('Light Color', 'shareable-checkout-urls'); ?></strong></label>
		  <input type="text" id="mx-scu-qr-colorLight" name="mx_scu_qr_colorLight"
				 class="mx-scu-qr-color-field" value="<?php echo esc_attr( $qr_colorLight ); ?>" />
		</p>

		<p>
			<label for="mx-scu-qr-output-type">
				<strong><?php esc_html_e( 'Output mode', 'shareable-checkout-urls' ); ?></strong>
			</label><br/>
			<select id="mx-scu-qr-output-type" style="width:100%;">
				<option value="datauri"><?php esc_html_e( 'Data-URI Image', 'shareable-checkout-urls' ); ?></option>
				<option value="embed"><?php esc_html_e( 'Embed Snippet', 'shareable-checkout-urls' ); ?></option>
				<option value="download"><?php esc_html_e( 'Download PNG', 'shareable-checkout-urls' ); ?></option>
				<option value="download_svg"><?php esc_html_e( 'Download SVG',    'shareable-checkout-urls' ); ?></option>
			</select>
		</p>

		<div id="mx-scu-qr-container" style="margin:1em 0;"></div>

		<div id="mx-scu-qr-snippet-container" style="display:none;">
			<textarea
				id="mx-scu-qr-embed-snippet"
				rows="3"
				style="width:100%;"
				readonly
				placeholder="<?php esc_attr_e( 'Embed code appears here…', 'shareable-checkout-urls' ); ?>"
			></textarea>
		</div>

		<p class="description">
			<strong><?php esc_html_e( 'Data-URI Image:', 'shareable-checkout-urls' ); ?></strong>
			<?php esc_html_e( 'Self-contained <img> you can copy or drag-drop anywhere.', 'shareable-checkout-urls' ); ?>
		</p>
		<p class="description">
			<strong><?php esc_html_e( 'Embed Snippet:', 'shareable-checkout-urls' ); ?></strong>
			<?php esc_html_e( 'Exact <img> HTML sent to the textarea for one-click copy/paste.', 'shareable-checkout-urls' ); ?>
		</p>
		<p class="description">
			<strong><?php esc_html_e( 'Download PNG:', 'shareable-checkout-urls' ); ?></strong>
			<?php esc_html_e( 'Saves a .png file named qr-code-{ID}.png to your downloads folder.', 'shareable-checkout-urls' ); ?>
		</p>
		<p class="description">
			<strong><?php esc_html_e( 'Download SVG:', 'shareable-checkout-urls' ); ?></strong>
			<?php esc_html_e( 'Saves a high-quality, scalable .svg file named qr-code-{ID}.svg.', 'shareable-checkout-urls' ); ?>
		</p>
	</div>


    <?php
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
			plugins_url( 'includes/js/scu-admin.js', __FILE__ ),
			[ 'jquery', 'select2', 'jquery-ui-sortable', 'wp-color-picker' ],
			'1.1.0',
			true
		);
        wp_localize_script( 'mx-scu-admin', 'mx_scu_data', [
            'site_url' => trailingslashit( home_url() ),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
			'post_id'  => get_the_ID(),
			'endpoint_slug' => mx_scu_get_endpoint_slug(),
		    'qr_size'       => intval( get_post_meta( get_the_ID(), 'mx_scu_qr_size', true ) ?: 200 ),
            'qr_colorDark'  => get_post_meta( get_the_ID(), 'mx_scu_qr_colorDark', true ) ?: '#000000',
            'qr_colorLight' => get_post_meta( get_the_ID(), 'mx_scu_qr_colorLight', true ) ?: '#ffffff',
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
			plugins_url('includes/js/qr-generator.js', __FILE__),
			['qrcode-svg'],
			'1.2.0',
			true
		);


    }

    if ( did_action( 'admin_head' ) ) {
        $css_url = plugins_url( 'includes/css/scu-admin.css', __FILE__ );
        echo '<link rel="stylesheet" id="mx-scu-admin-css" href="'
           . esc_url( $css_url ) . '?ver=1.1.0" type="text/css" media="all" />' . "\n";
    }
}

add_action( 'wp_ajax_mx_scu_search_products', 'mx_scu_search_products' );
function mx_scu_search_products() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error();
    }

    $term = isset( $_GET['q'] )
        ? sanitize_text_field( wp_unslash( $_GET['q'] ) )
        : '';
    if ( ! $term ) {
        wp_send_json( [] );
    }

    $results   = [];
    $found_ids = [];

    $product_ids = get_posts( [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 10,
        'fields'         => 'ids',
    ] );

    foreach ( $product_ids as $pid ) {
        $prod = wc_get_product( $pid );
        // skip if not a simple product
        if ( ! $prod || 'simple' !== $prod->get_type() ) {
            continue;
        }

        $results[]   = [
            'id'    => $pid,
            'text'  => sprintf( '%s (ID:%d)', $prod->get_name(), $pid ),
            'stock' => $prod->managing_stock() ? $prod->get_stock_quantity() : -1,
        ];
        $found_ids[] = $pid;
    }

    if ( ! empty( $product_ids ) ) {
        $variations = wc_get_products( [
            'type'      => 'variation',
            'parent'    => $product_ids,
            'limit'     => -1,
            'status'    => 'publish',
        ] );

        foreach ( $variations as $var ) {
            $vid   = $var->get_id();
            $label = sprintf( '%s (ID:%d)', $var->get_name(), $vid );

            if ( stripos( $label, $term ) !== false && ! in_array( $vid, $found_ids, true ) ) {
                $results[]   = [
                    'id'    => $vid,
                    'text'  => $label,
                    'stock' => $var->managing_stock() ? $var->get_stock_quantity() : -1,
                ];
                $found_ids[] = $vid;
            }
        }
    }

    foreach ( wc_get_attribute_taxonomies() as $tax ) {
        $meta_key = sanitize_text_field( $tax->attribute_name );

        $var_ids = get_posts( [
            'post_type'      => 'product_variation',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'fields'         => 'ids',
            'meta_query'     => [[
                'key'     => $meta_key,
                'value'   => $term,
                'compare' => 'LIKE',
            ]],
        ] );

        foreach ( $var_ids as $vid ) {
            if ( in_array( $vid, $found_ids, true ) ) {
                continue;
            }
            $var = wc_get_product( $vid );
            if ( ! $var ) {
                continue;
            }
            $results[]   = [
                'id'    => $vid,
                'text'  => sprintf( '%s (ID:%d)', $var->get_name(), $vid ),
                'stock' => $var->managing_stock() ? $var->get_stock_quantity() : -1,
            ];
            $found_ids[] = $vid;
        }
    }

    wp_send_json( $results );
}

add_action( 'wp_ajax_mx_scu_search_coupons', 'mx_scu_search_coupons' );
function mx_scu_search_coupons() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error();
    }

    $term = isset( $_GET['q'] )
        ? sanitize_text_field( wp_unslash( $_GET['q'] ) )
        : '';
    if ( ! $term ) {
        wp_send_json( [] );
    }

    $results = [];
    $coupons = get_posts( [
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 15,
        'fields'         => 'ids',
    ] );

    foreach ( $coupons as $cid ) {
        $code = get_the_title( $cid );
        $results[] = [
            'id'    => $code,                       
            'label' => sprintf( '%s (ID:%d)', $code, $cid ),
        ];
    }

    wp_send_json( $results );
}


add_action( 'save_post_scu_link', 'mx_scu_save_post' );
function mx_scu_save_post( $post_id ) {
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        || ! isset( $_POST['mx_scu_nonce'] )
        || ! wp_verify_nonce( wp_unslash( $_POST['mx_scu_nonce'] ), 'mx_scu_save' )
        || ! current_user_can( 'edit_post', $post_id )
    ) {
        return;
    }

    $ids            = isset( $_POST['mx_scu_products_ids'] )   ? array_map( 'intval', wp_unslash( $_POST['mx_scu_products_ids'] ) ) : [];
    $qts            = isset( $_POST['mx_scu_products_qtys'] )  ? array_map( 'intval', wp_unslash( $_POST['mx_scu_products_qtys'] ) ) : [];
    $coupon         = isset( $_POST['mx_scu_coupon'] )         ? sanitize_text_field( wp_unslash( $_POST['mx_scu_coupon'] ) ) : '';
    $shortcode_text = isset( $_POST['mx_scu_shortcode_text'] ) ? sanitize_text_field( wp_unslash( $_POST['mx_scu_shortcode_text'] ) ) : '';
		
	$qr_size       = isset( $_POST['mx_scu_qr_size'] )
                  ? intval(   wp_unslash( $_POST['mx_scu_qr_size'] ) )
                  : 200;

	$qr_colorDark  = isset( $_POST['mx_scu_qr_colorDark'] )
					  ? sanitize_hex_color( wp_unslash( $_POST['mx_scu_qr_colorDark'] ) )
					  : '#000000';

	$qr_colorLight = isset( $_POST['mx_scu_qr_colorLight'] )
					  ? sanitize_hex_color( wp_unslash( $_POST['mx_scu_qr_colorLight'] ) )
					  : '#ffffff';

    $products = [];
    foreach ( $ids as $i => $id ) {
        if ( $id ) {
            $qty = ( ! empty( $qts[ $i ] ) && $qts[ $i ] > 0 )
                ? $qts[ $i ]
                : 1;
            $products[] = [
                'id'  => $id,
                'qty' => $qty,
            ];
        }
    }

    update_post_meta( $post_id, 'mx_scu_data', [
        'products'       => $products,
        'coupon'         => $coupon,
        'shortcode_text' => $shortcode_text,
		
    ] );
	
   update_post_meta( $post_id, 'mx_scu_qr_size',      $qr_size );
   update_post_meta( $post_id, 'mx_scu_qr_colorDark', $qr_colorDark );
  update_post_meta( $post_id, 'mx_scu_qr_colorLight',$qr_colorLight );

    if ( ! empty( $products ) ) {
        $parts = array_map(
            function( $p ) {
                return $p['id'] . ':' . $p['qty'];
            },
            $products
        );

        $slug = mx_scu_get_endpoint_slug();

        $url = trailingslashit( home_url() ) . $slug . '/?products=' . implode( ',', $parts );

        if ( $coupon ) {
            $url .= '&coupon=' . rawurlencode( $coupon );
        }
    } else {
        $url = '';
    }

    update_post_meta( $post_id, 'mx_scu_url', $url );
}

add_filter( 'manage_scu_link_posts_columns', 'mx_scu_columns' );
function mx_scu_columns( $cols ) {
    return [
        'cb'       => $cols['cb'],
        'title'    => __( 'Name', 'shareable-checkout-urls' ),
        'products' => __( 'Products × Qty', 'shareable-checkout-urls' ),
        'coupon'   => __( 'Coupon', 'shareable-checkout-urls' ),
        'url'      => __( 'Checkout URL', 'shareable-checkout-urls' ),
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
        case 'url':
            $url = get_post_meta( $post_id, 'mx_scu_url', true );
            if ( $url ) {
                echo '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'View', 'shareable-checkout-urls' ) . '</a>';
            }
            break;
    }
}

add_shortcode('scu_link', function($atts) {
    $a = shortcode_atts([
        'id'    => '',
        'text'  => '',
        'class' => '',
        'style' => '',
        'target'=> '',
        'rel'   => '',
        'button'=> '',
        'align' => '',
        'aria-label' => '',
        'title'      => '',
    ], $atts, 'scu_link');

    $url  = get_post_meta(intval($a['id']), 'mx_scu_url', true);
    $text = $a['text'] ? esc_html($a['text']) : esc_html__('Go to Checkout', 'shareable-checkout-urls');
    if (!$url) return '';

    $attrs = [];
    if ($a['class'])       $attrs[] = 'class="'.esc_attr($a['class']).'"';
    if ($a['style'])       $attrs[] = 'style="'.esc_attr($a['style']).'"';
    if ($a['target'])      $attrs[] = 'target="'.esc_attr($a['target']).'"';
    if ($a['rel'])         $attrs[] = 'rel="'.esc_attr($a['rel']).'"';
    if ($a['aria-label'])  $attrs[] = 'aria-label="'.esc_attr($a['aria-label']).'"';
    if ($a['title'])       $attrs[] = 'title="'.esc_attr($a['title']).'"';
    if ($a['align'])       $attrs[] = 'style="text-align:'.esc_attr($a['align']).';' . ($a['style'] ?? '') . '"';

    $attr_str = implode(' ', $attrs);

    if (strtolower($a['button']) === 'yes') {
        return sprintf(
            '<a href="%s" %s><button type="button">%s</button></a>',
            esc_url($url), $attr_str, $text
        );
    }
    return sprintf(
        '<a href="%s" %s>%s</a>',
        esc_url($url), $attr_str, $text
    );
});

add_filter( 'woocommerce_get_sections_advanced', 'mx_scu_add_advanced_section' );
function mx_scu_add_advanced_section( $sections ) {
    $sections['scu_endpoint'] = __( 'Shareable URLs', 'shareable-checkout-urls' );
    return $sections;
}

add_filter( 'woocommerce_get_settings_advanced', 'mx_scu_advanced_settings', 10, 2 );
function mx_scu_advanced_settings( $settings, $current_section ) {
    if ( 'scu_endpoint' !== $current_section ) {
        return $settings;
    }

    $scu_settings = [];

    $scu_settings[] = [
        'title' => __( 'Shareable URLs Settings', 'shareable-checkout-urls' ),
        'type'  => 'title',
        'desc'  => __( 'Customize the endpoint slug used by the Shareable Checkout URLs plugin.', 'shareable-checkout-urls' ),
        'id'    => 'scu_endpoint_options',
    ];

    $scu_settings[] = [
        'title'    => __( 'Endpoint Slug', 'shareable-checkout-urls' ),
        'id'       => 'woocommerce_scu_endpoint_slug',
        'type'     => 'text',
        'desc'     => __( 'The URL path segment for shareable‐checkout links (e.g., <code>checkout-link</code>, <code>fast-checkout</code>).' , 'shareable-checkout-urls' ),
        'default'  => 'checkout-link',
        'autoload' => false,
    ];

    $scu_settings[] = [ 'type' => 'sectionend', 'id' => 'scu_endpoint_options' ];

    return $scu_settings;
}

/**
 * Get the shareable‐checkout endpoint slug (default: checkout-link),
 * but override with the WooCommerce setting if present.
 */
function mx_scu_get_endpoint_slug() {
    $opt = get_option( 'woocommerce_scu_endpoint_slug', '' );
    if ( $opt ) {
        return sanitize_title( $opt );
    }
    return 'checkout-link';
}

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'scu';
    return $vars;
} );

add_action( 'init', function() {
    $slug = sanitize_title( mx_scu_get_endpoint_slug() );
    add_rewrite_rule(
        '^' . preg_quote( $slug, '/' ) . '/?$',
        'index.php?scu=1',  
        'top'
    );
} );

register_activation_hook( __FILE__, 'mx_scu_flush_rewrites' );
add_action( 'update_option_woocommerce_scu_endpoint_slug', 'mx_scu_flush_rewrites', 10, 2 );
function mx_scu_flush_rewrites() {
    // re-register our rule so WP knows about it during the flush
    $slug = sanitize_title( mx_scu_get_endpoint_slug() );
    add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?scu=1', 'top' );
    flush_rewrite_rules();
}

add_action( 'template_redirect', function() {
    if ( intval( get_query_var( 'scu' ) ) !== 1 ) {
        return;
    }

    if ( empty( $_GET['products'] ) ) {
        wp_die( __( 'No products specified.', 'shareable-checkout-urls' ) );
    }

    WC()->cart->empty_cart( true );

    $pairs = explode( ',', sanitize_text_field( wp_unslash( $_GET['products'] ) ) );
    foreach ( $pairs as $pair ) {
        list( $id, $qty ) = array_pad( explode( ':', $pair ), 2, 1 );
        if ( $prod = wc_get_product( intval( $id ) ) ) {
            WC()->cart->add_to_cart( $prod->get_id(), intval( $qty ) );
        }
    }

    if ( ! empty( $_GET['coupon'] ) ) {
        WC()->cart->apply_coupon( sanitize_text_field( wp_unslash( $_GET['coupon'] ) ) );
    }

    wp_safe_redirect( wc_get_checkout_url() );
    exit;
} );
