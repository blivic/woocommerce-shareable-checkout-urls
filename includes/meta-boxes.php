<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mx_scu_meta_or_option( $post_id, $meta_key, $option_key ) {
    $m = get_post_meta( $post_id, $meta_key, true );
    return $m !== '' ? $m : get_option( $option_key, '' );
}

add_action( 'add_meta_boxes', 'mx_scu_add_meta_boxes' );
function mx_scu_add_meta_boxes() {
	if ( ! mx_scu_current_user_has_access() ) {
		return;
	}

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

	$data          = get_post_meta( $post->ID, 'mx_scu_data', true );
	$items         = isset( $data['products'] ) ? $data['products'] : [];
	$coupon        = isset( $data['coupon'] )   ? $data['coupon']   : '';
	$qr_size       = intval( get_post_meta( $post->ID, 'mx_scu_qr_size', true ) ) ?: 200;
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
		<p class="description" style="margin-bottom:20px; font-size:0.8em;">
			<strong><?php esc_html_e( 'NOTE:', 'shareable-checkout-urls' ); ?></strong>
			<?php esc_html_e( ' For variable products only fully-defined variations can be added (any “Any …” wildcards are skipped), so checkout links always pass validation.', 'shareable-checkout-urls' ); ?>
		</p>

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
			<?php esc_html_e( 'This is the basic shortcode. You can add any of the following attributes for advanced usage:', 'shareable-checkout-urls' ); ?>
		</p>
		<ul style="font-size:13px;margin-bottom:8px;line-height:1.7;">
			<li><strong>class</strong>: <?php esc_html_e( 'Add custom CSS class', 'shareable-checkout-urls' ); ?></li>
			<li><strong>style</strong>: <?php esc_html_e( 'Inline CSS styles', 'shareable-checkout-urls' ); ?></li>
			<li><strong>target</strong>: <code>_blank</code> <?php esc_html_e( 'to open in new tab', 'shareable-checkout-urls' ); ?></li>
			<li><strong>rel</strong>: <code>nofollow</code>, <code>noopener</code>, etc.</li>
			<li><strong>button</strong>: <code>yes</code> <?php esc_html_e( 'to output as a button', 'shareable-checkout-urls' ); ?></li>
			<li><strong>align</strong>: <code>left</code>, <code>center</code>, <code>right</code></li>
			<li><strong>aria-label</strong> / <strong>title</strong>: <?php esc_html_e( 'Accessibility label or tooltip', 'shareable-checkout-urls' ); ?></li>
		</ul>
		<p style="font-size:13px;">
			<?php esc_html_e( 'Full example:', 'shareable-checkout-urls' ); ?><br>
			<code style="background:#f7f7f7;display:inline-block;padding:6px 10px;">
				[scu_link id="<?php echo esc_attr( $post->ID ); ?>" text="<?php echo esc_attr( $saved_text ?: 'Buy Now' ); ?>" class="my-btn" style="color:#fff;background:#222;" target="_blank" rel="nofollow" button="yes" align="center" aria-label="Fast checkout" title="Go to checkout"]
			</code>
		</p>
	</div>

	<div id="mx-scu-qr-builder">
		<h2><?php esc_html_e( 'QR Code Options', 'shareable-checkout-urls' ); ?></h2>

		<p>
			<label for="mx-scu-qr-size"><strong><?php esc_html_e( 'QR Size (px)', 'shareable-checkout-urls' ); ?></strong></label>
			<input type="number" id="mx-scu-qr-size" name="mx_scu_qr_size"
				value="<?php echo esc_attr( $qr_size ); ?>" min="50" max="1000" style="width:80px;" />
		</p>

		<p>
			<label for="mx-scu-qr-colorDark"><strong><?php esc_html_e( 'Dark Color', 'shareable-checkout-urls' ); ?></strong></label>
			<input type="text" id="mx-scu-qr-colorDark" name="mx_scu_qr_colorDark"
				class="mx-scu-qr-color-field" value="<?php echo esc_attr( $qr_colorDark ); ?>" />
		</p>

		<p>
			<label for="mx-scu-qr-colorLight"><strong><?php esc_html_e( 'Light Color', 'shareable-checkout-urls' ); ?></strong></label>
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
				<option value="download_svg"><?php esc_html_e( 'Download SVG', 'shareable-checkout-urls' ); ?></option>
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

	<div id="mx-scu-promo-message-builder">
		<h2><?php esc_html_e( 'Promo Message (optional)', 'shareable-checkout-urls' ); ?></h2>

		<?php
		$promo_message = get_post_meta( $post->ID, 'mx_scu_promo_message', true );
		$display_mode  = get_post_meta( $post->ID, 'mx_scu_promo_display_mode', true );
		if ( ! in_array( $display_mode, [ 'notice', 'custom' ], true ) ) {
			$display_mode = 'notice';
		}
		?>

		<p>
			<label for="mx-scu-promo-message-text">
				<?php esc_html_e( 'This message will appear above the checkout form when this link is used. For now only on Classic checkout.', 'shareable-checkout-urls' ); ?>
			</label>
		</p>

		<p>
			<textarea
				id="mx-scu-promo-message-text"
				name="mx_scu_promo_message"
				rows="4"
				style="width:100%;"
				placeholder="<?php esc_attr_e( 'You can enter basic HTML like <strong>, <em>, <a>…', 'shareable-checkout-urls' ); ?>"
			><?php echo esc_textarea( $promo_message ); ?></textarea>
		</p>

		<p class="description" style="font-size:13px;">
			<?php esc_html_e( 'Supports basic HTML formatting (e.g. <strong>, <em>, <h3>, <a href="">link</a>)', 'shareable-checkout-urls' ); ?>
		</p>

		<p id="mx-scu-display-mode-wrap" style="<?php echo trim( $promo_message ) ? '' : 'display:none;'; ?>">
			<label><strong><?php esc_html_e( 'Display Promo Message As:', 'shareable-checkout-urls' ); ?></strong></label><br>
			<label>
				<input type="radio" name="mx_scu_promo_display_mode" value="notice" <?php checked( $display_mode, 'notice' ); ?>>
				<?php esc_html_e( 'WooCommerce Notice', 'shareable-checkout-urls' ); ?>
			</label><br>
			<label>
				<input type="radio" name="mx_scu_promo_display_mode" value="custom" <?php checked( $display_mode, 'custom' ); ?>>
				<?php esc_html_e( 'Custom Block (above notices)', 'shareable-checkout-urls' ); ?>
			</label>
		</p>
	</div>

	<?php
	$max_uses = get_post_meta( $post->ID, 'mx_scu_max_uses', true );
	?>
	<div id="mx-scu-max-uses">
		<h2><?php esc_html_e( 'Usage Limits', 'shareable-checkout-urls' ); ?></h2>
		<p>
			<label for="mx-scu-max-uses-picker" style="margin-bottom:10px; display:inline-block;">
				<?php esc_html_e( 'Maximum allowed uses (optional)', 'shareable-checkout-urls' ); ?>
			</label><br>
			<input
				type="number"
				id="mx-scu-max-uses-picker"
				name="mx_scu_max_uses"
				min="0"
				value="<?php echo esc_attr( $max_uses ); ?>"
				style="width:80px;"
			/>
			<span class="description"><?php esc_html_e( 'Leave blank or 0 for unlimited.', 'shareable-checkout-urls' ); ?></span>
		</p>
	</div>
	
	<div id="mx-scu-utm">
		<?php
		$mode = get_post_meta( $post->ID, 'mx_scu_tracking_mode', true ) ?: 'global';
		?>
		<h2><?php esc_html_e( 'UTM & Pixel Tracking (optional)', 'shareable-checkout-urls' ); ?></h2>

		<p><strong><?php esc_html_e( 'Tracking for this link', 'shareable-checkout-urls' ); ?></strong></p>
		<label>
		  <input type="radio" name="mx_scu_tracking_mode" value="global" <?php checked( $mode, 'global' ); ?> />
		  <?php esc_html_e( 'Use global defaults', 'shareable-checkout-urls' ); ?>
		</label><br>
		<label>
		  <input type="radio" name="mx_scu_tracking_mode" value="custom" <?php checked( $mode, 'custom' ); ?> />
		  <?php esc_html_e( 'Use custom UTM/pixel for this link', 'shareable-checkout-urls' ); ?>
		</label><br>
		<label>
		  <input type="radio" name="mx_scu_tracking_mode" value="none" <?php checked( $mode, 'none' ); ?> />
		  <?php esc_html_e( 'Disable tracking on this link', 'shareable-checkout-urls' ); ?>
		</label>

		<div id="mx-scu-tracking-fields" style="<?php echo $mode === 'custom' ? '' : 'display:none;'; ?>; margin-top:1em;">
		  <p>
			<label for="mx-scu-utm-source"><strong><?php esc_html_e( 'UTM Source', 'shareable-checkout-urls' ); ?></strong></label><br>
			<input type="text" id="mx-scu-utm-source" name="mx_scu_utm_source"
			  value="<?php echo esc_attr( mx_scu_meta_or_option( $post->ID, 'mx_scu_utm_source',   'scu_utm_source' ) ); ?>"
			  placeholder="<?php esc_attr_e( 'newsletter', 'shareable-checkout-urls' ); ?>" style="width:100%;" />
		  </p>
		  <p>
			<label for="mx-scu-utm-medium"><strong><?php esc_html_e( 'UTM Medium', 'shareable-checkout-urls' ); ?></strong></label><br>
			<input type="text" id="mx-scu-utm-medium" name="mx_scu_utm_medium"
			  value="<?php echo esc_attr( mx_scu_meta_or_option( $post->ID, 'mx_scu_utm_medium',   'scu_utm_medium' ) ); ?>"
			  placeholder="<?php esc_attr_e( 'email', 'shareable-checkout-urls' ); ?>" style="width:100%;" />
		  </p>
		  <p>
			<label for="mx-scu-utm-campaign"><strong><?php esc_html_e( 'UTM Campaign', 'shareable-checkout-urls' ); ?></strong></label><br>
			<input type="text" id="mx-scu-utm-campaign" name="mx_scu_utm_campaign"
			  value="<?php echo esc_attr( mx_scu_meta_or_option( $post->ID, 'mx_scu_utm_campaign', 'scu_utm_campaign' ) ); ?>"
			  placeholder="<?php esc_attr_e( 'spring-sale', 'shareable-checkout-urls' ); ?>" style="width:100%;" />
		  </p>
		  <p>
			<label for="mx-scu-utm-term"><strong><?php esc_html_e( 'UTM Term', 'shareable-checkout-urls' ); ?></strong></label><br>
			<input type="text" id="mx-scu-utm-term" name="mx_scu_utm_term"
			  value="<?php echo esc_attr( mx_scu_meta_or_option( $post->ID, 'mx_scu_utm_term',     'scu_utm_term' ) ); ?>"
			  placeholder="<?php esc_attr_e( 'blue-widget', 'shareable-checkout-urls' ); ?>" style="width:100%;" />
		  </p>
		  <p>
			<label for="mx-scu-utm-content"><strong><?php esc_html_e( 'UTM Content', 'shareable-checkout-urls' ); ?></strong></label><br>
			<input type="text" id="mx-scu-utm-content" name="mx_scu_utm_content"
			  value="<?php echo esc_attr( mx_scu_meta_or_option( $post->ID, 'mx_scu_utm_content',  'scu_utm_content' ) ); ?>"
			  placeholder="<?php esc_attr_e( 'banner-1', 'shareable-checkout-urls' ); ?>" style="width:100%;" />
		  </p>
		  <p>
			<label for="mx-scu-pixel-id"><strong><?php esc_html_e( 'Meta Pixel ID', 'shareable-checkout-urls' ); ?></strong></label><br>
			<input type="text" id="mx-scu-pixel-id" name="mx_scu_pixel_id"
			  value="<?php echo esc_attr( mx_scu_meta_or_option( $post->ID, 'mx_scu_pixel_id',    'scu_pixel_id' ) ); ?>"
			  placeholder="<?php esc_attr_e( '1234567890', 'shareable-checkout-urls' ); ?>" style="width:100%;" />
		  </p>
	</div>

	<script>
	jQuery(function($){
	  $('input[name="mx_scu_tracking_mode"]').on('change', function(){
		$('#mx-scu-tracking-fields').toggle( this.value === 'custom' );
	  });
	});
	</script>

	<?php
}

