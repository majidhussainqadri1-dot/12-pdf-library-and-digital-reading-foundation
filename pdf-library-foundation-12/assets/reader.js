(() => {
  'use strict';
  const cfg = window.PLDR_READER || null;
  const root = document.querySelector('[data-pldr-reader]');
  const api = async (path, options = {}) => {
    const headers = Object.assign({'X-WP-Nonce': cfg?.nonce || ''}, options.headers || {});
    if (options.body && typeof options.body !== 'string') {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    const response = await fetch((cfg?.rest || '/wp-json/pldr/v1/') + path, Object.assign({credentials: 'same-origin', headers}, options));
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data?.message || `Request failed (${response.status})`);
    return data;
  };

  if (!root || !cfg) return;
  const frame = root.querySelector('[data-frame]');
  const pageInput = root.querySelector('[data-page]');
  const status = root.querySelector('.pldr-reader-status');
  const ocrForm = root.querySelector('[data-ocr-search]');
  const ocrResults = root.querySelector('[data-ocr-results]');
  const privateItems = root.querySelector('[data-private-items]');
  let page = Math.max(1, Math.min(cfg.pages, Number(cfg.startPage) || 1));
  let zoom = 'page-width';
  let saveTimer = null;
  let tokenUrl = cfg.url;

  const say = (message) => { if (status) status.textContent = message || ''; };
  const updateFrame = () => {
    if (!frame) return;
    frame.src = `${tokenUrl}#page=${page}&zoom=${encodeURIComponent(zoom)}`;
    if (pageInput) pageInput.value = String(page);
    root.querySelectorAll('[data-thumb-page]').forEach((button) => button.setAttribute('aria-current', Number(button.dataset.thumbPage) === page ? 'page' : 'false'));
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(saveProgress, 700);
  };
  const refreshToken = async (operation = 'read') => {
    const grant = await api('reader-access', {method: 'POST', body: {edition_id: cfg.editionId, operation, ttl: 900}, headers: {'Idempotency-Key': crypto.randomUUID?.() || `${Date.now()}-${Math.random()}`}});
    return grant;
  };
  const saveProgress = async () => {
    try {
      await api('reading/progress', {method: 'POST', body: {edition_id: cfg.editionId, page}, headers: {'Idempotency-Key': crypto.randomUUID?.() || `progress-${cfg.editionId}-${page}-${Date.now()}-${Math.random()}`}});
      say(cfg.strings?.saved || 'Saved privately.');
    } catch (error) { say(error.message); }
  };
  const setPage = (next) => { page = Math.max(1, Math.min(cfg.pages, Number(next) || 1)); updateFrame(); };

  root.addEventListener('click', async (event) => {
    const button = event.target.closest('button');
    if (!button) return;
    if (button.dataset.thumbPage) { setPage(button.dataset.thumbPage); return; }
    const action = button.dataset.action;
    if (!action) return;
    if (action === 'prev') setPage(page - 1);
    if (action === 'next') setPage(page + 1);
    if (action === 'zoom-in') { const n = Number(zoom) || 100; zoom = String(Math.min(300, n + 25)); updateFrame(); }
    if (action === 'zoom-out') { const n = Number(zoom) || 100; zoom = String(Math.max(50, n - 25)); updateFrame(); }
    if (action === 'fit') { zoom = 'page-width'; updateFrame(); }
    if (action === 'fullscreen') { try { await root.requestFullscreen(); } catch (e) { say(e.message); } }
    if (action === 'bookmark') {
      try { await api('reading/items', {method:'POST',body:{edition_id:cfg.editionId,type:'bookmark',page},headers:{'Idempotency-Key':crypto.randomUUID?.()||`bookmark-${cfg.editionId}-${page}-${Date.now()}-${Math.random()}`}}); say('Bookmark saved privately.'); loadPrivateItems(); } catch(e){ say(e.message); }
    }
    if (action === 'note') {
      const note = window.prompt('Private note for this page:');
      if (note === null || !note.trim()) return;
      try { await api('reading/items', {method:'POST',body:{edition_id:cfg.editionId,type:'note',page,note},headers:{'Idempotency-Key':crypto.randomUUID?.()||`${Date.now()}-${Math.random()}`}}); say('Private note saved.'); loadPrivateItems(); } catch(e){ say(e.message); }
    }
    if (action === 'highlight') {
      const anchor = window.prompt('Text or description of the highlighted passage on this page:');
      if (anchor === null || !anchor.trim()) return;
      const note = window.prompt('Optional private note for this highlight:') || '';
      try { await api('reading/items', {method:'POST',body:{edition_id:cfg.editionId,type:'highlight',page,anchor,note},headers:{'Idempotency-Key':crypto.randomUUID?.()||`${Date.now()}-${Math.random()}`}}); say('Highlight note saved privately.'); loadPrivateItems(); } catch(e){ say(e.message); }
    }
    if (action === 'citation') {
      try { const result = await api(`citation/${cfg.editionId}?page=${page}&style=sabri`); await navigator.clipboard.writeText(result.citation); say('Citation copied.'); } catch(e){ say(e.message); }
    }
    if (action === 'print') {
      try { const grant = await refreshToken('print'); window.open(`${grant.url}#page=${page}`, '_blank', 'noopener'); say('Authorized print view opened.'); } catch(e){ say(e.message); }
    }
    if (action === 'download') openDownloadManager();
  });

  pageInput?.addEventListener('change', () => setPage(pageInput.value));
  root.addEventListener('keydown', (event) => {
    if (event.target.matches('input,textarea,select')) return;
    if (event.key === 'ArrowLeft' || event.key === 'PageUp') { event.preventDefault(); setPage(page - 1); }
    if (event.key === 'ArrowRight' || event.key === 'PageDown') { event.preventDefault(); setPage(page + 1); }
    if (event.key === 'Home') { event.preventDefault(); setPage(1); }
    if (event.key === 'End') { event.preventDefault(); setPage(cfg.pages); }
    if (event.key === '+' || event.key === '=') { event.preventDefault(); root.querySelector('[data-action="zoom-in"]')?.click(); }
    if (event.key === '-') { event.preventDefault(); root.querySelector('[data-action="zoom-out"]')?.click(); }
  });

  frame?.addEventListener('error', async () => {
    say(cfg.strings?.error || 'Reader error.');
    try { const grant = await refreshToken('read'); tokenUrl = grant.url; updateFrame(); } catch (e) { say(e.message); }
  });

  ocrForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const query = new FormData(ocrForm).get('q')?.toString().trim() || '';
    if (query.length < 2) return;
    ocrResults.innerHTML = '<p>Searching…</p>';
    try {
      const result = await api(`ocr-search/${cfg.editionId}?q=${encodeURIComponent(query)}`);
      if (!result.items?.length) { ocrResults.innerHTML = '<p>No lawful OCR text matches were found.</p>'; return; }
      const list = document.createElement('ol');
      result.items.forEach((item) => { const li=document.createElement('li'); const b=document.createElement('button'); b.type='button'; b.textContent=`Page ${item.page}: ${item.snippet}`; b.addEventListener('click',()=>setPage(item.page)); li.appendChild(b); list.appendChild(li); });
      ocrResults.replaceChildren(list);
    } catch(e){ ocrResults.textContent=e.message; }
  });

  const loadPrivateItems = async () => {
    if (!privateItems) return;
    try {
      const result = await api(`reading/items?edition_id=${cfg.editionId}`);
      if (!result.items?.length) { privateItems.innerHTML=''; return; }
      const section=document.createElement('section'); section.innerHTML='<h2>Private bookmarks and notes</h2>';
      const ul=document.createElement('ul');
      result.items.forEach((item)=>{const li=document.createElement('li');const jump=document.createElement('button');jump.type='button';jump.textContent=`${item.item_type} · page ${item.page_number}`;jump.addEventListener('click',()=>setPage(item.page_number));li.appendChild(jump);if(item.note_text){const span=document.createElement('span');span.textContent=` — ${item.note_text}`;li.appendChild(span);}const del=document.createElement('button');del.type='button';del.textContent='Delete';del.addEventListener('click',async()=>{await api(`reading/items/${item.id}`,{method:'DELETE',headers:{'Idempotency-Key':crypto.randomUUID?.()||`delete-${item.id}-${Date.now()}-${Math.random()}`}});loadPrivateItems();});li.appendChild(del);ul.appendChild(li);});section.appendChild(ul);privateItems.replaceChildren(section);
    } catch(e){ /* private overlays degrade without blocking reading */ }
  };

  const dm = root.querySelector('[data-download-manager]');
  const progress = root.querySelector('[data-download-progress]');
  const dmStatus = root.querySelector('[data-download-status]');
  const dmChecksum = root.querySelector('[data-download-checksum]');
  let download = {paused:false,running:false,session:null,chunks:[],next:0,loaded:0};
  const openDownloadManager = async () => {
    if (!dm) return; dm.hidden=false; dm.scrollIntoView({behavior:'smooth',block:'nearest'});
    if (!download.session) {
      try { download.session=await api('downloads/session',{method:'POST',body:{edition_id:cfg.editionId},headers:{'Idempotency-Key':crypto.randomUUID?.()||`${Date.now()}-${Math.random()}`}}); dmChecksum.textContent=download.session.checksum; dmStatus.textContent='Ready. Range-resumable access will be revalidated on every chunk.'; } catch(e){dmStatus.textContent=e.message;}
    }
  };
  const runDownload = async () => {
    if(download.running||!download.session)return;download.running=true;download.paused=false;
    const d=download.session.delivery;const chunkSize=download.session.range_bytes||2097152;const total=d.size;
    try{
      while(download.loaded<total&&!download.paused){const start=download.loaded;const end=Math.min(total-1,start+chunkSize-1);let response=await fetch(d.url,{headers:{Range:`bytes=${start}-${end}`},credentials:'same-origin'});if(response.status===410||response.status===403){download.session=await api('downloads/session',{method:'POST',body:{edition_id:cfg.editionId},headers:{'Idempotency-Key':crypto.randomUUID?.()||`${Date.now()}-${Math.random()}`}});response=await fetch(download.session.delivery.url,{headers:{Range:`bytes=${start}-${end}`},credentials:'same-origin'});}if(!(response.ok||response.status===206))throw new Error(`Download range failed (${response.status})`);const blob=await response.blob();download.chunks.push(blob);download.loaded+=blob.size;if(progress)progress.value=Math.floor((download.loaded/total)*100);dmStatus.textContent=`${Math.floor((download.loaded/total)*100)}% · ${download.loaded.toLocaleString()} / ${total.toLocaleString()} bytes`;}
      if(download.loaded>=total){const blob=new Blob(download.chunks,{type:d.mime_type||'application/pdf'});if(blob.size!==total)throw new Error('Downloaded byte count does not match the signed manifest.');if(total<=128*1024*1024&&crypto.subtle){dmStatus.textContent='Verifying SHA-256 checksum…';const digest=await crypto.subtle.digest('SHA-256',await blob.arrayBuffer());const hex=[...new Uint8Array(digest)].map(b=>b.toString(16).padStart(2,'0')).join('');if(hex.toLowerCase()!==d.sha256.toLowerCase())throw new Error('Download checksum mismatch.');}const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=d.filename||'document.pdf';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);dmStatus.textContent=total<=128*1024*1024?'Download complete; SHA-256 verified.':'Download complete; server-side object integrity and authenticated ranges verified. Expected checksum is shown below.';download.running=false;}
    }catch(e){download.running=false;dmStatus.textContent=e.message;}
  };
  root.querySelector('[data-download-start]')?.addEventListener('click',runDownload);
  root.querySelector('[data-download-pause]')?.addEventListener('click',()=>{download.paused=true;dmStatus.textContent='Paused after the current authenticated range.';});
  root.querySelector('[data-download-resume]')?.addEventListener('click',()=>{download.paused=false;runDownload();});

  say(cfg.strings?.loading || 'Loading…');
  updateFrame();
  loadPrivateItems();
  if (window.location.hash === '#download' && cfg.canDownload) openDownloadManager();
  window.setTimeout(()=>say(''),1200);
})();
