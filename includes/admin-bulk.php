<?php
/**
 * Admin CSV Import/Export with:
 * 1) drag/drop + header validation
 * 2) background‐queued import + progress bar
 * 3) template download link
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Capability helper
 */
if ( ! function_exists( 'mx_scu_cpt_capability' ) ) {
    function mx_scu_cpt_capability() {
        $cpt = get_post_type_object( 'scu_link' );
        return ( $cpt && ! empty( $cpt->cap->edit_posts ) )
            ? $cpt->cap->edit_posts
            : 'manage_woocommerce';
    }
}

/**
 * 1) Enqueue modal script + ThickBox on SCU_Link screen
 */
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

/**
 * 2) Inject buttons + modal HTML
 */
add_action( 'admin_footer', function() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'edit-scu_link' ) {
        return;
    }
	
	// 1) mobile‐friendly override
    echo '<style>
	  @media (max-width: 640px) {
      /* 1) Make the Thickbox window a fixed 95% width and reposition it */
      #TB_window {
        position: fixed !important;
        width: 95% !important;
        max-width: 95% !important;
        left: 2.5% !important;
        top: 5% !important;
        margin: 0 !important;          /* kill negative margins */
      }
      /* 2) Ensure the AJAX content container fills it and scrolls if too tall */
      #TB_ajaxContent {
        width: 100%    !important;     /* override inline width:600px */
        height: auto   !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        padding: 1em   !important;
        box-sizing: border-box !important;
      }
      /* 3) And your inner modal wrapper (#scu-import-modal) too */
      #TB_ajaxContent > #scu-import-modal {
        width: auto     !important;
        max-width: 100% !important;
        margin: 0       !important;
        box-sizing: border-box !important;
      }
      /* 4) Finally, make the dropzone full width of that modal */
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

      <!-- 1) Header validation instructions -->
      <span><small><?php esc_html_e( 'csv format: scu name,products,coupon', 'shareable-checkout-urls' ); ?></small></span>

      <!-- 3) Template download link -->
      <p><a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=scu_download_template&_wpnonce=' . wp_create_nonce( 'scu_template' ) ) ); ?>">
        <?php esc_html_e( 'Download CSV template', 'shareable-checkout-urls' ); ?></a></p>

      <!-- 1) Drag & drop zone -->
     <label id="scu-dropzone" for="scu-import-file" style="border:2px dashed #ccc;padding:20px;text-align:center;cursor:pointer;display:block;">
	  <?php esc_html_e( 'drag & drop csv here or click to browse', 'shareable-checkout-urls' ); ?>
	  <input type="file" id="scu-import-file" name="scu_csv" accept=".csv, text/csv" style="display:none;" />
	  </label>


      <!-- Upload button -->
      <p><button type="button" class="button button-primary" id="scu-import-submit" disabled>
		  <?php esc_html_e( 'Upload & create links', 'shareable-checkout-urls' ); ?></button></p>

      <!-- 2) Progress bar -->
      <progress id="scu-import-progress" max="100" value="0" style="width:100%;display:none;"></progress>

      <!-- Result area -->
      <div id="scu-import-result" style="margin-top:10px;"></div>
    </div>
    <?php
});
/**
 * AJAX: Export CSV with metrics (Uses, Orders, Revenue, Conversion Rate)
 */
