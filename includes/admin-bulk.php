<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'mx_scu_cpt_capability' ) ) {
    function mx_scu_cpt_capability() {
        $cpt = get_post_type_object( 'scu_link' );
        return ( $cpt && ! empty( $cpt->cap->edit_posts ) )
            ? $cpt->cap->edit_posts
            : 'manage_woocommerce';
    }
}

add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook === 'edit.php'
      && isset( $_GET['post_type'] )
      && $_GET['post_type'] === 'scu_link'
    ) {
        wp_enqueue_script(
            'scu-modal',
            plugin_dir_url( __FILE__ ) . 'js/admin-bulk-modal.js',
            [ 'jquery', 'thickbox' ],
            '1.0',
            true
        );
        wp_enqueue_style( 'thickbox' );
        wp_localize_script( 'scu-modal', 'SCU_Ajax', [
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'import_nonce' => wp_create_nonce( 'scu_import' ),
            'export_nonce' => wp_create_nonce( 'scu_export' ),
        ] );
    }
});

add_action( 'admin_footer', function() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'edit-scu_link' ) {
        return;
    }
	
	// mobile‐friendly
    echo '<style>
	  @media (max-width: 640px) {
      #TB_window {
        position: fixed !important;
        width: 95% !important;
        max-width: 95% !important;
        left: 2.5% !important;
        top: 5% !important;
        margin: 0 !important;       
      }
      #TB_ajaxContent {
        width: 100%    !important;   
        height: auto   !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        padding: 1em   !important;
        box-sizing: border-box !important;
      }
      #TB_ajaxContent > #scu-import-modal {
        width: auto     !important;
        max-width: 100% !important;
        margin: 0       !important;
        box-sizing: border-box !important;
      }
      #scu-dropzone {
        width: 80% !important;
        max-width: 100% !important;
        margin: 1em 0 !important;
      }
    }
    </style>';

    $modal_id   = 'scu-import-modal';
    $import_txt = esc_js( __( 'Import', 'shareable-checkout-urls' ) );
    $export_txt = esc_js( __( 'Export', 'shareable-checkout-urls' ) );
    ?>
    <script>
    jQuery(function($){
      var importBtn = '<a href="#TB_inline?width=600&height=300&inlineId=<?php echo $modal_id; ?>" class="page-title-action thickbox"><?php echo $import_txt; ?></a>';
      var exportBtn = '<a href="#" id="scu-export-btn" class="page-title-action"><?php echo $export_txt; ?></a>';
      var $add = $('.wrap h1.wp-heading-inline').next('a.page-title-action');
      if ( $add.length ) {
        $add.after(importBtn + exportBtn);
      } else {
        $('.wrap h1.wp-heading-inline').after(importBtn + exportBtn);
      }
    });
    </script>

    <div id="<?php echo esc_attr( $modal_id ); ?>" style="display:none;padding:20px;">
      <h2><?php esc_html_e( 'Import CSV', 'shareable-checkout-urls' ); ?></h2>

      <span><small><?php esc_html_e( 'csv format: scu name,products,coupon', 'shareable-checkout-urls' ); ?></small></span>

      <p><a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=scu_download_template&_wpnonce=' . wp_create_nonce( 'scu_template' ) ) ); ?>">
        <?php esc_html_e( 'Download CSV template', 'shareable-checkout-urls' ); ?></a></p>

     <label id="scu-dropzone" for="scu-import-file" style="border:2px dashed #ccc;padding:20px;text-align:center;cursor:pointer;display:block;">
	  <?php esc_html_e( 'drag & drop csv here or click to browse', 'shareable-checkout-urls' ); ?>
	  <input type="file" id="scu-import-file" name="scu_csv" accept=".csv, text/csv" style="display:none;" />
	  </label>

      <p><button type="button" class="button button-primary" id="scu-import-submit" disabled>
		  <?php esc_html_e( 'Upload & create links', 'shareable-checkout-urls' ); ?></button></p>

      <progress id="scu-import-progress" max="100" value="0" style="width:100%;display:none;"></progress>

      <div id="scu-import-result" style="margin-top:10px;"></div>
    </div>
    <?php
});

