jQuery(function($){
  const EXPECTED = ['scu name','products','coupon'];

  $('#scu-export-btn').on('click', function(e){
    e.preventDefault();
    const url = `${SCU_Ajax.ajax_url}?action=scu_export_csv&_wpnonce=${SCU_Ajax.export_nonce}`;
    window.location = url;
  });

  function validateHeader(file){
    const reader = new FileReader();
    reader.onload = function(e){
      let header = e.target.result.split(/\r?\n/)[0]
                      .toLowerCase()
                      .trim();
      const cols = header.split(',').map(c =>
        c.trim().replace(/^["']|["']$/g, '')
      );
      const ok = EXPECTED.every((h,i) => cols[i] === h);
      $('#scu-import-submit').prop('disabled', !ok);
      $('#scu-import-result').text(
        ok ? 'header OK—ready to upload' : 'invalid header row'
      );
    };
    reader.readAsText(file.slice(0,200));
  }

  $('#scu-dropzone')
    .on('dragover', function(e){
      e.preventDefault();
      e.originalEvent.dataTransfer.dropEffect = 'copy';
    })
    .on('drop', function(e){
      e.preventDefault();
      const files = e.originalEvent.dataTransfer.files;
      if ( files.length ) {
        $('#scu-import-file')[0].files = files;
        validateHeader(files[0]);
      }
    });

  $('#scu-import-file').on('change', function(){
    if ( this.files.length ) {
      validateHeader(this.files[0]);
    }
  });

  $('#scu-import-submit').on('click', function(){
    const inp = $('#scu-import-file')[0];
    if ( ! inp.files.length ) {
      alert('Please choose a CSV file.');
      return;
    }
    const fd = new FormData();
    fd.append('action','scu_import_csv_queue');
    fd.append('_wpnonce', SCU_Ajax.import_nonce);
    fd.append('scu_csv', inp.files[0]);

    $('#scu-import-result').text('Queuing…');
    $('#scu-import-progress').show().val(0);

    $.ajax({
      url: SCU_Ajax.ajax_url,
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false
    })
    .done(function(res){
      const job = res.data.job_id;
      pollProgress(job);
    })
    .fail(function(xhr){
      console.error('Import error:', xhr.responseText);
      $('#scu-import-result').text('Error: ' + (xhr.responseText||'unknown'));
    });
  });

  function pollProgress(job){
    $.getJSON(SCU_Ajax.ajax_url, {
      action: 'scu_import_progress',
      job_id: job
    }, function(res){
      if ( ! res.success ) {
        $('#scu-import-result').text('Error polling progress');
        return;
      }
      const d   = res.data;
      const pct = d.total ? Math.round(100 * d.completed / d.total) : 0;
      $('#scu-import-progress').val(pct);

      $('#scu-import-result').text(
        `${d.completed} of ${d.total} processed…`
      );

      if ( ! d.done ) {
        setTimeout(function(){ pollProgress(job); }, 1000);
      } else {
        $('#scu-import-result').html('<pre>' + d.message + '</pre>');
        $('#scu-import-progress').hide();

        setTimeout(function(){
          if ( typeof tb_remove === 'function' ) {
            tb_remove();
          }
          location.reload();
        }, 2500);
      }
    });
  }
});
