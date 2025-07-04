;(function(window){
  function initQRGenerator({
    codeSelector,
    qrContainerSelector,
    snippetOutputSelector,
    outputTypeSelector,
    snippetContainerSelector,
    postId = 0,
    width  = 128,
    height = 128,
    level  = QRCode.CorrectLevel.H
  }) {
    const codeEl       = document.querySelector(codeSelector);
    const qrWrap       = document.querySelector(qrContainerSelector);
    const snippet      = document.querySelector(snippetOutputSelector);
    const outputSel    = document.querySelector(outputTypeSelector);
    const snippetWrap  = document.querySelector(snippetContainerSelector);
    let dataUri = '';

    function render() {
      const url = codeEl.textContent.trim();
      if (!url) return;
      qrWrap.innerHTML = '';
      new QRCode(qrWrap, { text: url, width, height, correctLevel: level });
      const canvas = qrWrap.querySelector('canvas');
      dataUri = canvas.toDataURL('image/png');
      qrWrap.innerHTML = `<img src="${dataUri}" alt="QR Code">`;
      // reset UI
      snippet.value = '';
      snippetWrap.style.display = 'none';
      outputSel.value = 'datauri';
    }

    function showEmbed() {
      snippet.value = `<img src="${dataUri}" alt="QR Code">`;
      snippetWrap.style.display = 'block';
    }

    function doDownload() {
      const a = document.createElement('a');
      a.href = dataUri;
      const id = postId > 0 ? postId : Date.now();
      a.download = `qr-code-${id}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }

    outputSel.addEventListener('change', e => {
      const mode = e.target.value;
      snippetWrap.style.display = 'none';
      switch (mode) {
        case 'datauri':
          qrWrap.innerHTML = `<img src="${dataUri}" alt="QR Code">`;
          break;
        case 'embed':
          showEmbed();
          break;
        case 'download':
          doDownload();
          break;
      }
    });

    const mo = new MutationObserver(render);
    mo.observe(codeEl, { childList: true, subtree: true });

    render();
  }

  window.initQRGenerator = initQRGenerator;
})(window);
