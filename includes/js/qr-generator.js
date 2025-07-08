(function(window){
  function QRGeneratorInstance(opts) {
    this.codeSelector             = opts.codeSelector;
    this.qrContainerSelector      = opts.qrContainerSelector;
    this.snippetOutputSelector    = opts.snippetOutputSelector;
    this.outputTypeSelector       = opts.outputTypeSelector;
    this.snippetContainerSelector = opts.snippetContainerSelector;
    this.postId                   = opts.postId || 0;

    this.codeEl      = document.querySelector(this.codeSelector);
    this.qrWrap      = document.querySelector(this.qrContainerSelector);
    this.snippet     = document.querySelector(this.snippetOutputSelector);
    this.outputSel   = document.querySelector(this.outputTypeSelector);
    this.snippetWrap = document.querySelector(this.snippetContainerSelector);

    this.size       = opts.size || 200;
    this.colorDark  = opts.colorDark || '#000000';
    this.colorLight = opts.colorLight || '#ffffff';
    this.level      = opts.level || 'H';

    this.lastSVG = ""; // hold the raw SVG string for downloads

    this.updateOptions = function(opts) {
      if (!opts) return;
      this.size = opts.size || this.size;
      this.colorDark = opts.colorDark || this.colorDark;
      this.colorLight = opts.colorLight || this.colorLight;
    };

    this.render = () => {
      const url = this.codeEl.textContent.trim();
      if (!url) return;
      const qrSVG = new QRCode({
        content: url,
        width: this.size,
        height: this.size,
        color: this.colorDark,
        background: this.colorLight,
        ecl: this.level,
        padding: 0,
        container: "svg-viewbox",
        join: true
		});
      this.lastSVG = qrSVG.svg(); 
      this.qrWrap.innerHTML = this.lastSVG;
      const svgEl = this.qrWrap.querySelector('svg');
      if (svgEl) {
        svgEl.setAttribute('width', this.size + 'px');
        svgEl.setAttribute('height', this.size + 'px');
        svgEl.style.width = this.size + 'px';
        svgEl.style.height = this.size + 'px';
        svgEl.style.display = 'block';
      }
      this.snippet.value = '';
      this.snippetWrap.style.display = 'none';
    };

    this.doDownloadSvg = () => {
      const blob = new Blob([this.lastSVG], { type: 'image/svg+xml' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `qr-code-${this.postId || Date.now()}.svg`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    };

    this.doDownloadPng = () => {
      const svgEl = this.qrWrap.querySelector('svg');
      const svgData = new XMLSerializer().serializeToString(svgEl);
      const canvas = document.createElement('canvas');
      canvas.width = this.size;
      canvas.height = this.size;
      const ctx = canvas.getContext('2d');
      const img = new Image();
      const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
      const url = URL.createObjectURL(svgBlob);
      img.onload = function(){
        ctx.drawImage(img, 0, 0);
        URL.revokeObjectURL(url);
        const pngUri = canvas.toDataURL('image/png');
        const a = document.createElement('a');
        a.href = pngUri;
        a.download = `qr-code-${this.postId || Date.now()}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }.bind(this);
      img.src = url;
    };

    this.showEmbed = () => {
      const svgContent = this.qrWrap.innerHTML;
      this.snippet.value = svgContent;
      this.snippetWrap.style.display = 'block';
    };

    if (!this.outputSel._qrDownloadHandlerAdded) {
      this.outputSel._qrDownloadHandlerAdded = true;
      this.outputSel.addEventListener('change', (e) => {
        this.snippetWrap.style.display = 'none';
        switch (e.target.value) {
          case 'embed':
            this.render();
            this.showEmbed();
            break;
          case 'download':
            this.render();
            this.doDownloadPng();
            break;
          case 'download_svg':
            this.render();
            this.doDownloadSvg();
            break;
          default:
            this.render();
        }
      });
    }

    if (!this.codeEl._qrMutationObserver) {
      this.codeEl._qrMutationObserver = new MutationObserver(() => this.render());
      this.codeEl._qrMutationObserver.observe(this.codeEl, { childList: true, subtree: true });
    }

    this.render();
  }

  window.initQRGenerator = function(opts) {
    if (!window.__mxQrInstance) {
      window.__mxQrInstance = new QRGeneratorInstance(opts);
    } else {
      window.__mxQrInstance.updateOptions(opts);
      window.__mxQrInstance.render();
    }
  };
})(window);
