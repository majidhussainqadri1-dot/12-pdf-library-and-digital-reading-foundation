(() => {
  'use strict';
  const F = window.PLDRF24; if (!F) return;
  const {root} = F;
  const jump = page => { if (!F.pageInput) return; F.pageInput.value=String(page); F.pageInput.dispatchEvent(new Event('change',{bubbles:true})); };

  const outline = async () => {
    F.openPanel(true); F.setWorkspace('<p>Recovering document outline…</p>');
    try { const data=await F.api(`outline/${F.editionId}`); if(!data.items?.length){F.setWorkspace('<p>No derived outline is available. The original PDF has not been modified.</p>');return;} const ol=document.createElement('ol'); data.items.forEach(item=>{const li=document.createElement('li'),b=document.createElement('button');b.type='button';b.textContent=`${item.title} — p. ${item.page}`;b.addEventListener('click',()=>jump(item.page));li.appendChild(b);ol.appendChild(li);});F.workspace.replaceChildren(ol);F.say(`Derived outline: ${data.provider}.`);} catch(e){F.setWorkspace(`<p>${F.escape(e.message)}</p>`);} };
  const compare = async () => {
    const other=Number(prompt('Enter the other File 12 edition ID:')||0);
    if(!Number.isSafeInteger(other)||other<1||other===F.editionId)return;
    F.openPanel(true);
    F.setWorkspace('<p>Comparing lawful OCR text page by page…</p>');
    try{
      const d=await F.api('compare?left='+F.editionId+'&right='+other);
      const changes=Array.isArray(d.changed)?d.changed:[];
      const box=document.createElement('div');
      const heading=document.createElement('h3');heading.textContent='Edition comparison';
      const p=document.createElement('p');
      p.textContent=String(d.same_pages??0)+' identical OCR pages; '+changes.length+
        ' changed; '+String(d.pages_without_comparable_ocr??0)+' without comparable OCR.';
      box.append(heading,p);
      const list=document.createElement('ol');
      changes.slice(0,200).forEach(item=>{
        const li=document.createElement('li');
        const strong=document.createElement('strong');
        strong.textContent='Page '+String(item.page??'?');
        const separation=document.createTextNode(' — similarity '+String(item.similarity??'?')+'%');
        const details=document.createElement('details');
        const summary=document.createElement('summary');summary.textContent='Show excerpts';
        const excerpts=document.createElement('div');excerpts.className='pldr-f24-compare';
        const left=document.createElement('blockquote');left.textContent=String(item.left_excerpt??'');
        const right=document.createElement('blockquote');right.textContent=String(item.right_excerpt??'');
        excerpts.append(left,right);details.append(summary,excerpts);
        li.append(strong,separation,details);list.appendChild(li);
      });
      F.workspace.replaceChildren(box,list);
    }catch(error){F.setWorkspace('<p>'+F.escape(error.message)+'</p>');}
  };
  const anchor = async () => { if(!F.future.loggedIn){F.say('Log in to save a private scholarly anchor.');return;}if(!F.reflowData)await F.loadReflow(F.page());const selection=window.getSelection()?.toString().trim()||F.lastSelection||'';if(!selection){F.say('Select text in Reflow mode first.');return;}const range=window.getSelection()?.rangeCount?window.getSelection().getRangeAt(0):null;const article=range?.commonAncestorContainer?.parentElement?.closest?.('[data-reflow-page]')||F.reflowBox?.querySelector(`[data-reflow-page="${F.page()}"]`);const rect=range?.getBoundingClientRect?.(),box=article?.getBoundingClientRect?.();const region=rect&&box&&box.width&&box.height?{x:Math.max(0,Math.min(1,(rect.left-box.left)/box.width)),y:Math.max(0,Math.min(1,(rect.top-box.top)/box.height)),w:Math.max(0,Math.min(1,rect.width/box.width)),h:Math.max(0,Math.min(1,rect.height/box.height))}:null;const note=prompt('Optional private note:')||'';try{const d=await F.api('anchor',{method:'POST',body:{edition_id:F.editionId,page:Number(article?.dataset.reflowPage||F.page()),selector:{type:'TextQuoteSelector',exact:selection.slice(0,260),region},note},headers:{'Idempotency-Key':F.idempotency('anchor')}});F.say(`Precise scholarly anchor saved privately (ID ${d.id}).`);}catch(e){F.say(e.message);}};
  const citation = async () => { const format=prompt('Citation format: bibtex, ris, csl-json, apa, mla, chicago, sabri','csl-json');if(!format)return;try{const d=await F.api(`citation-export/${F.editionId}?format=${encodeURIComponent(format)}&page=${F.page()}`);const text=typeof d.content==='string'?d.content:JSON.stringify(d.content,null,2);F.openPanel(true);F.setWorkspace(`<h3>Citation export — ${F.escape(d.format)}</h3><textarea class="pldr-f24-export" readonly>${F.escape(text)}</textarea>`);navigator.clipboard?.writeText(text).catch(()=>{});}catch(e){F.say(e.message);}};
  const annotations = async () => { if(!F.future.loggedIn){F.say('Log in to export private annotations.');return;}try{let after=0,first=null,items=[];for(;;){const d=await F.api(`annotations/${F.editionId}?limit=500${after?`&after_id=${after}`:''}`);if(!first)first=d;items.push(...(d.items||[]));if(!d.has_more||!d.next_after_id)break;const next=Number(d.next_after_id);if(!next||next<=after)throw new Error('Annotation export cursor did not advance safely.');after=next;}const page=Object.assign({},first||{}, {items,returned:items.length,has_more:false,next_after_id:null,complete_export:true});const text=JSON.stringify(page,null,2),blob=new Blob([text],{type:'application/ld+json'}),url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=`file12-edition-${F.editionId}-annotations.jsonld`;a.textContent=`Download complete W3C Web Annotation export (${items.length})`;F.openPanel(true);F.setWorkspace('<h3>Portable annotations</h3>');F.workspace.append(a);setTimeout(()=>URL.revokeObjectURL(url),60000);}catch(e){F.say(e.message);}};
  const ocr = async () => {
    try{
      const d=await F.api('ocr-quality/'+F.editionId);
      F.openPanel(true);
      const heading=document.createElement('h3');heading.textContent='OCR Quality Laboratory';
      const summary=document.createElement('p');
      summary.textContent='Average '+String(d.average_quality??'?')+
        '%; minimum '+String(d.minimum_quality??'?')+'%; maximum '+
        String(d.maximum_quality??'?')+'%; '+String(d.pages??0)+' OCR pages.';
      const heat=document.createElement('div');heat.className='pldr-f24-ocr-heat';
      (Array.isArray(d.heatmap)?d.heatmap:[]).slice(0,500).forEach(item=>{
        const span=document.createElement('span');
        span.title='Page '+String(item.page_number??'?')+': '+String(item.quality_score??'?')+'%';
        const raw=Number(item.quality_score);
        span.style.setProperty('--ocrq',String(Number.isFinite(raw)?Math.max(0,Math.min(1,raw/100)):0));
        span.textContent=String(item.page_number??'?');
        heat.appendChild(span);
      });
      const footer=document.createElement('p');
      const corrections=Array.isArray(d.corrections)?d.corrections:[];
      footer.textContent=corrections.length+' correction records. Original scan remains immutable.';
      F.workspace.replaceChildren(heading,summary,heat,footer);
    }catch(error){F.say(error.message);}
  };
  const iiif = async () => { const url=(F.future.rest||'').replace(/future\/$/,'')+`future/iiif/${encodeURIComponent(F.cfg.publicId)}/manifest`;try{const r=await fetch(url,{credentials:'same-origin',headers:{'X-WP-Nonce':F.future.nonce||F.cfg.nonce||''}}),d=await r.json();if(!r.ok)throw new Error(d?.message||`IIIF request failed (${r.status})`);F.openPanel(true);F.setWorkspace(`<h3>IIIF Presentation 3 Manifest</h3><p>Canvases: ${d.items?.length||0}. Rights-aware grants remain short-lived.</p><textarea class="pldr-f24-export" readonly>${F.escape(JSON.stringify(d,null,2))}</textarea>`);}catch(e){F.say(e.message);}};
  const heatmap = async () => {
    const query=prompt('Search word or phrase for heatmap:');
    if(!query?.trim())return;
    F.openPanel(true);
    try{
      const d=await F.api('search-heatmap/'+F.editionId+'?q='+encodeURIComponent(query));
      const pages=Array.isArray(d.pages)?d.pages:[];
      const counts=pages.map(item=>Number(item.matches));
      const max=Math.max(1,...counts.filter(Number.isFinite));
      const wrap=document.createElement('div');
      const heading=document.createElement('h3');heading.textContent='Search heatmap';
      const summary=document.createElement('p');
      summary.textContent=String(d.matches??0)+' matches across '+pages.length+' pages.';
      const bars=document.createElement('div');bars.className='pldr-f24-heatmap';
      pages.forEach(item=>{
        const b=document.createElement('button');b.type='button';
        b.title='Page '+String(item.page??'?')+': '+String(item.matches??0)+' matches';
        const count=Number(item.matches);
        b.style.setProperty('--heat',String(Number.isFinite(count)?Math.max(.1,Math.min(1,count/max)):.1));
        b.textContent=String(item.page??'?');
        b.addEventListener('click',()=>jump(item.page));
        bars.appendChild(b);
      });
      wrap.append(heading,summary,bars);
      F.workspace.replaceChildren(wrap);
    }catch(error){F.setWorkspace('<p>'+F.escape(error.message)+'</p>');}
  };

  root.addEventListener('click', async e => { const b=e.target.closest('button[data-f24-action]');if(!b)return;const a=b.dataset.f24Action;if(a==='outline')await outline();if(a==='compare')await compare();if(a==='anchor')await anchor();if(a==='citation-export')await citation();if(a==='annotation-export')await annotations();if(a==='ocr-lab')await ocr();if(a==='iiif')await iiif();if(a==='heatmap')await heatmap(); });
})();