add_action( 'wp_ajax_scu_export_csv', function() {
    // 1) Capability & nonce
    if ( ! current_user_can( mx_scu_cpt_capability() )
      || ! check_admin_referer( 'scu_export', '_wpnonce' )
    ) {
        wp_die( 403 );
    }

    // 2) Timestamped file name
    $timestamp = date_i18n( 'Y-m-d_H-i-s' );

    // 3) Send headers
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="scu-export-' . $timestamp . '.csv"' );

    // 4) Open output & write header row
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

    // 5) Fetch all SCU_Link posts
    $links = get_posts( [
        'post_type'      => 'scu_link',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    // 6) Loop through each link and output a row
    foreach ( $links as $link ) {
        $meta  = get_post_meta( $link->ID, 'mx_scu_data', true );
        $pairs = [];
        if ( ! empty( $meta['products'] ) && is_array( $meta['products'] ) ) {
            foreach ( $meta['products'] as $p ) {
                $pairs[] = absint( $p['id'] ) . ':' . absint( $p['qty'] );
            }
        }
        $url = get_post_meta( $link->ID, 'mx_scu_url', true );

        // the 4 new metrics
        $uses    = (int) get_post_meta( $link->ID, 'mx_scu_uses',         true );
        $orders  = (int) get_post_meta( $link->ID, 'mx_scu_order_count', true );
        $revenue = (float) get_post_meta( $link->ID, 'mx_scu_order_total', true );

        // compute conversion rate (orders ÷ uses × 100, 1 decimal)
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

    // 7) Close and exit
    fclose( $out );
    exit;
} );


/**
 * 4) AJAX: Download CSV template
 */
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

/**
 * 5) AJAX: Queue CSV import in background
 */
add_action( 'wp_ajax_scu_import_csv_queue', function() {
    // 1) Permissions + nonce
    if ( ! current_user_can( mx_scu_cpt_capability() )
      || ! check_admin_referer( 'scu_import', '_wpnonce' )
    ) {
        wp_die( 403 );
    }

    // 2) Load WP file API if needed
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    // 3) Validate the uploaded file
    if ( empty( $_FILES['scu_csv']['tmp_name'] )
      || ! is_uploaded_file( $_FILES['scu_csv']['tmp_name'] )
    ) {
        wp_die( __( 'No file uploaded.', 'shareable-checkout-urls' ) );
    }

    // 4) Move it into the uploads dir
    $upload = wp_handle_upload(
        $_FILES['scu_csv'],
        [ 'test_form' => false ]
    );
    if ( isset( $upload['error'] ) ) {
        wp_die( esc_html( $upload['error'] ) );
    }
    $file = $upload['file'];

    // 5) Count the number of data rows (skip header)
    $fobj = new SplFileObject( $file );
    $fobj->setFlags( SplFileObject::READ_CSV );
    $fobj->rewind();
    $fobj->current(); // skip header
    $total = 0;
    while ( $fobj->valid() ) {
        $fobj->next();
        if ( $fobj->valid() && array_filter( (array) $fobj->current() ) ) {
            $total++;
        }
    }

    // 6) Generate a unique job ID and store metadata
    $job_id = uniqid( 'scu_' );
    update_option( "scu_import_{$job_id}_file",  $file );
    update_option( "scu_import_{$job_id}_total", $total );
    update_option( "scu_import_{$job_id}_done",  0 );

    // 7) Schedule processing: small files synchronously, large via cron
    $chunk_size = 50;
    $chunks     = ceil( $total / $chunk_size );

    if ( $total > 0 && $total <= $chunk_size ) {
        // one‐off immediate processing
        do_action( 'scu_process_chunk', $job_id, 1, $chunk_size );
    } else {
        // schedule each chunk
        for ( $i = 0; $i < $chunks; $i++ ) {
            $start  = ( $i * $chunk_size ) + 1; // 1-based data row
            $length = $chunk_size;
            wp_schedule_single_event(
                time() + $i,
                'scu_process_chunk',
                [ $job_id, $start, $length ]
            );
        }
        // force WP-Cron to run right now
        if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
            require_once ABSPATH . 'wp-includes/cron.php';
            spawn_cron( true );
        }
    }

    // 8) Return the job_id to the client for polling
    wp_send_json_success( [ 'job_id' => $job_id ] );
} );

/**
 * 6) Background worker: process one chunk
 */
add_action( 'scu_process_chunk', function( $job_id, $start, $length ) {
    $file = get_option( "scu_import_{$job_id}_file" );
    if ( ! $file || ! file_exists( $file ) ) {
        return;
    }

    $f = fopen( $file, 'r' );
    if ( ! $f ) {
        return;
    }

    // Skip header
    fgetcsv( $f );

    // Skip to start-1 rows
    for ( $i = 1; $i < $start; $i++ ) {
        fgetcsv( $f );
    }

    $processed = 0;
    while ( $processed < $length && ( $row = fgetcsv( $f ) ) !== false ) {
        if ( empty( $row[0] ) ) {
            continue;
        }
        // SCU Name / Products / Coupon
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

        // save meta
        $parts = array_filter( array_map( 'trim', explode( ',', $prod_str ) ) );
        $data  = [];
        foreach ( $parts as $p ) {
            list( $id, $qty ) = array_merge( explode( ':', $p ), [ 1 ] );
            $data[] = [ 'id' => absint( $id ), 'qty' => absint( $qty ) ];
        }
        update_post_meta( $post_id, 'mx_scu_data', [ 'products' => $data, 'coupon' => $coupon ] );

        // generate & save URL
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

    // Update done count
    $done = get_option( "scu_import_{$job_id}_done", 0 ) + $processed;
    update_option( "scu_import_{$job_id}_done", $done );

    // If complete, record message
    if ( $done >= get_option( "scu_import_{$job_id}_total", 0 ) ) {
        update_option( "scu_import_{$job_id}_message",
            sprintf( _n( '%d link created.', '%d links created.', $done, 'shareable-checkout-urls' ), $done )
        );
        }
    },
    10, // priority
    3   // number of arguments the callback accepts
);

/**
 * 7) AJAX: Poll import progress
 */
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
