// ── Inactivity logout (5 min warning + 1 min countdown) ─────────────────────
(function() {
  const IDLE_MS   = 5 * 60 * 1000;  // 5 min before warning
  const WARN_MS   = 60 * 1000;       // 60 sec countdown
  const LOGOUT_URL = (typeof ADMIN_URL !== 'undefined' ? ADMIN_URL : '') + 'logout.php';

  let idleTimer   = null;
  let countTimer  = null;
  let countVal    = 60;
  let modal       = null;

  function createModal() {
    if (modal) return;
    modal = document.createElement('div');
    modal.id = 'inactivityModal';
    modal.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);';
    modal.innerHTML = `
      <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:36px;max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--warn-soft);color:var(--warn);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div style="font-size:17px;font-weight:600;color:var(--ink);margin-bottom:8px;">Brzy budete odhlášeni</div>
        <div style="font-size:13.5px;color:var(--body);margin-bottom:8px;">Z důvodu nečinnosti budete odhlášeni za</div>
        <div style="font-family:var(--mono);font-size:36px;font-weight:700;color:var(--warn);margin-bottom:20px;" id="inactivityCount">60</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:24px;">sekund</div>
        <button onclick="resetIdle()" style="padding:10px 24px;border-radius:8px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-size:14px;font-weight:500;border:none;cursor:pointer;width:100%;">Zůstat přihlášen</button>
      </div>`;
    document.body.appendChild(modal);
  }

  function showWarning() {
    createModal();
    countVal = 60;
    modal.style.display = 'flex';
    document.getElementById('inactivityCount').textContent = countVal;
    countTimer = setInterval(() => {
      countVal--;
      const el = document.getElementById('inactivityCount');
      if (el) el.textContent = countVal;
      if (countVal <= 0) {
        clearInterval(countTimer);
        window.location.href = LOGOUT_URL;
      }
    }, 1000);
  }

  function hideWarning() {
    if (modal) modal.style.display = 'none';
    clearInterval(countTimer);
  }

  function resetIdle() {
    hideWarning();
    clearTimeout(idleTimer);
    idleTimer = setTimeout(showWarning, IDLE_MS);
  }

  window.resetIdle = resetIdle;

  ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll', 'click'].forEach(evt => {
    document.addEventListener(evt, resetIdle, { passive: true });
  });

  idleTimer = setTimeout(showWarning, IDLE_MS);
})();

// Topbar article search used by dashboard and post list pages.
(function() {
  const input = document.getElementById('topSearch');
  if (!input) return;

  const wrap = input.closest('.search') || input.parentElement;
  if (!wrap) return;

  let timer = null;
  let activeIndex = -1;
  let results = [];

  const box = document.createElement('div');
  box.className = 'top-search-results';
  box.style.cssText = 'display:none;position:absolute;left:0;right:0;top:calc(100% + 8px);z-index:1000;background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 45px rgba(31,41,55,.16);overflow:hidden;';
  wrap.appendChild(box);

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[char]));
  }

  function statusLabel(status) {
    return {
      published: 'Publikovano',
      draft: 'Koncept',
      scheduled: 'Planovano'
    }[status] || status || '';
  }

  function hideResults() {
    box.style.display = 'none';
    box.innerHTML = '';
    activeIndex = -1;
  }

  function setActive(index) {
    const items = box.querySelectorAll('[data-search-index]');
    if (!items.length) return;
    activeIndex = Math.max(0, Math.min(index, items.length - 1));
    items.forEach((item, i) => {
      item.style.background = i === activeIndex ? 'var(--paper-2)' : 'transparent';
    });
  }

  function openResult(index) {
    const item = results[index];
    if (!item) return;
    window.location.href = 'edit_post.php?id=' + encodeURIComponent(item.id);
  }

  function render(data, query) {
    results = data.results || [];
    if (!results.length) {
      box.innerHTML = '<div style="padding:14px 16px;color:var(--muted);font-size:13px">Zadne vysledky pro "' + escapeHtml(query) + '".</div>';
      box.style.display = 'block';
      activeIndex = -1;
      return;
    }

    box.innerHTML = results.map((item, index) => `
      <button type="button" data-search-index="${index}" style="width:100%;display:block;text-align:left;padding:12px 14px;border:0;border-bottom:1px solid var(--line);background:transparent;cursor:pointer;font:inherit">
        <div style="display:flex;align-items:center;gap:10px;justify-content:space-between">
          <span style="min-width:0;font-size:13px;font-weight:500;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(item.title)}</span>
          <span style="font-size:10.5px;color:var(--muted);font-family:var(--mono);white-space:nowrap">${escapeHtml(statusLabel(item.status))}</span>
        </div>
        <div style="margin-top:3px;font-size:11.5px;color:var(--muted);display:flex;gap:8px">
          <span>${escapeHtml(item.category_name)}</span>
          <span>${escapeHtml(item.created_at)}</span>
        </div>
      </button>
    `).join('');
    box.style.display = 'block';
    box.querySelectorAll('[data-search-index]').forEach(item => {
      item.addEventListener('mouseenter', () => setActive(Number(item.dataset.searchIndex)));
      item.addEventListener('click', () => openResult(Number(item.dataset.searchIndex)));
    });
    setActive(0);
  }

  async function search() {
    const query = input.value.trim();
    if (query.length < 2) {
      hideResults();
      return;
    }

    box.innerHTML = '<div style="padding:14px 16px;color:var(--muted);font-size:13px">Hledam...</div>';
    box.style.display = 'block';

    try {
      const response = await fetch('search_posts.php?q=' + encodeURIComponent(query), {
        headers: { 'Accept': 'application/json' }
      });
      const data = await response.json();
      render(data, query);
    } catch (error) {
      box.innerHTML = '<div style="padding:14px 16px;color:var(--danger);font-size:13px">Vyhledavani se nepodarilo.</div>';
      box.style.display = 'block';
    }
  }

  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(search, 180);
  });

  input.addEventListener('keydown', event => {
    if (box.style.display === 'none') return;
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setActive(activeIndex + 1);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      setActive(activeIndex - 1);
    } else if (event.key === 'Enter') {
      event.preventDefault();
      openResult(activeIndex >= 0 ? activeIndex : 0);
    } else if (event.key === 'Escape') {
      hideResults();
    }
  });

  document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      input.focus();
      input.select();
    }
  });

  document.addEventListener('click', event => {
    if (!wrap.contains(event.target)) {
      hideResults();
    }
  });
})();
