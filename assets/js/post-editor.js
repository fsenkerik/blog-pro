(() => {
  const STYLE_ID = 'post-editor-enhancements-style';
  const BLOCK_OPTIONS = [
    { value: 'p', label: 'Normální' },
    { value: 'h1', label: 'Nadpis 1' },
    { value: 'h2', label: 'Nadpis 2' },
    { value: 'h3', label: 'Nadpis 3' }
  ];
  const FONT_SIZE_OPTIONS = ['14px', '16px', '18px', '22px', '28px', '36px'];

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .ed-toolbar{gap:8px 10px;padding:10px 12px;align-items:flex-start}
      .ed-toolbar .ed-select,.ed-toolbar .ed-btn{flex:0 0 auto}
      .ed-toolbar .ed-select{height:32px}
      .ed-toolbar .ed-select[data-role="block"]{min-width:132px}
      .ed-toolbar .ed-select[data-role="font"]{min-width:150px}
      .ed-toolbar .ed-select[data-role="font-size"]{min-width:92px}
      .ed-toolbar .toolbar-actions{margin-left:auto;display:flex;gap:4px}
      .ed-btn.is-active{background:var(--accent-soft)!important;color:var(--accent-2)!important;border-color:var(--accent)!important}
      .ed-content .editor-media{position:relative;max-width:100%;margin:12px auto;clear:both}
      .ed-content .editor-media img{display:block;width:100%;max-width:100%;height:auto;border-radius:10px}
      .ed-content .editor-media.is-selected{outline:2px solid rgba(102,126,234,.45);outline-offset:4px}
      .ed-stats{clear:both}
      .editor-image-tools{position:fixed;z-index:1200;display:none;flex-wrap:wrap;align-items:center;gap:6px;max-width:min(92vw,520px);padding:8px 10px;border-radius:12px;background:rgba(17,24,39,.94);box-shadow:0 12px 30px rgba(15,23,42,.28);backdrop-filter:blur(8px)}
      .editor-image-tools.is-visible{display:flex}
      .editor-image-tools button{border:none;border-radius:8px;padding:6px 8px;background:rgba(255,255,255,.08);color:#fff;font-size:11px;line-height:1;cursor:pointer;transition:background .15s ease}
      .editor-image-tools button:hover{background:rgba(255,255,255,.18)}
      @media (max-width: 980px){
        .ed-toolbar{gap:7px 8px}
        .ed-toolbar .toolbar-actions{width:100%;margin-left:0;justify-content:flex-end}
      }
      @media (max-width: 720px){
        .ed-toolbar .ed-select[data-role="font"]{min-width:132px}
        .ed-toolbar .ed-select[data-role="block"]{min-width:118px}
      }
    `;

    document.head.appendChild(style);
  }

  function getSelection() {
    return window.getSelection ? window.getSelection() : null;
  }

  function withSelection(selectionRange, fn) {
    if (selectionRange) {
      const selection = getSelection();
      selection.removeAllRanges();
      selection.addRange(selectionRange);
    }
    fn();
  }

  function getNodeElement(node) {
    if (!node) return null;
    return node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
  }

  function getActiveNode(editor) {
    const selection = getSelection();
    if (!selection || !selection.rangeCount) return null;
    const node = getNodeElement(selection.focusNode);
    if (!node || !editor.contains(node)) return null;
    return node;
  }

  function getClosestBlockTag(node) {
    const block = node?.closest('h1,h2,h3,h4,h5,h6,p,blockquote,li');
    if (!block) return 'p';
    if (block.tagName.toLowerCase() === 'li') {
      const parent = block.parentElement?.tagName?.toLowerCase();
      return parent === 'ol' || parent === 'ul' ? 'p' : 'p';
    }
    return block.tagName.toLowerCase();
  }

  function getClosestFigure(node) {
    return node?.closest('figure.editor-media') || null;
  }

  function isEmptyParagraph(node) {
    return !!node && node.tagName === 'P' && !node.textContent.trim() && node.querySelectorAll('img,video').length === 0;
  }

  function humanizeFontName(fontFamily) {
    if (!fontFamily) return 'Geist';
    const primary = fontFamily.split(',')[0].replace(/["']/g, '').trim();
    return primary || 'Geist';
  }

  function normalizeLegacyFontTags(editor) {
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
    editor.querySelectorAll('figure.editor-media, img').forEach((node) => {
      const figure = node.tagName === 'FIGURE' ? node : node.closest('figure.editor-media');
      const img = node.tagName === 'IMG' ? node : figure?.querySelector('img');
      if (!figure || !img) return;

      figure.classList.add('editor-media');
      figure.setAttribute('data-editor-media', 'image');
      figure.setAttribute('contenteditable', 'false');
      figure.draggable = false;

      if (!figure.style.maxWidth) figure.style.maxWidth = '100%';
      if (!figure.style.width) figure.style.width = '100%';
      if (!figure.style.margin) figure.style.margin = '12px auto';
      if (!figure.style.clear) figure.style.clear = 'both';

      img.draggable = false;
      if (!img.style.maxWidth) img.style.maxWidth = '100%';
      if (!img.style.width) img.style.width = '100%';
      if (!img.style.height) img.style.height = 'auto';
      if (!img.style.display) img.style.display = 'block';
      if (!img.style.borderRadius) img.style.borderRadius = '10px';
    });
  }

  function normalizeEditorMarkup(editor) {
    if (!editor) return;
    normalizeLegacyFontTags(editor);
    normalizeEditorMedia(editor);
  }

  function buildResponsiveImageHtml(url) {
    const safeUrl = String(url)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    return `<figure class="editor-media" data-editor-media="image" contenteditable="false" draggable="false" style="width:100%;max-width:100%;margin:12px auto;clear:both;"><img src="${safeUrl}" alt="" draggable="false" style="width:100%;max-width:100%;height:auto;display:block;border-radius:10px;"></figure><p><br></p>`;
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

  function wrapSelectionWithSpan(styleText) {
    const selection = getSelection();
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

  function ensureImageToolbar() {
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

  function positionImageToolbar(toolbar, figure) {
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

  function getFigureBlockNodes(figure) {
    const nodes = [figure];
    if (isEmptyParagraph(figure.nextElementSibling)) {
      nodes.push(figure.nextElementSibling);
    }
    return nodes;
  }

  function moveFigureBlock(figure, direction) {
    const nodes = getFigureBlockNodes(figure);
    let target = direction === 'up' ? figure.previousElementSibling : nodes[nodes.length - 1].nextElementSibling;

    while (target && isEmptyParagraph(target)) {
      target = direction === 'up' ? target.previousElementSibling : target.nextElementSibling;
    }

    if (!target) return;

    if (direction === 'up') {
      nodes.forEach((node) => target.before(node));
    } else {
      const anchor = target.nextElementSibling;
      nodes.forEach((node) => {
        if (anchor) {
          anchor.before(node);
        } else {
          target.parentNode.appendChild(node);
        }
      });
    }
  }

  function mountImageToolbar({ editorId, onChange }) {
    const editor = document.getElementById(editorId);
    if (!editor) return;

    const toolbar = ensureImageToolbar();
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

    editor.addEventListener('dragstart', (event) => {
      if (event.target.closest('figure.editor-media')) {
        event.preventDefault();
      }
    });

    editor.addEventListener('click', (event) => {
      const figure = event.target.closest('figure.editor-media');
      if (!figure || !editor.contains(figure)) {
        clearSelection();
        return;
      }

      event.preventDefault();
      if (selectedFigure) selectedFigure.classList.remove('is-selected');
      selectedFigure = figure;
      selectedFigure.classList.add('is-selected');
      toolbar.classList.add('is-visible');
      positionImageToolbar(toolbar, selectedFigure);
    });

    document.addEventListener('click', (event) => {
      if (!selectedFigure) return;
      if (toolbar.contains(event.target) || selectedFigure.contains(event.target)) return;
      clearSelection();
    });

    window.addEventListener('scroll', () => {
      if (selectedFigure && toolbar.classList.contains('is-visible')) {
        positionImageToolbar(toolbar, selectedFigure);
      }
    }, { passive: true });

    window.addEventListener('resize', () => {
      if (selectedFigure && toolbar.classList.contains('is-visible')) {
        positionImageToolbar(toolbar, selectedFigure);
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
        moveFigureBlock(selectedFigure, value === 'up' ? 'up' : 'down');
      } else if (action === 'remove') {
        getFigureBlockNodes(selectedFigure).forEach((node) => node.remove());
        clearSelection();
      }

      if (selectedFigure) {
        positionImageToolbar(toolbar, selectedFigure);
      }
      markChanged();
    });

    normalizeEditorMarkup(editor);
  }

  function createBlockSelect(applyBlock) {
    const select = document.createElement('select');
    select.id = 'blockSelect';
    select.className = 'ed-select';
    select.dataset.role = 'block';
    select.title = 'Styl odstavce';

    BLOCK_OPTIONS.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.value;
      option.textContent = item.label;
      select.appendChild(option);
    });

    select.addEventListener('mousedown', () => {
      if (typeof window.saveColorRange === 'function') window.saveColorRange();
      if (typeof window.saveColorRangeE === 'function') window.saveColorRangeE();
    });

    select.addEventListener('change', () => {
      if (typeof applyBlock === 'function') {
        applyBlock(select.value);
      }
    });

    return select;
  }

  function closestValueMatch(value, options) {
    const numeric = parseInt(value, 10);
    if (Number.isNaN(numeric)) return options[0] ?? '';
    return options.reduce((best, current) => {
      return Math.abs(parseInt(current, 10) - numeric) < Math.abs(parseInt(best, 10) - numeric) ? current : best;
    }, options[0] ?? '');
  }

  function syncFontSelect(select, fontFamily) {
    if (!select) return;
    const currentLabel = humanizeFontName(fontFamily);
    const options = [...select.options];
    const match = options.find((option) => option.value && fontFamily.toLowerCase().includes(option.value.split(',')[0].replace(/["']/g, '').toLowerCase()));

    if (match) {
      select.value = match.value;
    } else {
      select.selectedIndex = 0;
      select.options[0].textContent = currentLabel;
    }
  }

  function syncSizeSelect(select, fontSize) {
    if (!select) return;
    const normalized = closestValueMatch(fontSize, FONT_SIZE_OPTIONS);
    const options = [...select.options];
    const match = options.find((option) => option.value === normalized);

    if (match) {
      select.value = match.value;
    } else {
      select.selectedIndex = 0;
      select.options[0].textContent = normalized || 'Velikost';
    }
  }

  function updateCommandStates(editor, toolbar, blockSelect, fontSelect, sizeSelect) {
    const node = getActiveNode(editor);
    if (!node) return;

    blockSelect.value = getClosestBlockTag(node);

    const computed = window.getComputedStyle(node);
    syncFontSelect(fontSelect, computed.fontFamily || '');
    syncSizeSelect(sizeSelect, computed.fontSize || '');

    const commandMap = {
      bold: 'Tučně',
      italic: 'Kurzíva',
      underline: 'Podtržení',
      strikeThrough: 'Přeškrtnutí',
      subscript: 'Dolní index',
      superscript: 'Horní index',
      justifyLeft: 'Zarovnat vlevo',
      justifyCenter: 'Na střed',
      justifyRight: 'Zarovnat vpravo',
      insertOrderedList: 'Číslovaný seznam',
      insertUnorderedList: 'Odrážky'
    };

    Object.entries(commandMap).forEach(([command, title]) => {
      const button = toolbar.querySelector(`button[title="${title}"]`);
      if (!button) return;

      let active = false;
      try {
        active = document.queryCommandState(command);
      } catch (error) {
        active = false;
      }

      if (command.startsWith('justify')) {
        const align = (computed.textAlign || '').toLowerCase();
        active = (command === 'justifyLeft' && (!align || align === 'left' || align === 'start'))
          || (command === 'justifyCenter' && align === 'center')
          || (command === 'justifyRight' && (align === 'right' || align === 'end'));
      }

      button.classList.toggle('is-active', !!active);
      button.classList.toggle('active', !!active);
    });
  }

  function initToolbar({ editorId, toolbarSelector, onChange, applyBlock }) {
    ensureStyles();

    const editor = document.getElementById(editorId);
    const toolbar = document.querySelector(toolbarSelector);
    if (!editor || !toolbar) return;

    const oldBlockButton = toolbar.querySelector('button.ed-select');
    const blockSelect = createBlockSelect(applyBlock);
    if (oldBlockButton) {
      oldBlockButton.replaceWith(blockSelect);
    } else if (!toolbar.querySelector('select[data-role="block"]')) {
      toolbar.prepend(blockSelect);
    }

    const allSelects = [...toolbar.querySelectorAll('select.ed-select')];
    const selectsWithoutRole = allSelects.filter((select) => !select.dataset.role);
    const fontSelect = selectsWithoutRole.find((select) => [...select.options].some((option) => /arial|georgia|playfair|inter/i.test(option.textContent))) || toolbar.querySelector('select[data-role="font"]');
    const sizeSelect = selectsWithoutRole.find((select) => [...select.options].some((option) => option.value === '14px')) || toolbar.querySelector('select[data-role="font-size"]');

    if (fontSelect) fontSelect.dataset.role = 'font';
    if (sizeSelect) sizeSelect.dataset.role = 'font-size';

    const refresh = () => {
      normalizeEditorMarkup(editor);
      updateCommandStates(editor, toolbar, blockSelect, fontSelect, sizeSelect);
    };

    editor.addEventListener('mouseup', () => setTimeout(refresh, 0));
    editor.addEventListener('keyup', () => setTimeout(refresh, 0));
    editor.addEventListener('input', () => setTimeout(refresh, 0));
    editor.addEventListener('click', () => setTimeout(refresh, 0));
    document.addEventListener('selectionchange', () => {
      const activeNode = getActiveNode(editor);
      if (activeNode || getClosestFigure(getNodeElement(getSelection()?.focusNode))) {
        refresh();
      }
    });

    toolbar.querySelectorAll('button.ed-btn').forEach((button) => {
      button.addEventListener('click', () => {
        setTimeout(() => {
          refresh();
          if (typeof onChange === 'function') onChange();
        }, 0);
      });
    });

    [blockSelect, fontSelect, sizeSelect].filter(Boolean).forEach((select) => {
      select.addEventListener('change', () => {
        setTimeout(() => {
          refresh();
          if (typeof onChange === 'function') onChange();
        }, 0);
      });
    });

    refresh();
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
    initToolbar,
    mountImageToolbar,
    normalizeEditorMarkup,
    prepareImageForUpload
  };
})();
