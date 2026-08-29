(() => {
  'use strict';
  const cfg = window.PLDR_READER || null;
  const future = window.PLDR_FUTURE || null;
  const reader = document.querySelector('[data-pldr-reader]');
  const root = document.querySelector('[data-pldr-f24]');
  if (!cfg || !future || !reader || !root) return;

  const F = window.PLDRF24 = {
    cfg, future, reader, root,
    editionId: Number(root.dataset.edition || cfg.editionId || 0),
    panel: root.querySelector('[data-f24-panel]'),
    workspace: root.querySelector('[data-f24-workspace]'),
    reflowBox: root.querySelector('[data-f24-reflow]'),
    contextBox: root.querySelector('[data-f24-context]'),
    status: root.querySelector('[data-f24-status]'),
    pageInput: reader.querySelector('[data-page]'),
    frame: reader.querySelector('[data-frame]'),
    stage: reader.querySelector('.pldr-reader-stage'),
    reflowData: null,
    speaking: false,
    layout: 'single',
    prefVersion: 0,
    lastSelection: '',
    sessionVersion: 0,
    startedAt: Date.now(),
    lastHeartbeatAt: Date.now(),
  };

  F.page = () => Math.max(1, Number(F.pageInput?.value || cfg.startPage || 1));
  F.zoom = () => {
    const src = F.frame?.getAttribute('src') || '';
    const match = src.match(/(?:#|&)zoom=([^&]+)/);
    return match ? decodeURIComponent(match[1]) : 'page-width';
  };
  F.say = (text) => { if (F.status) F.status.textContent = text || ''; };
  F.openPanel = (open = true) => {
    if (!F.panel) return;
    F.panel.hidden = !open;
    root.querySelector('[data-f24-action="toggle-panel"]')?.setAttribute('aria-expanded', open ? 'true' : 'false');
  };
  F.setWorkspace = (nodeOrHtml) => {
    if (!F.workspace) return;
    if (typeof nodeOrHtml === 'string') F.workspace.innerHTML = nodeOrHtml;
    else F.workspace.replaceChildren(nodeOrHtml);
  };
  F.escape = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
  F.idempotency = (scope = 'future') => `${scope}-${F.editionId}-${Date.now()}-${Math.random().toString(36).slice(2)}`;
  F.storageGet = (key, fallback = {}) => {
    try {
      const raw = window.localStorage?.getItem(key);
      if (!raw) return fallback;
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : fallback;
    } catch (_) { return fallback; }
  };
  F.storageSet = (key, value) => {
    try { window.localStorage?.setItem(key, JSON.stringify(value)); return true; }
    catch (_) { return false; }
  };
  F.api = async (path, options = {}) => {
    const headers = Object.assign({'X-WP-Nonce': future.nonce || cfg.nonce || ''}, options.headers || {});
    if (options.body && typeof options.body !== 'string') {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    const response = await fetch((future.rest || '/wp-json/pldr/v1/future/') + path, Object.assign({credentials:'same-origin', headers}, options));
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data?.message || `Advanced reader request failed (${response.status})`);
    return data;
  };

  F.loadReflow = async (focusPage = 0) => {
    F.openPanel(true);
    if (F.reflowBox) { F.reflowBox.hidden = false; F.reflowBox.innerHTML = '<p>Loading lawful reflow text…</p>'; }
    try {
      const data = await F.api(`reflow/${F.editionId}${focusPage ? `?page=${focusPage}` : ''}`);
      F.reflowData = data;
      if (!data.available || !data.pages?.length) {
        F.reflowBox.innerHTML = '<p>Reflow text is unavailable. The original PDF remains unchanged and readable through the standard viewer.</p>';
        return null;
      }
      const frag = document.createDocumentFragment();
      data.pages.forEach(item => {
        const article = document.createElement('article');
        article.dataset.reflowPage = String(item.page);
        article.lang = item.language || cfg.language || '';
        const h = document.createElement('h3'); h.textContent = `Page ${item.page}`;
        const body = document.createElement('div'); body.className = 'pldr-f24-reflow-text'; body.textContent = item.text;
        article.append(h, body); frag.appendChild(article);
      });
      F.reflowBox.replaceChildren(frag);
      if (focusPage) F.reflowBox.querySelector(`[data-reflow-page="${focusPage}"]`)?.scrollIntoView({block:'start'});
      return data;
    } catch (error) { if (F.reflowBox) F.reflowBox.textContent = error.message; return null; }
  };

  F.readAloud = async () => {
    if (!('speechSynthesis' in window)) { F.say('Text-to-speech is unavailable in this browser.'); return; }
    if (F.speaking) { speechSynthesis.cancel(); F.speaking = false; F.say('Read aloud stopped.'); return; }
    if (!F.reflowData) await F.loadReflow(F.page());
    const article = F.reflowBox?.querySelector(`[data-reflow-page="${F.page()}"]`) || F.reflowBox?.querySelector('[data-reflow-page]');
    const text = article?.querySelector('.pldr-f24-reflow-text')?.textContent?.trim();
    if (!text) { F.say('No lawful reflow text is available for read aloud.'); return; }
    const utter = new SpeechSynthesisUtterance(text.slice(0, 32000));
    utter.lang = article.lang || cfg.language || document.documentElement.lang || 'en-US';
    const saved = F.storageGet('pldr-f24-prefs', {});
    utter.rate = Math.max(.5, Math.min(2, Number(saved.tts_rate || 1)));
    utter.onend = () => { F.speaking = false; F.say('Read aloud finished.'); };
    utter.onerror = () => { F.speaking = false; F.say('Read aloud stopped because the browser speech engine reported an error.'); };
    F.speaking = true; speechSynthesis.cancel(); speechSynthesis.speak(utter); F.say('Reading aloud current reflow page.');
  };

  const savePrefs = (() => {
    let timer = null;
    return () => {
      const value = {
        layout: F.layout,
        text_size: Number(root.querySelector('[data-f24-text-size]')?.value || 110),
        line_height: Number(root.querySelector('[data-f24-line-height]')?.value || 175),
        column_width: Number(root.querySelector('[data-f24-column-width]')?.value || 82),
        contrast: root.querySelector('[data-f24-contrast]')?.value || 'default',
        data_saver: !!root.querySelector('[data-f24-data-saver]')?.checked,
      };
      F.storageSet('pldr-f24-prefs', value);
      clearTimeout(timer);
      if (future.loggedIn) timer = setTimeout(async () => {
        try {
          const result = await F.api('preferences', {method:'POST', body:{key:'reader', value, expected_version:F.prefVersion}, headers:{'Idempotency-Key':F.idempotency('prefs')}});
          F.prefVersion = Number(result.version || F.prefVersion);
        } catch (error) { if (error.message.includes('changed')) loadPrefs(); }
      }, 500);
    };
  })();

  const applyPrefs = prefs => {
    if (!prefs) return;
    const layout = root.querySelector('[data-f24-layout]');
    const size = root.querySelector('[data-f24-text-size]');
    const line = root.querySelector('[data-f24-line-height]');
    const width = root.querySelector('[data-f24-column-width]');
    const contrast = root.querySelector('[data-f24-contrast]');
    const saver = root.querySelector('[data-f24-data-saver]');
    if (layout && prefs.layout) layout.value = prefs.layout;
    if (size && prefs.text_size) size.value = String(prefs.text_size);
    if (line && prefs.line_height) line.value = String(prefs.line_height);
    if (width && prefs.column_width) width.value = String(prefs.column_width);
    if (contrast && prefs.contrast) contrast.value = prefs.contrast;
    if (saver && prefs.data_saver !== undefined) saver.checked = !!prefs.data_saver;
    F.applyLayout(layout?.value || 'single', false);
    F.applyReaderTypography(false);
  };

  const loadPrefs = async () => {
    const local = F.storageGet('pldr-f24-prefs', {}); applyPrefs(local);
    if (!future.loggedIn) return;
    try { const result = await F.api('preferences?key=reader'); F.prefVersion = Number(result.version || 0); applyPrefs(result.value || local); } catch (_) {}
  };

  F.applyReaderTypography = (persist = true) => {
    root.style.setProperty('--f24-text-size', `${Math.max(90,Math.min(180,Number(root.querySelector('[data-f24-text-size]')?.value || 110)))}%`);
    root.style.setProperty('--f24-line-height', String(Math.max(1.4,Math.min(2.4,Number(root.querySelector('[data-f24-line-height]')?.value || 175)/100))));
    root.style.setProperty('--f24-column-width', `${Math.max(45,Math.min(100,Number(root.querySelector('[data-f24-column-width]')?.value || 82)))}ch`);
    root.dataset.f24Contrast = root.querySelector('[data-f24-contrast]')?.value || 'default';
    if (persist) savePrefs();
  };

  F.applyLayout = (layout, persist = true) => {
    const allowed = ['single','continuous','spread-ltr','spread-rtl','horizontal','presentation'];
    F.layout = allowed.includes(layout) ? layout : 'single'; reader.dataset.futureLayout = F.layout;
    reader.querySelector('.pldr-f24-spread-frame')?.remove();
    if ((F.layout === 'spread-ltr' || F.layout === 'spread-rtl') && F.frame && F.stage) {
      const adjacent = document.createElement('iframe'); adjacent.className='pldr-f24-spread-frame'; adjacent.title=`Adjacent page ${Math.min(cfg.pages,F.page()+1)}`; adjacent.src=`${cfg.url}#page=${Math.min(cfg.pages,F.page()+1)}&zoom=page-width`; F.stage.appendChild(adjacent);
    }
    if (F.layout === 'continuous') F.loadReflow(F.page());
    if (F.layout === 'presentation') reader.requestFullscreen?.().catch(()=>{});
    if (persist) savePrefs();
    window.dispatchEvent(new CustomEvent('pldr:f24-layout',{detail:{layout:F.layout}}));
  };

  root.addEventListener('click', async event => {
    const button = event.target.closest('button[data-f24-action]'); if (!button) return;
    const action = button.dataset.f24Action;
    if (action === 'toggle-panel') F.openPanel(F.panel.hidden);
    if (action === 'reflow') await F.loadReflow(F.page());
    if (action === 'read-aloud') await F.readAloud();
  });
  root.querySelector('[data-f24-layout]')?.addEventListener('change', e => F.applyLayout(e.target.value, true));
  ['[data-f24-text-size]','[data-f24-line-height]','[data-f24-column-width]'].forEach(sel => root.querySelector(sel)?.addEventListener('input', () => F.applyReaderTypography(true)));
  root.querySelector('[data-f24-contrast]')?.addEventListener('change', () => F.applyReaderTypography(true));
  root.querySelector('[data-f24-data-saver]')?.addEventListener('change', async e => { savePrefs(); if (e.target.checked) await F.loadReflow(F.page()); });
  F.reflowBox?.addEventListener('mouseup', () => { const text=window.getSelection()?.toString().trim() || ''; if (text) F.lastSelection=text.slice(0,500); });
  F.reflowBox?.addEventListener('keyup', () => { const text=window.getSelection()?.toString().trim() || ''; if (text) F.lastSelection=text.slice(0,500); });

  const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  if (connection && (connection.saveData || ['slow-2g','2g'].includes(connection.effectiveType))) {
    const saver = root.querySelector('[data-f24-data-saver]'); if (saver) saver.checked = true;
    F.loadReflow(F.page()); F.say('Ultra-low-bandwidth mode enabled: lawful reflow text is prioritized.');
  }
  loadPrefs();
})();
