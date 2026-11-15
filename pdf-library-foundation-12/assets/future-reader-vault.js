(() => {
  'use strict';
  const F=window.PLDRF24;if(!F)return;
  const DB='pldr-offline-v1';
  const currentActor=Number(F.future?.userId||0);
  const validActor=()=>Number.isSafeInteger(currentActor)&&currentActor>0;
  const openDb=()=>new Promise((resolve,reject)=>{const r=indexedDB.open(DB,1);r.onupgradeneeded=()=>{const db=r.result;if(!db.objectStoreNames.contains('keys'))db.createObjectStore('keys');if(!db.objectStoreNames.contains('chunks'))db.createObjectStore('chunks');if(!db.objectStoreNames.contains('meta'))db.createObjectStore('meta');};r.onsuccess=()=>resolve(r.result);r.onerror=()=>reject(r.error||new Error('Unable to open the encrypted offline vault.'));r.onblocked=()=>reject(new Error('The encrypted offline vault is blocked by another open browser context.'));});
  const req=async(store,mode,key,value)=>{const db=await openDb();try{return await new Promise((resolve,reject)=>{const tx=db.transaction(store,mode),os=tx.objectStore(store),r=value===undefined?os.get(key):os.put(value,key);let result,requestDone=false,settled=false;const fail=(e)=>{if(settled)return;settled=true;reject(e||new Error('Offline vault transaction failed.'));};r.onsuccess=()=>{result=r.result;requestDone=true;if(mode==='readonly'&&!settled){settled=true;resolve(result);}};r.onerror=()=>fail(r.error||new Error('Offline vault request failed.'));tx.onerror=()=>fail(tx.error||new Error('Offline vault transaction failed.'));tx.onabort=()=>fail(tx.error||new Error('Offline vault transaction was aborted.'));tx.oncomplete=()=>{if(mode!=='readonly'&&!settled){if(!requestDone)return fail(new Error('Offline vault write completed without a request result.'));settled=true;resolve(result);}};});}finally{try{db.close();}catch(_){}}};
  const key=async()=>{let k=await req('keys','readonly','device-key');if(k)return k;k=await crypto.subtle.generateKey({name:'AES-GCM',length:256},false,['encrypt','decrypt']);await req('keys','readwrite','device-key',k);return k;};
  const aad=(index,offset)=>new TextEncoder().encode(`${DB}:${F.editionId}:${index}:${offset}`);
  const clearEdition=async(announce=true)=>{try{const db=await openDb(),tx=db.transaction(['chunks','meta'],'readwrite'),store=tx.objectStore('chunks'),r=store.openCursor();r.onsuccess=()=>{const c=r.result;if(c){if(String(c.key).startsWith(`${F.editionId}:`))c.delete();c.continue();}};tx.objectStore('meta').delete(String(F.editionId));await new Promise((res,rej)=>{tx.oncomplete=res;tx.onerror=()=>rej(tx.error||new Error('Offline vault clear failed.'));tx.onabort=()=>rej(tx.error||new Error('Offline vault clear was aborted.'));});db.close();if(announce)F.say('Offline vault cleared for this edition.');return true;}catch(e){if(announce)F.say(e.message);return false;}};
  const clearAllOnce=async()=>new Promise(resolve=>{if(!window.indexedDB)return resolve(true);const r=indexedDB.deleteDatabase(DB);r.onsuccess=()=>resolve(true);r.onerror=r.onblocked=()=>resolve(false);});
  const clearAll=async()=>{for(let attempt=0;attempt<3;attempt++){if(await clearAllOnce())return true;await new Promise(resolve=>setTimeout(resolve,250*(attempt+1)));}return false;};
  const purgeLoggedOut=async()=>{const ok=await clearAll();try{if(ok){localStorage.removeItem('pldrOfflinePurgePending');localStorage.removeItem('pldrOfflineReconnectReauth');localStorage.removeItem('pldrOfflineReauthDenied:'+F.editionId);}else localStorage.setItem('pldrOfflinePurgePending','1');}catch(_){}if(!ok)console.warn('[PLDR] Encrypted offline vault purge remains pending and will retry on a later logged-out page load.');};
  const reconnectPending=()=>{try{return localStorage.getItem('pldrOfflineReconnectReauth')==='1';}catch(_){return false;}};
  const markReconnectPending=()=>{try{localStorage.setItem('pldrOfflineReconnectReauth','1');}catch(_){}};
  const clearReconnectPending=()=>{try{localStorage.removeItem('pldrOfflineReconnectReauth');}catch(_){}};
  // Once this device has actually observed a failed online rights recheck,
  // later going offline must not reopen a cached book under stale rights.
  // This is distinct from the ordinary "offline event" flag: users must
  // retain permitted local reading during an initial genuine offline period.
  const deniedKey='pldrOfflineReauthDenied:'+F.editionId;
  const offlineReauthDenied=()=>{try{return localStorage.getItem(deniedKey)==='1';}catch(_){return true;}};
  const markOfflineReauthDenied=()=>{try{localStorage.setItem(deniedKey,'1');return true;}catch(_){return false;}};
  const clearOfflineReauthDenied=()=>{try{localStorage.removeItem(deniedKey);return true;}catch(_){return false;}};

  const reauthorizeAfterReconnect=async()=>{if(!F.future.loggedIn||!navigator.onLine)return false;try{
    const d=await F.api('offline-authorization/'+F.editionId);
    if(d?.authorized!==true||Number(d?.actor_id)!==currentActor||!validActor())
      throw new Error('Offline authorization or current account identity is no longer valid.');
    if(!clearOfflineReauthDenied())throw new Error('Offline authorization could not be recorded locally.');
    clearReconnectPending();
    return true;
  }catch(e){
    // Keep a durable deny latch so an online revocation cannot be bypassed
    // by toggling offline or restarting the browser. If storage is disabled,
    // purge the cached ciphertext instead of making an unverifiable promise.
    const durable=markOfflineReauthDenied();
    if(!durable)await clearEdition(false);
    F.say((e.message||'Offline authorization could not be revalidated after reconnect.')+
      (durable?' The local copy remains locked until online authorization succeeds.':
        ' Local authorization could not be persisted, so cached data was purged.'));
    return false;
  }};
  // Offline reading is intentionally bounded while the browser's native
  // SHA-256 API requires a complete ArrayBuffer. A larger vault must use a
  // separately audited incremental digest, not silently skip verification.
  const MAX_OFFLINE_BYTES=128*1024*1024;
  const validDigest=value=>typeof value==='string'&&/^[a-f0-9]{64}$/i.test(value);
  const digestBlob=async blob=>{
    const result=await crypto.subtle.digest('SHA-256',await blob.arrayBuffer());
    return [...new Uint8Array(result)].map(x=>x.toString(16).padStart(2,'0')).join('');
  };
  const verifyOfflineRange=async (response,start,end,total)=>{
    if(response.status!==206)throw new Error('Offline range must return HTTP 206 Partial Content.');
    const value=response.headers?.get?.('Content-Range')||'';
    const match=/^bytes ([0-9]+)-([0-9]+)\/([0-9]+)$/.exec(value.trim());
    if(!match||match[1]!==String(start)||match[2]!==String(end)||match[3]!==String(total)){
      throw new Error('Offline Content-Range does not match the requested source bytes.');
    }
    const bytes=await response.arrayBuffer();
    if(bytes.byteLength!==end-start+1)throw new Error('Offline range byte length mismatch.');
    return bytes;
  };
  const store=async()=>{
    if(!window.crypto?.subtle||!window.indexedDB){
      F.say('Encrypted offline vault is unavailable in this browser.');return;
    }
    if(!F.future.loggedIn||!validActor()){F.say('Verified WordPress account identity is required for private offline reading.');return;}
    F.say('Requesting rights-aware offline grant…');
    const plaintext=[];
    try{
      const grant=await F.api('offline-grant',{method:'POST',body:{edition_id:F.editionId},
        headers:{'Idempotency-Key':F.idempotency('offline')}});
      if(Number(grant?.actor_id)!==currentActor){
        throw new Error('Offline grant account identity does not match the current WordPress user.');
      }
      const size=Number(grant?.size);
      const expectedHash=String(grant?.sha256||'').toLowerCase();
      const expiry=Date.parse(grant?.offline_valid_until||'');
      if(!Number.isSafeInteger(size)||size<1||size>MAX_OFFLINE_BYTES||
         !validDigest(expectedHash)||!Number.isFinite(expiry)||expiry<=Date.now()||
         typeof grant?.url!=='string'||!grant.url){
        throw new Error('Invalid offline manifest, SHA-256, expiry or size; local offline capture is limited to 128 MiB.');
      }
      const k=await key();
      if(!await clearEdition(false))throw new Error('Existing offline-vault data could not be cleared safely.');
      const chunkSize=2*1024*1024;
      let offset=0,index=0;
      while(offset<size){
        const end=Math.min(size-1,offset+chunkSize-1);
        const response=await fetch(grant.url,{headers:{Range:'bytes='+offset+'-'+end},credentials:'same-origin'});
        if(response.status===410||response.status===403){
          throw new Error('Offline grant expired during capture; restart to revalidate current rights.');
        }
        const plain=await verifyOfflineRange(response,offset,end,size);
        plaintext.push(plain);
        const iv=crypto.getRandomValues(new Uint8Array(12));
        const cipher=await crypto.subtle.encrypt({name:'AES-GCM',iv,
          additionalData:aad(index,offset)},k,plain);
        await req('chunks','readwrite',F.editionId+':'+index,{
          iv:Array.from(iv),cipher,offset,aadVersion:1
        });
        offset+=plain.byteLength;index++;
        F.say('Encrypted offline vault: '+Math.floor((offset/size)*100)+'%');
      }
      const complete=new Blob(plaintext,{type:grant.mime_type||'application/pdf'});
      if(complete.size!==size||await digestBlob(complete)!==expectedHash){
        throw new Error('Offline SHA-256 checksum mismatch: captured bytes differ from the authorized PDF.');
      }
      await req('meta','readwrite',String(F.editionId),{
        editionId:F.editionId,actorId:currentActor,size,sha256:expectedHash,filename:grant.filename,
        mime:grant.mime_type,chunks:index,offlineValidUntil:grant.offline_valid_until,
        storedAt:Date.now(),aadVersion:1,
        policy:'128 MiB max; exact ranges and full SHA-256 verified; encrypted non-extractable WebCrypto key; expiry and reconnect authorization'
      });
      clearOfflineReauthDenied();clearReconnectPending();
      F.say('Encrypted offline vault stored with a non-extractable WebCrypto key; original file SHA-256 verified.');
    }catch(error){await clearEdition(false);F.say(error.message||'Encrypted offline vault failed.');}finally{
      for(const part of plaintext){try{new Uint8Array(part).fill(0);}catch(_){}}
      plaintext.length=0;
    }
  };
  const open=async()=>{
    try{
      const meta=await req('meta','readonly',String(F.editionId));
      if(!meta){F.say('No encrypted offline copy exists for this edition.');return;}
      if(!F.future?.loggedIn||!validActor()||Number(meta.actorId)!==currentActor){
        F.say('Cached offline PDF is bound to another or unverified WordPress account; switch to the original account and obtain current rights.');
        return;
      }
      const expiry=Date.parse(meta.offlineValidUntil||'');
      if(!expiry||Date.now()>=expiry){
        await clearEdition(false);
        F.say('Offline-vault authorization expired and the local copy was purged.');return;
      }
      if(Number(meta.aadVersion)!==1){
        await clearEdition(false);
        F.say('This offline-vault copy uses an obsolete integrity format and was purged; store it again.');return;
      }
      const size=Number(meta.size),sha=String(meta.sha256||'').toLowerCase(),count=Number(meta.chunks);
      if(!Number.isSafeInteger(size)||size<1||size>MAX_OFFLINE_BYTES||!validDigest(sha)||
         !Number.isSafeInteger(count)||count<1||count>Math.ceil(MAX_OFFLINE_BYTES/(64*1024))){
        throw new Error('Offline-vault manifest has invalid size, SHA-256 or chunk count.');
      }
      // Online openings must always recheck owner rights; the offline event may have been missed by a closed browser.
      if(!navigator.onLine&&offlineReauthDenied()){F.say('Offline cache was denied after a previous online rights check; reconnect and obtain authorization first.');return;}
      if(navigator.onLine&&!await reauthorizeAfterReconnect())return;
      const k=await key(),chunks=[];
      let nextOffset=0;
      for(let i=0;i<count;i++){
        const row=await req('chunks','readonly',F.editionId+':'+i);
        if(!row||Number(row.aadVersion)!==1||Number(row.offset)!==nextOffset){
          throw new Error('Offline vault contains an incomplete, reordered or obsolete encrypted chunk.');
        }
        const plain=await crypto.subtle.decrypt({name:'AES-GCM',iv:new Uint8Array(row.iv),
          additionalData:aad(i,nextOffset)},k,row.cipher);
        if(!plain.byteLength||plain.byteLength>2*1024*1024){
          throw new Error('Offline decrypted chunk length is invalid.');
        }
        chunks.push(plain);nextOffset+=plain.byteLength;
      }
      const blob=new Blob(chunks,{type:meta.mime||'application/pdf'});
      if(blob.size!==size||nextOffset!==size){
        throw new Error('Offline-vault byte count mismatch.');
      }
      if(await digestBlob(blob)!==sha){
        throw new Error('Offline-vault SHA-256 checksum mismatch; the original PDF cannot be trusted.');
      }
      const url=URL.createObjectURL(blob);
      window.open(url,'_blank','noopener');
      setTimeout(()=>URL.revokeObjectURL(url),1200000);
      F.say('Opened verified encrypted offline copy. Local authorization expires '+new Date(expiry).toLocaleString()+'.');
    }catch(error){
      await clearEdition(false);
      F.say((error.message||'Encrypted offline vault could not be opened.')+' The damaged/incomplete local copy was purged.');
    }
  };
  const show=()=>{F.openPanel(true);F.setWorkspace('<h3>Encrypted Offline Reading Vault</h3><p>Eligible content is captured only after server authorization. Browser chunks are AES-GCM encrypted with a non-extractable CryptoKey. Local expiry is enforced, server refresh reauthorizes rights, and logout/explicit purge removes local material.</p><div><button type="button" data-vault-store>Store encrypted offline</button> <button type="button" data-vault-open>Open offline copy</button> <button type="button" data-vault-clear>Clear offline copy</button></div>');F.workspace.querySelector('[data-vault-store]')?.addEventListener('click',store);F.workspace.querySelector('[data-vault-open]')?.addEventListener('click',open);F.workspace.querySelector('[data-vault-clear]')?.addEventListener('click',()=>clearEdition(true));};
  F.root.addEventListener('click',e=>{const b=e.target.closest('button[data-f24-action]');if(b?.dataset.f24Action==='offline')show();});
  window.addEventListener('offline',markReconnectPending);
  window.addEventListener('online',()=>{if(reconnectPending())reauthorizeAfterReconnect();});
  if(!F.future.loggedIn)purgeLoggedOut();
})();
