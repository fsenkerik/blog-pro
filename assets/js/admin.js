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