add_action( 'wp_ajax_scu_export_csv', function() {

    if ( ! current_user_can( mx_scu_cpt_capability() )
      || ! check_admin_referer( 'scu_export', '_wpnonce' )
    ) {
        wp_die( 403 );
    }

    $timestamp = date_i18n( 'Y-m-d_H-i-s' );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="scu-export-' . $timestamp . '.csv"' );

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, [
        'SCU ID',
        'SCU Name',
        'Products',
        'Coupon',
        'Generated URL',
        'Uses',
        'Orders',
        'Revenue',
        'Conversion Rate',
    ] );

    $links = get_posts( [
        'post_type'      => 'scu_link',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    foreach ( $links as $link ) {
        $meta  = get_post_meta( $link->ID, 'mx_scu_data', true );
        $pairs = [];
        if ( ! empty( $meta['products'] ) && is_array( $meta['products'] ) ) {
            foreach ( $meta['products'] as $p ) {
                $pairs[] = absint( $p['id'] ) . ':' . absint( $p['qty'] );
            }
        }
        $url = get_post_meta( $link->ID, 'mx_scu_url', true );

        $uses    = (int) get_post_meta( $link->ID, 'mx_scu_uses',         true );
        $orders  = (int) get_post_meta( $link->ID, 'mx_scu_order_count', true );
        $revenue = (float) get_post_meta( $link->ID, 'mx_scu_order_total', true );

        $conversion = '';
        if ( $uses > 0 ) {
            $conversion = round( ( $orders / $uses ) * 100, 1 ) . '%';
        }

        fputcsv( $out, [
            $link->ID,
            $link->post_title,
            implode( ',', $pairs ),
            $meta['coupon'] ?? '',
            $url,
            $uses,
            $orders,
            $revenue,
            $conversion,
        ] );
    }
    fclose( $out );
    exit;
} );

add_action( 'wp_ajax_scu_download_template', function() {
    if ( ! current_user_can( mx_scu_cpt_capability() )
      || ! check_admin_referer( 'scu_template' )
    ) {
        wp_die( 403 );
    }
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="scu-template.csv"' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, [ 'scu name', 'products', 'coupon' ] );
    fputcsv( $out, [ 'holiday sale', '123:2,456:1', 'summer20' ] );
    fclose( $out );
    exit;
});

add_action( 'wp_ajax_scu_import_csv_queue', function() {
    if ( ! current_user_can( mx_scu_cpt_capability() )
      || ! check_admin_referer( 'scu_import', '_wpnonce' )
    ) {
        wp_die( 403 );
    }

    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if ( empty( $_FILES['scu_csv']['tmp_name'] )
      || ! is_uploaded_file( $_FILES['scu_csv']['tmp_name'] )
    ) {
        wp_die( __( 'No file uploaded.', 'shareable-checkout-urls' ) );
    }

    $upload = wp_handle_upload(
        $_FILES['scu_csv'],
        [ 'test_form' => false ]
    );
    if ( isset( $upload['error'] ) ) {
        wp_die( esc_html( $upload['error'] ) );
    }
    $file = $upload['file'];

    $fobj = new SplFileObject( $file );
    $fobj->setFlags( SplFileObject::READ_CSV );
    $fobj->rewind();
    $fobj->current(); 
    $total = 0;
    while ( $fobj->valid() ) {
        $fobj->next();
        if ( $fobj->valid() && array_filter( (array) $fobj->current() ) ) {
            $total++;
        }
    }

    $job_id = uniqid( 'scu_' );
    update_option( "scu_import_{$job_id}_file",  $file );
    update_option( "scu_import_{$job_id}_total", $total );
    update_option( "scu_import_{$job_id}_done",  0 );

    $chunk_size = 50;
    $chunks     = ceil( $total / $chunk_size );

    if ( $total > 0 && $total <= $chunk_size ) {
        do_action( 'scu_process_chunk', $job_id, 1, $chunk_size );
    } else {
        for ( $i = 0; $i < $chunks; $i++ ) {
            $start  = ( $i * $chunk_size ) + 1; 
            $length = $chunk_size;
            wp_schedule_single_event(
                time() + $i,
                'scu_process_chunk',
                [ $job_id, $start, $length ]
            );
        }
        if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
            require_once ABSPATH . 'wp-includes/cron.php';
            spawn_cron( true );
        }
    }

    wp_send_json_success( [ 'job_id' => $job_id ] );
} );

