jQuery(function($){
  var ajaxUrl = mx_scu_data.ajax_url;
  var siteUrl = mx_scu_data.site_url;
  var postId  = mx_scu_data.post_id;
  var slug    = mx_scu_data.endpoint_slug;

  function showNotice($row, msg) {
    var $n = $row.find('.mx-scu-row-notice');
    if (!$n.length) {
      $n = $('<div class="mx-scu-row-notice" style="color:#b94a48;margin-top:4px;"></div>');
      $row.append($n);
    }
    $n.text(msg);
  }

  function clearNotice($row) {
    $row.find('.mx-scu-row-notice').remove();
  }

  function updateLink(){
    var items = [];
    $('#mx-scu-products .mx-scu-product-row').each(function(){
      var id = $(this).find('.mx-scu-product-id').val();
      var qt = $(this).find('.mx-scu-product-qty').val() || '1';
      if ( id ) items.push( id + ':' + qt );
    });
    var url = siteUrl + slug + '/?';
    if ( items.length ) url += 'products=' + items.join(',');

    // *** read coupon from our Select2 ***
    var coupon = $('#mx-scu-coupon').val().trim();
    if ( coupon ) url += '&coupon=' + encodeURIComponent(coupon);

    $('#mx-scu-generated-text').text( url );
    $('#mx-scu-generated-url').val( url );

    updateShortcode();
  }

  function updateShortcode(){
    var text = $('#mx-scu-shortcode-text').val().trim();
    var shortcode = '[scu_link id="' + postId + '"';
    if ( text ) {
      shortcode += ' text="' + text.replace(/"/g, '\\"') + '"';
    }
    shortcode += ']';
    $('#mx-scu-generated-shortcode-text').text( shortcode );
  }

  function initAutocomplete($row){
    var $input  = $row.find('.mx-scu-product-search');
    var $hidden = $row.find('.mx-scu-product-id');
    var $qty    = $row.find('.mx-scu-product-qty');
    var stock   = -1;

    $input.autocomplete({
      source: function(request, response){
        $.getJSON( ajaxUrl, {
          action: 'mx_scu_search_products',
          q:      request.term
        }).done(function(data){
          var mapped = $.map(data, function(item){
            return {
              label: item.text,
              value: item.text,
              id:    item.id,
              stock: (typeof item.stock === 'number') ? item.stock : -1
            };
          });
          response(mapped);
        });
      },
      minLength: 2,
      select: function(event, ui){
        $input.val(ui.item.value);
        $hidden.val(ui.item.id);
        stock = ui.item.stock;

        if ( stock >= 0 ) {
          $qty.attr('max', stock);
          clearNotice($row);
        } else {
          $qty.removeAttr('max');
          clearNotice($row);
        }

        updateLink();
        return false;
      }
    })
    .autocomplete('instance')._renderItem = function(ul, item){
      var badge = '';
      if ( item.stock > 0 ) {
        badge = ' <em style="color:#999">(in stock: ' + item.stock + ')</em>';
      } else if ( item.stock < 0 ) {
        badge = ' <em style="color:#999">(unlimited stock)</em>';
      }
      return $('<li>')
        .append('<div>' + item.label + badge + '</div>')
        .appendTo(ul);
    };

    $input.on('input', function(){
      if ( ! $(this).val().trim() ) {
        $hidden.val('');
        stock = -1;
        clearNotice($row);
        updateLink();
      }
    });

    $qty.on('input change', function(){
      var val = parseInt( $(this).val(), 10 ) || 0;
      if ( stock >= 0 && val > stock ) {
        showNotice($row, 'Only ' + stock + ' in stock.');
      } else {
        clearNotice($row);
      }
      updateLink();
    });
  }


  $('#mx-scu-products .mx-scu-product-row').each(function(){
    initAutocomplete($(this));
  });
  $('#mx-scu-products').sortable({
    handle: '.mx-scu-drag-handle',
    items: '.mx-scu-product-row',
    cursor: 'move',
    placeholder: 'mx-scu-sortable-placeholder',
    update: updateLink
  });
  $('#mx-scu-add-product').on('click', function(e){
    e.preventDefault();
    var $row = $(`
      <div class="mx-scu-product-row">
        <span class="mx-scu-drag-handle dashicons dashicons-move" style="cursor:move; margin-right:8px;"></span>
        <input type="text" class="mx-scu-product-search" placeholder="Search product…">
        <input type="hidden" name="mx_scu_products_ids[]" class="mx-scu-product-id" value="">
        <input type="number" class="mx-scu-product-qty" name="mx_scu_products_qtys[]" value="1" min="1" placeholder="Qty">
        <button class="button mx-remove-product">Remove</button>
      </div>
    `);
    $('#mx-scu-products').append($row);
    initAutocomplete($row);
    updateLink();
  });
  $(document).on('click', '.mx-remove-product', function(e){
    e.preventDefault();
    if ($('#mx-scu-products .mx-scu-product-row').length > 1) {
      $(this).closest('.mx-scu-product-row').remove();
      updateLink();
    }
  });

  $('#mx-scu-copy-url').on('click', function(e){
    e.preventDefault();
    var $btn = $(this);
    var url  = $('#mx-scu-generated-url').val() || $('#mx-scu-generated-text').text();
    var tmp  = document.createElement('textarea');
    tmp.value = url;
    document.body.appendChild(tmp);
    tmp.select();
    document.execCommand('copy');
    document.body.removeChild(tmp);
    var orig = $btn.text();
    $btn.text('Copied!').prop('disabled', true);
    setTimeout(function(){
      $btn.text(orig).prop('disabled', false);
    }, 2000);
  });

  $('#mx-scu-shortcode-text').on('input', updateShortcode);
  $('#mx-scu-copy-shortcode').on('click', function(e){
    e.preventDefault();
    var $btn  = $(this);
    var text  = $('#mx-scu-generated-shortcode-text').text();
    var tmp   = document.createElement('textarea');
    tmp.value = text;
    document.body.appendChild(tmp);
    tmp.select();
    document.execCommand('copy');
    document.body.removeChild(tmp);
    var orig = $btn.text();
    $btn.text('Copied!').prop('disabled', true);
    setTimeout(function(){
      $btn.text(orig).prop('disabled', false);
    }, 2000);
  });
  
  // ------------ coupon autocomplete via jQuery-UI ------------
function initCouponSearch() {
  var $input = $('#mx-scu-coupon');
  $input.autocomplete({
    source: function(request, response) {
      $.getJSON( ajaxUrl, {
        action: 'mx_scu_search_coupons',
        q:      request.term
      }).done(function(data) {
        response($.map(data, function(item){
          return { label: item.label, value: item.id };
        }));
      });
    },
    minLength: 2,
    select: function(event, ui){
      $input.val(ui.item.value);
      updateLink();
      return false;
    }
  })
  .autocomplete('instance')._renderItem = function(ul, item){
    return $('<li>')
      .append('<div>'+ item.label +'</div>')
      .appendTo(ul);
  };

  // if they clear it, rebuild link
  $input.on('input', function(){
    if ( ! $(this).val().trim() ) {
      updateLink();
    }
  });
}

// call it once
initCouponSearch();

// 1. Init color pickers
$('#mx-scu-qr-colorDark, #mx-scu-qr-colorLight').wpColorPicker({
  change: function(event, ui) {
    var input = this;
    setTimeout(function(){
      updateQRPreview();
    }, 10);
  },
  clear: function() {
    var def = this.id === 'mx-scu-qr-colorDark' ? mx_scu_data.qr_colorDark : mx_scu_data.qr_colorLight;
    $(this).val(def);  // Just set value, do not call .iris('color', ...)!
    setTimeout(function(){
      updateQRPreview();
    }, 10);
  }
});


// 2. QR size input
$('#mx-scu-qr-size').on('input change', updateQRPreview);

// 3. Initial render
updateQRPreview();


function updateQRPreview(){
  initQRGenerator({
    codeSelector:             '#mx-scu-generated-text',
    qrContainerSelector:      '#mx-scu-qr-container',
    snippetOutputSelector:    '#mx-scu-qr-embed-snippet',
    snippetContainerSelector: '#mx-scu-qr-snippet-container',
    outputTypeSelector:       '#mx-scu-qr-output-type',
    postId:                   mx_scu_data.post_id,
    size:       parseInt($('#mx-scu-qr-size').val(), 10) || mx_scu_data.qr_size,
    colorDark:  $('#mx-scu-qr-colorDark').val()  || mx_scu_data.qr_colorDark,
    colorLight: $('#mx-scu-qr-colorLight').val() || mx_scu_data.qr_colorLight
  });
}


  updateLink();
  updateShortcode();
  updateQRPreview();
  
  // Promo Message: toggle display mode visibility
  const $promoMessage = $('#mx-scu-promo-message-text');
  const $displayModeWrap = $('#mx-scu-display-mode-wrap');

  if ($promoMessage.length && $displayModeWrap.length) {
    function togglePromoDisplayMode() {
      const hasContent = $promoMessage.val().trim().length > 0;
      $displayModeWrap.toggle(hasContent);
    }

    $promoMessage.on('input', togglePromoDisplayMode);
    togglePromoDisplayMode(); // initial run
  }


}); 