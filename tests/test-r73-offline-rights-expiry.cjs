'use strict';
/**
 * R73 RED-first: current owner's shortened lawful expiry must constrain an offline copy; test must ask
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
          put(value,k){
            const req={onsuccess:null};queueMicrotask(()=>{store.set(k,value);req.onsuccess?.();tx.oncomplete?.();});return req;
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
  return {stores,open(){
    const req={result:db,onupgradeneeded:null,onsuccess:null,onerror:null};
    queueMicrotask(()=>req.onsuccess?.());return req;
  }};
}
async function simulate({online,authorized,validUntil=null,retryOffline=false,wrongEdition=false,moveTime=false}){
  const editionId=700;
  const bytes=Buffer.from('%PDF-1.7\\nsigned-synthetic-PDF-offline');
  const sha=createHash('sha256').update(bytes).digest('hex');
  const k=await webcrypto.subtle.generateKey({name:'AES-GCM',length:256},false,['encrypt','decrypt']);
  const iv=webcrypto.getRandomValues(new Uint8Array(12));
  const aad=new TextEncoder().encode('pldr-offline-v1:'+editionId+':0:0');
  const cipher=await webcrypto.subtle.encrypt({name:'AES-GCM',iv,additionalData:aad},k,bytes);
  const idb=makeDb({
    keys:[['device-key',k]],
    chunks:[['700:0',{iv:Array.from(iv),cipher,offset:0,aadVersion:1}]],
    meta:[['700',{editionId,size:bytes.length,sha256:sha,chunks:1,mime:'application/pdf',
      offlineValidUntil:'2099-01-01T00:00:00Z',aadVersion:1,actorId:17}]]
  });
  const handlers={},messages=[];
  let observedTime=Date.parse('2026-11-15T08:00:00Z');
  class ControlledDate extends Date {static now(){return observedTime;}}
  let authCalls=0,opens=0;
  const F={
    editionId,future:{loggedIn:true,userId:17},
    api:async endpoint=>{
      assert.equal(endpoint,'offline-authorization/'+editionId);
      authCalls++;
      return {authorized,actor_id:17,edition_id:wrongEdition?999:editionId,rights_valid_until:validUntil,checked_at:'2026-11-15T08:00:00Z'};
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
    navigator,Blob,TextEncoder,Date:ControlledDate,URL:{
      createObjectURL:()=>'/synthetic-local-blob',revokeObjectURL(){}
    },setTimeout(){return 1;},clearTimeout(){},console};
  vm.runInNewContext(source,sandbox,{timeout:2000,filename:'future-reader-vault.js'});
  handlers['root:click']({target:{closest(){return {dataset:{f24Action:'offline'}};}}});
  await handlers['[data-vault-open]']();
  const expiryAfterOnline=idb.stores.get('meta').get('700')?.offlineValidUntil;
  if(retryOffline){navigator.onLine=false;if(moveTime)observedTime=Date.parse('2026-11-18T08:00:00Z');await handlers['[data-vault-open]']();}
  return {authCalls,opens,messages,expiryAfterOnline,stillCached:idb.stores.get('meta').has('700')};
}
(async()=>{
  const shorter=await simulate({online:true,authorized:true,validUntil:'2026-11-17T00:00:00Z',retryOffline:true,moveTime:true});
  assert.equal(shorter.authCalls,1,'Online owner rights checked once');
  assert.equal(shorter.opens,1,'Cached PDF must not open offline after revised legal expiry');
  assert.equal(Date.parse(shorter.expiryAfterOnline||''),Date.parse('2026-11-17T00:00:00Z'),'New rights expiry must persist to cached manifest');
  const noNewExpiry=await simulate({online:true,authorized:true,validUntil:null,retryOffline:true,moveTime:true});
  assert.equal(noNewExpiry.opens,2,'No newly limited rights expiry preserves original bounded offline grant');
  const malformed=await simulate({online:true,authorized:true,validUntil:'bad-date'});
  assert.equal(malformed.opens,0,'Malformed current owner rights expiry must fail closed');
  const mismatched=await simulate({online:true,authorized:true,validUntil:'2026-11-17T00:00:00Z',wrongEdition:true});
  assert.equal(mismatched.opens,0,'An unrelated edition authorization cannot authorize this cached book');
  const genuineOffline=await simulate({online:false,authorized:false,validUntil:'2026-11-17T00:00:00Z'});
  assert.equal(genuineOffline.authCalls,0,'No network request in genuine offline mode');
  assert.equal(genuineOffline.opens,1,'Valid cached local license still opens when initially disconnected');
  console.log('R73 current owner shortened rights expiry and edition identity: PASS');
})().catch(e=>{console.error(e.stack||e);process.exit(1);});
