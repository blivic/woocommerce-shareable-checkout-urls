jQuery(function($){
  $(document).on('click','.mx-scu-send-email',function(e){
    e.preventDefault();
    var $link = $(this);
    var id    = $link.data('id');

    $link.siblings('.mx-scu-email-status, .spinner').remove();

    $('#mx-scu-email-error').text('');
    $('#mx-scu-email-to, #mx-scu-email-cc, #mx-scu-email-bcc').val('');
    $('#mx-scu-email-override-toggle').prop('checked', false);
    $('#mx-scu-email-override-fields').hide();
    $('#mx-scu-email-subject, #mx-scu-email-body').val('');

    $('#mx-scu-email-override-toggle')
      .off('change')
      .on('change', function(){
        $('#mx-scu-email-override-fields').toggle(this.checked);
      });

    $('#mx-scu-email-modal').dialog({
      modal: true,
      width: 400,     
      buttons: [
        {
          text: 'Send',
          class: 'button button-primary',
          click: function(){
            var $dlg   = $(this),
                $btn   = $dlg.parent().find('.ui-dialog-buttonpane button:contains("Send")'),
                toRaw  = $('#mx-scu-email-to').val().trim(),
                ccRaw  = $('#mx-scu-email-cc').val().trim(),
                bccRaw = $('#mx-scu-email-bcc').val().trim(),
                useOverride = $('#mx-scu-email-override-toggle').is(':checked'),
                subjOverride = $('#mx-scu-email-subject').val().trim(),
                bodyOverride = $('#mx-scu-email-body').val().trim();

            if ( ! toRaw ) {
              return $('#mx-scu-email-error')
                .text( 'Please enter at least one recipient.' );
            }

            var toList   = toRaw.split(/[\s,;]+/).filter(Boolean),
                ccList   = ccRaw ? ccRaw.split(/[\s,;]+/).filter(Boolean) : [],
                bccList  = bccRaw ? bccRaw.split(/[\s,;]+/).filter(Boolean) : [],
                allAddrs = toList.concat(ccList, bccList),
                invalid  = allAddrs.filter(function(e){ return !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); });

            if ( invalid.length ) {
              return $('#mx-scu-email-error')
                .text( 'Invalid email(s): ' + invalid.join(', ') );
            }

            var $spinner = $('<span class="spinner is-active" style="margin-left:6px;vertical-align:middle;"></span>');
            $link.after( $spinner );

            $btn.prop('disabled', true).addClass('disabled');

            $.post(mx_scu_data.ajax_url, {
              action:   'mx_scu_send_email',
              to:       toList.join(','),
              cc:       ccList.join(','),
              bcc:      bccList.join(','),
              override: useOverride ? 1 : 0,
              subject:  useOverride ? subjOverride : '',
              body:     useOverride ? bodyOverride : '',
              id:       id,
              nonce:    mx_scu_data.email_nonce
            })
            .always(function(){
              $spinner.remove();
              $btn.prop('disabled', false).removeClass('disabled');
            })
            .done(function(resp){
              if ( resp.success ) {
                var $ok = $('<span class="dashicons dashicons-yes" style="color:green;margin-left:6px;vertical-align:middle;"></span>');
                $link.after( $ok );
                setTimeout(function(){
                  $ok.fadeOut(300, function(){ $(this).remove(); });
                }, 2000);
                $dlg.dialog('close');
              } else {
                $('#mx-scu-email-error').text( resp.data || 'Error sending email' );
              }
            });
          }
        },
        {
          text: 'Cancel',
          click: function(){
            $(this).dialog('close');
          }
        }
      ]
    });
  });
});