add_action( 'scu_process_chunk', function( $job_id, $start, $length ) {
    $file = get_option( "scu_import_{$job_id}_file" );
    if ( ! $file || ! file_exists( $file ) ) {
        return;
    }

    $f = fopen( $file, 'r' );
    if ( ! $f ) {
        return;
    }

    fgetcsv( $f );

    for ( $i = 1; $i < $start; $i++ ) {
        fgetcsv( $f );
    }

    $processed = 0;
    while ( $processed < $length && ( $row = fgetcsv( $f ) ) !== false ) {
        if ( empty( $row[0] ) ) {
            continue;
        }
        if ( count( $row ) >= 3 ) {
            $name     = sanitize_text_field( $row[0] );
            $prod_str = sanitize_text_field( $row[1] );
            $coupon   = sanitize_text_field( $row[2] );
        } else {
            $name     = sprintf( __( 'Bulk import %s', 'shareable-checkout-urls' ), date_i18n( 'Y-m-d H:i:s' ) );
            $prod_str = sanitize_text_field( $row[0] );
            $coupon   = sanitize_text_field( $row[1] ?? '' );
        }

        $post_id = wp_insert_post( [
            'post_type'   => 'scu_link',
            'post_title'  => $name,
            'post_status' => 'publish',
        ] );
        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        $parts = array_filter( array_map( 'trim', explode( ',', $prod_str ) ) );
        $data  = [];
        foreach ( $parts as $p ) {
            list( $id, $qty ) = array_merge( explode( ':', $p ), [ 1 ] );
            $data[] = [ 'id' => absint( $id ), 'qty' => absint( $qty ) ];
        }
        update_post_meta( $post_id, 'mx_scu_data', [ 'products' => $data, 'coupon' => $coupon ] );

        $slug   = mx_scu_get_endpoint_slug();
        $pp     = array_map( fn( $e ) => "{$e['id']}:{$e['qty']}", $data );
        $qs     = 'products=' . implode( ',', $pp );
        if ( $coupon ) {
            $qs .= '&coupon=' . rawurlencode( $coupon );
        }
        update_post_meta( $post_id, 'mx_scu_url', home_url( "/{$slug}/?{$qs}" ) );

        $processed++;
    }
    fclose( $f );

    $done = get_option( "scu_import_{$job_id}_done", 0 ) + $processed;
    update_option( "scu_import_{$job_id}_done", $done );

    if ( $done >= get_option( "scu_import_{$job_id}_total", 0 ) ) {
        update_option( "scu_import_{$job_id}_message",
            sprintf( _n( '%d link created.', '%d links created.', $done, 'shareable-checkout-urls' ), $done )
        );
        }
    },
    10,
    3   
);

add_action( 'wp_ajax_scu_import_progress', function() {
    if ( ! current_user_can( mx_scu_cpt_capability() ) ) {
        wp_die( 403 );
    }
    $job_id = sanitize_text_field( $_GET['job_id'] ?? '' );
    $total  = (int) get_option( "scu_import_{$job_id}_total", 0 );
    $done   = (int) get_option( "scu_import_{$job_id}_done",  0 );
    $msg    = get_option( "scu_import_{$job_id}_message", '' );
    wp_send_json_success([
        'total'     => $total,
        'completed' => $done,
        'done'      => $done >= $total,
        'message'   => $msg,
    ]);
});
