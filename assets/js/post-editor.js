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
      .ed-content .editor-file-block{display:block;margin:14px 0;clear:both}
      .ed-content .editor-file{display:flex;align-items:center;gap:14px;padding:14px 16px;border:1px solid var(--border);border-radius:12px;background:var(--card-2);text-decoration:none;color:var(--ink)}
      .ed-content .editor-file-icon{width:42px;height:42px;border-radius:10px;background:var(--accent-soft);color:var(--accent-2);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:11px;font-weight:700;text-transform:uppercase;flex-shrink:0}
      .ed-content .editor-file-body{min-width:0;flex:1}
      .ed-content .editor-file-name{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
      .ed-content .editor-media.is-selected,.ed-content .editor-file-block.is-selected{outline:2px solid rgba(102,126,234,.45);outline-offset:4px}
      .ed-stats{clear:both}
      .editor-upload-progress{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(255,255,255,.9);backdrop-filter:blur(6px);border:1px solid rgba(102,126,234,.16);border-radius:14px;z-index:35}
      .editor-upload-progress.is-floating{position:fixed;inset:auto 18px 18px auto;width:min(320px,calc(100vw - 36px));box-shadow:0 20px 60px rgba(15,23,42,.18)}
      .editor-upload-card{width:min(280px,100%);padding:16px 16px 14px;border-radius:14px;background:linear-gradient(180deg,#fff,#f8faff);box-shadow:0 10px 35px rgba(102,126,234,.12)}
      .editor-upload-label{font-size:12px;font-weight:600;color:var(--ink);margin-bottom:8px}
      .editor-upload-meta{display:flex;justify-content:space-between;align-items:center;gap:12px;font-family:var(--mono);font-size:11px;color:var(--muted);margin-bottom:8px}
      .editor-upload-bar{height:8px;border-radius:999px;background:rgba(102,126,234,.12);overflow:hidden}
      .editor-upload-bar > span{display:block;height:100%;width:0;background:linear-gradient(90deg,#667eea,#7c3aed);border-radius:inherit;transition:width .18s ease}
      .editor-upload-stage{margin-top:8px;font-size:11.5px;color:var(--muted)}
      .editor-image-tools{position:fixed;z-index:1200;display:none;flex-wrap:wrap;align-items:center;gap:6px;max-width:min(92vw,520px);padding:8px 10px;border-radius:12px;background:rgba(17,24,39,.94);box-shadow:0 12px 30px rgba(15,23,42,.28);backdrop-filter:blur(8px)}
      .editor-image-tools.is-visible{display:flex}
      .editor-image-tools button{border:none;border-radius:8px;padding:6px 8px;background:rgba(255,255,255,.08);color:#fff;font-size:11px;line-height:1;cursor:pointer;transition:background .15s ease}
      .editor-image-tools button[hidden]{display:none}
      .editor-image-tools button:hover{background:rgba(255,255,255,.18)}
      .editor-alert{position:fixed;inset:0;z-index:1600;display:flex;align-items:center;justify-content:center;background:rgba(17,24,39,.42);backdrop-filter:blur(4px)}
      .editor-alert-box{width:min(560px,calc(100vw - 32px));padding:26px;border-radius:18px;background:linear-gradient(180deg,#fff,#fbfcff);border:1px solid rgba(148,163,184,.28);box-shadow:0 28px 80px rgba(15,23,42,.24)}
      .editor-alert-kicker{display:inline-flex;align-items:center;gap:7px;margin-bottom:10px;padding:5px 9px;border-radius:999px;background:rgba(102,126,234,.1);color:var(--accent-2);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
      .editor-alert-title{font-size:20px;font-weight:750;color:var(--ink);margin-bottom:8px}
      .editor-alert-body{font-size:14px;line-height:1.55;color:var(--body);margin-bottom:18px}
      .editor-alert-formats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:16px 0 20px}
      .editor-alert-format{padding:12px;border:1px solid rgba(148,163,184,.24);border-radius:13px;background:#fff}
      .editor-alert-format-title{font-size:12px;font-weight:700;color:var(--ink);margin-bottom:8px}
      .editor-alert-chips{display:flex;flex-wrap:wrap;gap:6px}
      .editor-alert-chip{padding:4px 7px;border-radius:7px;background:var(--accent-soft);color:var(--accent-2);font-family:var(--mono);font-size:10.5px;font-weight:700;text-transform:uppercase}
      .editor-alert-note{margin-top:-4px;margin-bottom:18px;font-size:12px;color:var(--muted)}
      .editor-alert-btn{width:100%;padding:11px 14px;border:0;border-radius:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 10px 24px rgba(102,126,234,.22)}
      @media (max-width: 620px){.editor-alert-formats{grid-template-columns:1fr}.editor-alert-box{padding:22px}}
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

  function getClosestMediaBlock(node) {
    return node?.closest('figure.editor-media,.editor-file-block') || null;
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

  function wrapStandaloneMedia(editor, selector, createWrapper) {
    editor.querySelectorAll(selector).forEach((node) => {
      if (node.closest('figure.editor-media,.editor-file-block')) return;
      const wrapper = createWrapper(node);
      node.before(wrapper);
      wrapper.appendChild(node);
      const next = wrapper.nextSibling;
      if (!next || next.nodeName !== 'P') {
        wrapper.after(document.createElement('p'));
      }
    });
  }

  function normalizeEditorMedia(editor) {
    wrapStandaloneMedia(editor, 'img,video,audio', (node) => {
      const figure = document.createElement('figure');
      figure.className = 'editor-media';
      figure.dataset.editorMedia = node.tagName === 'IMG' ? 'image' : node.tagName.toLowerCase();
      return figure;
    });

    wrapStandaloneMedia(editor, 'a.editor-file', () => {
      const block = document.createElement('div');
      block.className = 'editor-file-block';
      block.dataset.editorMedia = 'file';
      return block;
    });

    editor.querySelectorAll('figure.editor-media').forEach((figure) => {
      const media = figure.querySelector('img,video,audio');
      if (!media) return;

      figure.classList.add('editor-media');
      figure.setAttribute('data-editor-media', media.tagName === 'IMG' ? 'image' : media.tagName.toLowerCase());
      figure.setAttribute('contenteditable', 'false');
      figure.draggable = false;
      figure.setAttribute('draggable', 'false');

      if (!figure.style.maxWidth) figure.style.maxWidth = '100%';
      if (!figure.style.width) figure.style.width = media.tagName === 'IMG' ? '50%' : '100%';
      if (!figure.dataset.size) figure.dataset.size = (figure.style.width || '50%').replace('%', '');
      if (!figure.style.margin) figure.style.margin = '12px auto';
      if (!figure.style.clear) figure.style.clear = 'both';

      media.draggable = false;
      media.setAttribute('draggable', 'false');
      if (!media.style.maxWidth) media.style.maxWidth = '100%';
      if (!media.style.width) media.style.width = '100%';
      if (!media.style.display) media.style.display = 'block';
      if (media.tagName === 'IMG' && !media.style.height) media.style.height = 'auto';
      if (!media.style.borderRadius && media.tagName !== 'AUDIO') media.style.borderRadius = '10px';
    });

    editor.querySelectorAll('.editor-file-block').forEach((block) => {
      block.classList.add('editor-file-block');
      block.setAttribute('data-editor-media', 'file');
      block.setAttribute('contenteditable', 'false');
      block.draggable = false;
      block.setAttribute('draggable', 'false');
      const link = block.querySelector('a.editor-file');
      if (link) {
        link.setAttribute('draggable', 'false');
      }
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

    return `<figure class="editor-media" data-editor-media="image" data-size="50" contenteditable="false" draggable="false" style="width:50%;max-width:100%;margin:12px auto;clear:both;"><img src="${safeUrl}" alt="" draggable="false" style="width:100%;max-width:100%;height:auto;display:block;border-radius:10px;"></figure><p><br></p>`;
  }

  const ALLOWED_MEDIA_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'mp4', 'webm', 'mov', 'mp3', 'wav', 'ogg', 'm4a'];
  const ALLOWED_MEDIA_GROUPS = [
    { title: 'Obrázky', formats: ['JPG', 'PNG', 'GIF', 'WebP'] },
    { title: 'Dokumenty', formats: ['PDF', 'DOC', 'DOCX', 'XLS', 'XLSX', 'PPT', 'PPTX', 'TXT', 'CSV'] },
    { title: 'Video', formats: ['MP4', 'WebM', 'MOV'] },
    { title: 'Zvuk', formats: ['MP3', 'WAV', 'OGG', 'M4A'] }
  ];

  function getFileExtension(name) {
    return String(name || '').split('.').pop().toLowerCase();
  }

  function getMediaKind(fileOrData) {
    const mime = String(fileOrData?.type || fileOrData?.mime_type || '').toLowerCase();
    const ext = getFileExtension(fileOrData?.name || fileOrData?.original_name || fileOrData?.filename);
    if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'image';
    if (mime.startsWith('video/') || ['mp4', 'webm', 'mov'].includes(ext)) return 'video';
    if (mime.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) return 'audio';
    return 'document';
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function validateMediaFile(file) {
    const ext = getFileExtension(file?.name);
    if (!file || !ALLOWED_MEDIA_EXTENSIONS.includes(ext)) {
      return {
        ok: false,
        code: 'unsupported-format',
        message: 'Tento formát není povolený.'
      };
    }
    if (file.size > 50 * 1024 * 1024) {
      return { ok: false, message: 'Soubor je příliš velký. Maximální velikost je 50 MB.' };
    }
    return { ok: true };
  }

  function showUploadAlert(message, title = 'Soubor nelze nahrát') {
    const alert = document.createElement('div');
    alert.className = 'editor-alert';
    alert.innerHTML = `
      <div class="editor-alert-box">
        <div class="editor-alert-kicker">Upload</div>
        <div class="editor-alert-title">${escapeHtml(title)}</div>
        <div class="editor-alert-body">${escapeHtml(message)}</div>
        <button type="button" class="editor-alert-btn">Rozumím</button>
      </div>
    `;
    alert.querySelector('button').addEventListener('click', () => alert.remove());
    alert.addEventListener('click', event => {
      if (event.target === alert) alert.remove();
    });
    document.body.appendChild(alert);
  }

  function showAllowedFormatAlert() {
    const alert = document.createElement('div');
    alert.className = 'editor-alert';
    const groups = ALLOWED_MEDIA_GROUPS.map((group) => `
      <div class="editor-alert-format">
        <div class="editor-alert-format-title">${escapeHtml(group.title)}</div>
        <div class="editor-alert-chips">${group.formats.map((format) => `<span class="editor-alert-chip">${escapeHtml(format)}</span>`).join('')}</div>
      </div>
    `).join('');
    alert.innerHTML = `
      <div class="editor-alert-box">
        <div class="editor-alert-kicker">Nepovolený formát</div>
        <div class="editor-alert-title">Soubor nelze nahrát</div>
        <div class="editor-alert-body">Vybraný typ souboru zatím není v systému povolený. Nahrajte prosím jeden z těchto formátů:</div>
        <div class="editor-alert-formats">${groups}</div>
        <div class="editor-alert-note">Maximální velikost jednoho souboru je 50 MB.</div>
        <button type="button" class="editor-alert-btn">Rozumím</button>
      </div>
    `;
    alert.querySelector('button').addEventListener('click', () => alert.remove());
    alert.addEventListener('click', event => {
      if (event.target === alert) alert.remove();
    });
    document.body.appendChild(alert);
  }

  function buildMediaHtml(data) {
    const url = String(data.url || '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
    const name = String(data.original_name || data.filename || 'soubor')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
    const ext = getFileExtension(name).toUpperCase() || 'FILE';
    const kind = getMediaKind(data);

    if (kind === 'image') return buildResponsiveImageHtml(url);
    if (kind === 'video') return `<figure class="editor-media" data-editor-media="video" contenteditable="false" draggable="false" style="width:100%;max-width:100%;margin:12px auto;clear:both;"><video src="${url}" controls draggable="false" style="width:100%;max-width:100%;border-radius:10px;display:block;"></video></figure><p><br></p>`;
    if (kind === 'audio') return `<figure class="editor-media" data-editor-media="audio" contenteditable="false" draggable="false" style="width:100%;max-width:100%;margin:12px auto;clear:both;"><audio src="${url}" controls draggable="false" style="width:100%;display:block;"></audio></figure><p><br></p>`;
    return `<div class="editor-file-block" data-editor-media="file" contenteditable="false" draggable="false"><a class="editor-file" href="${url}" target="_blank" rel="noopener" draggable="false"><span class="editor-file-icon">${ext}</span><span class="editor-file-body"><span class="editor-file-name">${name}</span></span></a></div><p><br></p>`;
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

  function createUploadProgress(target, label) {
    const host = target || document.body;
    const floating = host === document.body;
    if (!floating) {
      const position = window.getComputedStyle(host).position;
      if (position === 'static') {
        host.dataset.uploadProgressPosition = 'static';
        host.style.position = 'relative';
      }
    }

    const wrap = document.createElement('div');
    wrap.className = `editor-upload-progress${floating ? ' is-floating' : ''}`;
    wrap.innerHTML = `
      <div class="editor-upload-card">
        <div class="editor-upload-label">${label}</div>
        <div class="editor-upload-meta">
          <span class="editor-upload-count">0%</span>
          <span class="editor-upload-size">čekám…</span>
        </div>
        <div class="editor-upload-bar"><span></span></div>
        <div class="editor-upload-stage">Připravuji soubor…</div>
      </div>
    `;
    host.appendChild(wrap);

    const percentEl = wrap.querySelector('.editor-upload-count');
    const sizeEl = wrap.querySelector('.editor-upload-size');
    const barEl = wrap.querySelector('.editor-upload-bar > span');
    const stageEl = wrap.querySelector('.editor-upload-stage');

    return {
      setProgress(percent, stage, sizeText) {
        const safe = Math.max(0, Math.min(100, Math.round(percent)));
        percentEl.textContent = `${safe}%`;
        barEl.style.width = `${safe}%`;
        if (stage) stageEl.textContent = stage;
        if (sizeText) sizeEl.textContent = sizeText;
      },
      finish(stage = 'Hotovo') {
        percentEl.textContent = '100%';
        barEl.style.width = '100%';
        stageEl.textContent = stage;
        sizeEl.textContent = 'dokončeno';
        setTimeout(() => {
          if (host.dataset.uploadProgressPosition === 'static') {
            host.style.position = '';
            delete host.dataset.uploadProgressPosition;
          }
          wrap.remove();
        }, 500);
      },
      fail(stage = 'Upload selhal') {
        stageEl.textContent = stage;
        sizeEl.textContent = 'chyba';
        wrap.style.background = 'rgba(255,248,248,.96)';
        setTimeout(() => {
          if (host.dataset.uploadProgressPosition === 'static') {
            host.style.position = '';
            delete host.dataset.uploadProgressPosition;
          }
          wrap.remove();
        }, 1400);
      }
    };
  }

  function uploadFormDataWithProgress({ url, formData, onProgress }) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.responseType = 'text';

      xhr.upload.addEventListener('progress', (event) => {
        if (event.lengthComputable && typeof onProgress === 'function') {
          onProgress(event.loaded, event.total);
        }
      });

      xhr.onload = () => {
        if (xhr.status < 200 || xhr.status >= 300) {
          reject(new Error(`Upload failed with status ${xhr.status}`));
          return;
        }
        const responseText = (xhr.responseText || '').trim();
        if (responseText.startsWith('<')) {
          reject(new Error('Server vrátil HTML chybu místo JSON odpovědi. Zkuste stránku obnovit, případně zkontrolovat log serveru.'));
          return;
        }
        try {
          resolve(JSON.parse(responseText));
        } catch (error) {
          reject(new Error('Server vrátil neplatnou odpověď. Upload se nepodařil.'));
        }
      };

      xhr.onerror = () => reject(new Error('Network error'));
      xhr.send(formData);
    });
  }

  async function uploadImageWithProgress({ url, file, target, label, prepareOptions }) {
    const progress = createUploadProgress(target, label);
    try {
      progress.setProgress(3, 'Zpracovávám obrázek…', `${Math.round(file.size / 1024)} KB`);
      const preparedFile = await prepareImageForUpload(file, prepareOptions);
      progress.setProgress(18, 'Obrázek připraven, začínám nahrávat…', `${Math.round(preparedFile.size / 1024)} KB`);

      const formData = new FormData();
      formData.append('ajax_action', 'upload_image');
      formData.append('image', preparedFile);

      const data = await uploadFormDataWithProgress({
        url,
        formData,
        onProgress: (loaded, total) => {
          const base = 18;
          const percent = total > 0 ? base + ((loaded / total) * 82) : base;
          const loadedKb = Math.round(loaded / 1024);
          const totalKb = Math.round(total / 1024);
          progress.setProgress(percent, 'Nahrávám obrázek…', `${loadedKb} / ${totalKb} KB`);
        }
      });

      if (!data.success) {
        throw new Error(data.message || 'Chyba uploadu');
      }

      progress.finish('Obrázek nahrán');
      return data;
    } catch (error) {
      progress.fail(error.message || 'Chyba uploadu');
      showUploadAlert(error.message || 'Upload se nepodařil. Zkuste to prosím znovu.');
      throw error;
    }
  }

  async function uploadMediaWithProgress({ url, file, target, label, prepareOptions }) {
    const validation = validateMediaFile(file);
    if (!validation.ok) {
      if (validation.code === 'unsupported-format') {
        showAllowedFormatAlert();
      } else {
        showUploadAlert(validation.message);
      }
      throw new Error(validation.message);
    }

    const kind = getMediaKind(file);
    if (kind === 'image') {
      return uploadImageWithProgress({ url, file, target, label, prepareOptions });
    }

    const progress = createUploadProgress(target, label || 'Nahrávám soubor');
    try {
      progress.setProgress(8, 'Připravuji soubor…', `${Math.round(file.size / 1024)} KB`);
      const formData = new FormData();
      formData.append('ajax_action', 'upload_media');
      formData.append('media', file);

      const data = await uploadFormDataWithProgress({
        url,
        formData,
        onProgress: (loaded, total) => {
          const percent = total > 0 ? 8 + ((loaded / total) * 92) : 8;
          const loadedKb = Math.round(loaded / 1024);
          const totalKb = Math.round(total / 1024);
          progress.setProgress(percent, 'Nahrávám soubor…', `${loadedKb} / ${totalKb} KB`);
        }
      });

      if (!data.success) {
        throw new Error(data.message || 'Chyba uploadu');
      }

      progress.finish('Soubor nahrán');
      return data;
    } catch (error) {
      progress.fail(error.message || 'Chyba uploadu');
      showUploadAlert(error.message || 'Upload se nepodařil. Zkuste to prosím znovu.');
      throw error;
    }
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

  function positionImageToolbar(toolbar, block) {
    const rect = block.getBoundingClientRect();
    toolbar.style.top = `${Math.max(12, rect.top - 54)}px`;
    toolbar.style.left = `${Math.max(12, Math.min(window.innerWidth - toolbar.offsetWidth - 12, rect.left))}px`;
  }

  function syncImageToolbarState(toolbar, block) {
    if (!toolbar || !block) return;
    const isFigure = block.matches('figure.editor-media');
    const activeSize = block.dataset.size || (block.style.width || '50%').replace('%', '');
    toolbar.querySelectorAll('button[data-action="size"],button[data-action="align"]').forEach((button) => {
      button.hidden = !isFigure;
    });
    toolbar.querySelectorAll('button[data-action="size"]').forEach((button) => {
      const isActive = button.dataset.value === activeSize;
      button.classList.toggle('is-active', isActive);
    });
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

  function getMediaBlockNodes(block) {
    const nodes = [block];
    if (isEmptyParagraph(block.nextElementSibling)) {
      nodes.push(block.nextElementSibling);
    }
    return nodes;
  }

  function moveMediaBlock(block, direction) {
    const nodes = getMediaBlockNodes(block);
    let target = direction === 'up' ? block.previousElementSibling : nodes[nodes.length - 1].nextElementSibling;

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
    let selectedBlock = null;

    const clearSelection = () => {
      if (selectedBlock) selectedBlock.classList.remove('is-selected');
      selectedBlock = null;
      toolbar.classList.remove('is-visible');
    };

    const markChanged = () => {
      normalizeEditorMarkup(editor);
      if (typeof onChange === 'function') onChange();
    };

    editor.addEventListener('dragstart', (event) => {
      if (getClosestMediaBlock(event.target)) {
        event.preventDefault();
      }
    });

    editor.addEventListener('click', (event) => {
      const block = getClosestMediaBlock(event.target);
      if (!block || !editor.contains(block)) {
        clearSelection();
        return;
      }

      event.preventDefault();
      if (selectedBlock) selectedBlock.classList.remove('is-selected');
      selectedBlock = block;
      selectedBlock.classList.add('is-selected');
      toolbar.classList.add('is-visible');
      positionImageToolbar(toolbar, selectedBlock);
      syncImageToolbarState(toolbar, selectedBlock);
    });

    document.addEventListener('click', (event) => {
      if (!selectedBlock) return;
      if (toolbar.contains(event.target) || selectedBlock.contains(event.target)) return;
      clearSelection();
    });

    window.addEventListener('scroll', () => {
      if (selectedBlock && toolbar.classList.contains('is-visible')) {
        positionImageToolbar(toolbar, selectedBlock);
      }
    }, { passive: true });

    window.addEventListener('resize', () => {
      if (selectedBlock && toolbar.classList.contains('is-visible')) {
        positionImageToolbar(toolbar, selectedBlock);
      }
    });

    toolbar.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-action]');
      if (!button || !selectedBlock) return;

      const action = button.dataset.action;
      const value = button.dataset.value;

      if (action === 'size') {
        selectedBlock.style.width = `${value}%`;
        selectedBlock.dataset.size = value;
      } else if (action === 'align') {
        applyImageAlignment(selectedBlock, value);
      } else if (action === 'move') {
        moveMediaBlock(selectedBlock, value === 'up' ? 'up' : 'down');
      } else if (action === 'remove') {
        getMediaBlockNodes(selectedBlock).forEach((node) => node.remove());
        clearSelection();
      }

      if (selectedBlock) {
        positionImageToolbar(toolbar, selectedBlock);
        syncImageToolbarState(toolbar, selectedBlock);
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
      if (activeNode || getClosestMediaBlock(getNodeElement(getSelection()?.focusNode))) {
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
    prepareImageForUpload,
    uploadImageWithProgress,
    uploadMediaWithProgress,
    buildMediaHtml,
    getMediaKind,
    validateMediaFile,
    showUploadAlert,
    showAllowedFormatAlert
  };
})();
