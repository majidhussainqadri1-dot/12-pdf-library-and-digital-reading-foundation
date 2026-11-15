'use strict';
/**
 * R72 RED-first: online opening of a locally cached encrypted PDF must ask
 * the current owner authorization even when the prior offline-event flag
 * was never persisted (e.g. browser closed offline, reopened online).
 * Actual future-reader-vault.js; in-memory crypto/IDB; no live site.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const {createHash,webcrypto}=require('node:crypto');
const source=fs.readFileSync(path.join(__dirname,'..','pdf-library-foundation-12','assets','future-reader-vault.js'),'utf8');
function makeDb(preloaded){
  const stores=new Map(Object.entries(preloaded).map(([k,v])=>[k,new Map(v)]));
  const db={
    objectStoreNames:{contains:n=>stores.has(n)},createObjectStore(n){stores.set(n,new Map());},
    close(){},
    transaction(_names,_mode){
      const tx={oncomplete:null,onerror:null,onabort:null,objectStore(name){
        const store=stores.get(name);
        assert.ok(store);
        return {
          get(k){
            const req={result:undefined,onsuccess:null,onerror:null};
            queueMicrotask(()=>{req.result=store.get(k);req.onsuccess?.();});
            return req;
          },
          delete(k){
            store.delete(k);const req={onsuccess:null};queueMicrotask(()=>{req.onsuccess?.();tx.oncomplete?.();});return req;
          },
          openCursor(){
            const req={result:null,onsuccess:null};queueMicrotask(()=>{req.onsuccess?.();tx.oncomplete?.();});return req;
          }
        };
      }};
      return tx;
    }
  };
  return {open(){
    const req={result:db,onupgradeneeded:null,onsuccess:null,onerror:null};
    queueMicrotask(()=>req.onsuccess?.());return req;
  }};
}
async function simulate({online,authorized,retryOffline=false}){
  const editionId=700;
  const bytes=Buffer.from('signed-synthetic-PDF-offline');
  const sha=createHash('sha256').update(bytes).digest('hex');
  const k=await webcrypto.subtle.generateKey({name:'AES-GCM',length:256},false,['encrypt','decrypt']);
  const iv=webcrypto.getRandomValues(new Uint8Array(12));
  const aad=new TextEncoder().encode('pldr-offline-v1:'+editionId+':0:0');
  const cipher=await webcrypto.subtle.encrypt({name:'AES-GCM',iv,additionalData:aad},k,bytes);
  const idb=makeDb({
    keys:[['device-key',k]],
    chunks:[['700:0',{iv:Array.from(iv),cipher,offset:0,aadVersion:1}]],
    meta:[['700',{editionId,size:bytes.length,sha256:sha,chunks:1,mime:'application/pdf',
      offlineValidUntil:'2099-01-01T00:00:00Z',aadVersion:1}]]
  });
  const handlers={},messages=[];
  let authCalls=0,opens=0;
  const F={
    editionId,future:{loggedIn:true},
    api:async endpoint=>{
      assert.equal(endpoint,'offline-authorization/'+editionId);
      authCalls++;
      return {authorized};
    },
    say:s=>messages.push(String(s)),
    openPanel(){},
    setWorkspace(){
      this.workspace={querySelector(sel){return {addEventListener(_event,cb){handlers[sel]=cb;}};}};
    },
    root:{addEventListener(name,cb){handlers['root:'+name]=cb;}}
  };
  const saved=new Map();
  const localStorage={getItem:k=>saved.get(k)||null,setItem(k,v){saved.set(k,String(v));},removeItem:k=>saved.delete(k)};
  const window={PLDRF24:F,indexedDB:idb,crypto:webcrypto,open(){opens++;},addEventListener(){}};
  const navigator={onLine:online};
  const sandbox={window,crypto:webcrypto,indexedDB:idb,localStorage,
    navigator,Blob,TextEncoder,Date,URL:{
      createObjectURL:()=>'/synthetic-local-blob',revokeObjectURL(){}
    },setTimeout(){return 1;},clearTimeout(){},console};
  vm.runInNewContext(source,sandbox,{timeout:2000,filename:'future-reader-vault.js'});
  handlers['root:click']({target:{closest(){return {dataset:{f24Action:'offline'}};}}});
  await handlers['[data-vault-open]']();
  if(retryOffline){navigator.onLine=false;await handlers['[data-vault-open]']();}
  return {authCalls,opens,messages,storedReauthDenied:saved.get('pldrOfflineReauthDenied:'+editionId)||null};
}
(async()=>{
  const revoked=await simulate({online:true,authorized:false});
  assert.equal(revoked.authCalls,1,'online offline-vault open must consult current rights even without reconnect flag');
  assert.equal(revoked.opens,0,'revoked online PDF must never open from stale encrypted cache');
  const afterDenial=await simulate({online:true,authorized:false,retryOffline:true});
  assert.equal(afterDenial.authCalls,1,'offline retry must not fabricate online request');
  assert.equal(afterDenial.opens,0,'after observed online revocation, stale offline copy must remain blocked');
  assert.equal(afterDenial.storedReauthDenied,'1','offline-revocation denial should be durable across browser restart');
  const approved=await simulate({online:true,authorized:true});
  assert.equal(approved.authCalls,1,'approved online open still revalidates server authorization');
  assert.equal(approved.opens,1,'approved online opening remains available');
  const offline=await simulate({online:false,authorized:false});
  assert.equal(offline.authCalls,0,'genuine offline reading must not invent a network request');
  assert.equal(offline.opens,1,'unexpired, intact offline file remains locally available while offline');
  console.log('R72 online current-rights recheck and legitimate offline access: PASS.');
})().catch(error=>{console.error(error.stack||error);process.exit(1);});