add_action( 'save_post_scu_link', 'mx_scu_save_post' );
function mx_scu_save_post( $post_id ) {
    if ( ! mx_scu_current_user_has_access() ) {
        return;
    }
    if (
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! isset( $_POST['mx_scu_nonce'] ) ||
        ! wp_verify_nonce( wp_unslash( $_POST['mx_scu_nonce'] ), 'mx_scu_save' ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) {
        return;
    }

    $ids            = isset( $_POST['mx_scu_products_ids'] )   ? array_map( 'intval', wp_unslash( $_POST['mx_scu_products_ids'] ) ) : [];
    $qts            = isset( $_POST['mx_scu_products_qtys'] )  ? array_map( 'intval', wp_unslash( $_POST['mx_scu_products_qtys'] ) )  : [];
    $coupon         = isset( $_POST['mx_scu_coupon'] )         ? sanitize_text_field( wp_unslash( $_POST['mx_scu_coupon'] ) )         : '';
    $shortcode_text = isset( $_POST['mx_scu_shortcode_text'] ) ? sanitize_text_field( wp_unslash( $_POST['mx_scu_shortcode_text'] ) ) : '';

    $products = [];
    foreach ( $ids as $i => $id ) {
        if ( $id ) {
            $qty = ( ! empty( $qts[ $i ] ) && $qts[ $i ] > 0 ) ? $qts[ $i ] : 1;
            $products[] = [ 'id' => $id, 'qty' => $qty ];
        }
    }

    update_post_meta( $post_id, 'mx_scu_data', [
        'products'       => $products,
        'coupon'         => $coupon,
        'shortcode_text' => $shortcode_text,
    ] );

    if ( ! empty( $products ) ) {
        $parts = array_map( function( $p ){ return $p['id'] . ':' . $p['qty']; }, $products );
        $slug  = mx_scu_get_endpoint_slug();
        $url   = trailingslashit( home_url() ) . $slug . '/?products=' . implode( ',', $parts );
        if ( $coupon ) {
            $url .= '&coupon=' . rawurlencode( $coupon );
        }
    } else {
        $url = '';
    }

    update_post_meta( $post_id, 'mx_scu_url', $url );
	
if ( isset( $_POST['mx_scu_tracking_mode'] ) ) {
		$mode = sanitize_key( wp_unslash( $_POST['mx_scu_tracking_mode'] ) );
		update_post_meta( $post_id, 'mx_scu_tracking_mode', $mode );
	} else {
		update_post_meta( $post_id, 'mx_scu_tracking_mode', 'global' );
}

if ( $mode === 'custom' ) {
    update_post_meta( $post_id, 'mx_scu_utm_source',   sanitize_text_field( wp_unslash( $_POST['mx_scu_utm_source']   ?? '' ) ) );
    update_post_meta( $post_id, 'mx_scu_utm_medium',   sanitize_text_field( wp_unslash( $_POST['mx_scu_utm_medium']   ?? '' ) ) );
    update_post_meta( $post_id, 'mx_scu_utm_campaign', sanitize_text_field( wp_unslash( $_POST['mx_scu_utm_campaign'] ?? '' ) ) );
    update_post_meta( $post_id, 'mx_scu_utm_term',     sanitize_text_field( wp_unslash( $_POST['mx_scu_utm_term']     ?? '' ) ) );
    update_post_meta( $post_id, 'mx_scu_utm_content',  sanitize_text_field( wp_unslash( $_POST['mx_scu_utm_content']  ?? '' ) ) );
    update_post_meta( $post_id, 'mx_scu_pixel_id',     sanitize_text_field( wp_unslash( $_POST['mx_scu_pixel_id']     ?? '' ) ) );
	} else {
		delete_post_meta( $post_id, 'mx_scu_utm_source' );
		delete_post_meta( $post_id, 'mx_scu_utm_medium' );
		delete_post_meta( $post_id, 'mx_scu_utm_campaign' );
		delete_post_meta( $post_id, 'mx_scu_utm_term' );
		delete_post_meta( $post_id, 'mx_scu_utm_content' );
		delete_post_meta( $post_id, 'mx_scu_pixel_id' );
	}

}

add_action( 'add_meta_boxes', 'mx_scu_email_history_metabox' );
function mx_scu_email_history_metabox() {
    if ( 'yes' !== get_option( 'scu_enable_email',   'no' ) ) {
        return;
    }
    if ( 'yes' !== get_option( 'scu_enable_email_history', 'no' ) ) {
        return;
    }

    add_meta_box(
		'mx-scu-email-history',
		__( 'Email History', 'shareable-checkout-urls' ),
		'mx_scu_email_history_metabox_cb', 
		'scu_link',
		'side',
		'default'
	);
}
function mx_scu_email_history_metabox_cb( $post ) {
    $history = get_post_meta( $post->ID, 'mx_scu_email_history', true );
    if ( ! is_array( $history ) || empty( $history ) ) {
        echo '<p>' . __( 'No emails sent yet.', 'shareable-checkout-urls' ) . '</p>';
        return;
    }

    $history    = array_reverse( $history );
    $per_page   = 20;
    $page       = max( 1, intval( $_GET['history_page'] ?? 1 ) );
    $total      = count( $history );
    $pages      = ceil( $total / $per_page );
    $start      = ( $page - 1 ) * $per_page;
    $slice      = array_slice( $history, $start, $per_page );

    echo '<ul style="margin:0; padding-left:1.2em;">';
    foreach ( $slice as $entry ) {
        $dt  = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['timestamp'] ) );
        $to  = implode( ', ', $entry['to'] );
        echo '<li><strong>' . esc_html( $dt ) . '</strong><br/>' .
             esc_html__( 'To:', 'shareable-checkout-urls' ) . ' ' . esc_html( $to ) .
             '</li>';
    }
    echo '</ul>';

    if ( $pages > 1 ) {
        $base = remove_query_arg( 'history_page' );
        echo '<p style="margin-left:15px;">';
        if ( $page > 1 ) {
            printf(
                '<a href="%s">&laquo; %s</a> ',
                esc_url( add_query_arg( 'history_page', $page - 1 ) ),
                __( 'Previous', 'shareable-checkout-urls' )
            );
        }
        if ( $page < $pages ) {
            printf(
                '<a href="%s">%s &raquo;</a>',
                esc_url( add_query_arg( 'history_page', $page + 1 ) ),
                __( 'Next', 'shareable-checkout-urls' )
            );
        }
        echo '</p>';
    }
}
