(() => {
  const STYLE_ID = 'post-editor-image-tools-style';

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .editor-media{position:relative;max-width:100%;margin:12px auto;clear:both}
      .editor-media img{display:block;width:100%;max-width:100%;height:auto;border-radius:10px}
      .editor-media.is-selected{outline:2px solid rgba(102,126,234,.45);outline-offset:4px}
      .editor-image-tools{position:fixed;z-index:1200;display:none;align-items:center;gap:6px;padding:8px 10px;border-radius:12px;background:rgba(17,24,39,.92);box-shadow:0 12px 30px rgba(15,23,42,.28);backdrop-filter:blur(8px)}
      .editor-image-tools.is-visible{display:flex}
      .editor-image-tools button{border:none;border-radius:8px;padding:6px 8px;background:rgba(255,255,255,.08);color:#fff;font-size:11px;line-height:1;cursor:pointer;transition:background .15s ease}
      .editor-image-tools button:hover{background:rgba(255,255,255,.18)}
    `;
    document.head.appendChild(style);
  }

  function withSelection(selectionRange, fn) {
    if (selectionRange) {
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(selectionRange);
    }
    fn();
  }

  function wrapSelectionWithSpan(styleText) {
    const selection = window.getSelection();
    if (!selection || !selection.rangeCount || selection.isCollapsed) return false;

    const range = selection.getRangeAt(0);
    const span = document.createElement('span');
    span.setAttribute('style', styleText);

    try {
      range.surroundContents(span);
    } catch (error) {
      const fragment = range.extractContents();
      span.appendChild(fragment);
      range.insertNode(span);
    }

    selection.removeAllRanges();
    const nextRange = document.createRange();
    nextRange.selectNodeContents(span);
    selection.addRange(nextRange);
    return true;
  }

  function normalizeLegacyFontTags(editor) {
    if (!editor) return;

    editor.querySelectorAll('font').forEach((fontEl) => {
      const span = document.createElement('span');
      const styles = [];
      const face = fontEl.getAttribute('face');
      const size = fontEl.getAttribute('size');
      const sizeMap = {
        '1': '12px',
        '2': '14px',
        '3': '16px',
        '4': '18px',
        '5': '22px',
        '6': '28px',
        '7': '36px'
      };

      if (face) styles.push(`font-family:${face}`);
      if (size && sizeMap[size]) styles.push(`font-size:${sizeMap[size]}`);
      if (styles.length) span.setAttribute('style', styles.join(';'));

      while (fontEl.firstChild) span.appendChild(fontEl.firstChild);
      fontEl.replaceWith(span);
    });
  }

  function normalizeEditorMedia(editor) {
    if (!editor) return;

    editor.querySelectorAll('img').forEach((img) => {
      const figure = img.closest('figure.editor-media');
      if (figure) {
        if (!figure.style.maxWidth) figure.style.maxWidth = '100%';
        if (!figure.style.width) figure.style.width = '100%';
        if (!figure.style.margin) figure.style.margin = '12px auto';
        if (!figure.style.clear) figure.style.clear = 'both';
      }

      if (!img.style.maxWidth) img.style.maxWidth = '100%';
      if (!img.style.width) img.style.width = '100%';
      if (!img.style.height) img.style.height = 'auto';
      if (!img.style.display) img.style.display = 'block';
      if (!img.style.borderRadius) img.style.borderRadius = '10px';
    });
  }

  function normalizeEditorMarkup(editor) {
    normalizeLegacyFontTags(editor);
    normalizeEditorMedia(editor);
  }

  function buildResponsiveImageHtml(url) {
    const safeUrl = String(url)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    return `<figure class="editor-media" data-editor-media="image" style="width:100%;max-width:100%;margin:12px auto;clear:both;"><img src="${safeUrl}" alt="" style="width:100%;max-width:100%;height:auto;display:block;border-radius:10px;"></figure><p><br></p>`;
  }

  async function loadImage(file) {
    const objectUrl = URL.createObjectURL(file);
    try {
      const image = await new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = objectUrl;
      });
      return image;
    } finally {
      URL.revokeObjectURL(objectUrl);
    }
  }

  async function prepareImageForUpload(file, options = {}) {
    if (!file || !file.type.startsWith('image/') || file.type === 'image/gif') {
      return file;
    }

    const sizeThreshold = options.sizeThreshold ?? 350 * 1024;
    if (file.size <= sizeThreshold) {
      return file;
    }

    const image = await loadImage(file);
    const maxDimension = options.maxDimension ?? 2200;
    const ratio = Math.min(1, maxDimension / Math.max(image.width, image.height));
    const targetWidth = Math.max(1, Math.round(image.width * ratio));
    const targetHeight = Math.max(1, Math.round(image.height * ratio));

    const canvas = document.createElement('canvas');
    canvas.width = targetWidth;
    canvas.height = targetHeight;
    const ctx = canvas.getContext('2d', { alpha: true });
    ctx.drawImage(image, 0, 0, targetWidth, targetHeight);

    const outputType = file.type === 'image/png' ? 'image/png' : 'image/webp';
    const quality = options.quality ?? 0.82;
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, outputType, quality));

    if (!blob || blob.size >= file.size * 0.95) {
      return file;
    }

    const nextName = file.name.replace(/\.[^.]+$/, outputType === 'image/png' ? '.png' : '.webp');
    return new File([blob], nextName, { type: outputType, lastModified: Date.now() });
  }

  function ensureToolbar() {
    ensureStyles();
    let toolbar = document.querySelector('.editor-image-tools');
    if (toolbar) return toolbar;

    toolbar = document.createElement('div');
    toolbar.className = 'editor-image-tools';
    toolbar.innerHTML = `
      <button type="button" data-action="size" data-value="33">S</button>
      <button type="button" data-action="size" data-value="50">M</button>
      <button type="button" data-action="size" data-value="75">L</button>
      <button type="button" data-action="size" data-value="100">Full</button>
      <button type="button" data-action="align" data-value="left">Vlevo</button>
      <button type="button" data-action="align" data-value="center">Střed</button>
      <button type="button" data-action="align" data-value="right">Vpravo</button>
      <button type="button" data-action="move" data-value="up">Nahoru</button>
      <button type="button" data-action="move" data-value="down">Dolů</button>
      <button type="button" data-action="remove">Smazat</button>
    `;
    document.body.appendChild(toolbar);
    return toolbar;
  }

  function positionToolbar(toolbar, figure) {
    const rect = figure.getBoundingClientRect();
    toolbar.style.top = `${Math.max(12, rect.top - 54)}px`;
    toolbar.style.left = `${Math.max(12, Math.min(window.innerWidth - toolbar.offsetWidth - 12, rect.left))}px`;
  }

  function applyImageAlignment(figure, value) {
    figure.style.float = 'none';
    figure.style.marginTop = '12px';
    figure.style.marginBottom = '12px';

    if (value === 'left') {
      figure.style.marginLeft = '0';
      figure.style.marginRight = '18px';
      figure.style.float = 'left';
    } else if (value === 'right') {
      figure.style.marginLeft = '18px';
      figure.style.marginRight = '0';
      figure.style.float = 'right';
    } else {
      figure.style.marginLeft = 'auto';
      figure.style.marginRight = 'auto';
    }
  }

  function mountImageToolbar({ editorId, onChange }) {
    const editor = document.getElementById(editorId);
    if (!editor) return;

    const toolbar = ensureToolbar();
    let selectedFigure = null;

    const clearSelection = () => {
      if (selectedFigure) selectedFigure.classList.remove('is-selected');
      selectedFigure = null;
      toolbar.classList.remove('is-visible');
    };

    const markChanged = () => {
      normalizeEditorMarkup(editor);
      if (typeof onChange === 'function') onChange();
    };

    editor.addEventListener('click', (event) => {
      const figure = event.target.closest('figure.editor-media');
      if (!figure || !editor.contains(figure)) {
        clearSelection();
        return;
      }

      if (selectedFigure) selectedFigure.classList.remove('is-selected');
      selectedFigure = figure;
      selectedFigure.classList.add('is-selected');
      toolbar.classList.add('is-visible');
      positionToolbar(toolbar, selectedFigure);
    });

    document.addEventListener('click', (event) => {
      if (!selectedFigure) return;
      if (toolbar.contains(event.target) || selectedFigure.contains(event.target)) return;
      clearSelection();
    });

    window.addEventListener('scroll', () => {
      if (selectedFigure && toolbar.classList.contains('is-visible')) {
        positionToolbar(toolbar, selectedFigure);
      }
    }, { passive: true });

    window.addEventListener('resize', () => {
      if (selectedFigure && toolbar.classList.contains('is-visible')) {
        positionToolbar(toolbar, selectedFigure);
      }
    });

    toolbar.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-action]');
      if (!button || !selectedFigure) return;

      const action = button.dataset.action;
      const value = button.dataset.value;

      if (action === 'size') {
        selectedFigure.style.width = `${value}%`;
      } else if (action === 'align') {
        applyImageAlignment(selectedFigure, value);
      } else if (action === 'move') {
        const sibling = value === 'up' ? selectedFigure.previousElementSibling : selectedFigure.nextElementSibling;
        if (sibling) {
          if (value === 'up') {
            sibling.before(selectedFigure);
          } else {
            sibling.after(selectedFigure);
          }
        }
      } else if (action === 'remove') {
        const next = selectedFigure.nextElementSibling;
        if (next && next.tagName === 'P' && !next.textContent.trim()) {
          next.remove();
        }
        selectedFigure.remove();
        clearSelection();
      }

      if (selectedFigure) {
        positionToolbar(toolbar, selectedFigure);
      }
      markChanged();
    });

    normalizeEditorMarkup(editor);
  }

  window.PostEditorUtils = {
    applyFontSize(sizePx, selectionRange) {
      if (!sizePx) return false;
      let applied = false;
      withSelection(selectionRange, () => {
        applied = wrapSelectionWithSpan(`font-size:${sizePx};line-height:1.65`);
      });
      return applied;
    },
    buildResponsiveImageHtml,
    mountImageToolbar,
    normalizeEditorMarkup,
    prepareImageForUpload
  };
})();
