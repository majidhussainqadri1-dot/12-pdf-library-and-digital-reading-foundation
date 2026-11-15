'use strict';
/**
 * R78 RED-first: offline PDF cache must not open HTML or arbitrary data as an origin-bearing Blob URL.
 * Same device / edition but different logged-in identity MUST NOT open
 * a previous user's encrypted cached book, even if currently offline.
 * Actual future-reader-vault.js with real AES-GCM, synthetic IDB; no live site.
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
async function simulate({online=false,authorized=false,retryOffline=false,actorId=17,cachedActorId=17,mime='application/pdf',body='%PDF-1.7\nsynthetic-verified-pdf-bytes'}){
  const editionId=700;
  const bytes=Buffer.from(body);
  const sha=createHash('sha256').update(bytes).digest('hex');
  const k=await webcrypto.subtle.generateKey({name:'AES-GCM',length:256},false,['encrypt','decrypt']);
  const iv=webcrypto.getRandomValues(new Uint8Array(12));
  const aad=new TextEncoder().encode('pldr-offline-v1:'+editionId+':0:0');
  const cipher=await webcrypto.subtle.encrypt({name:'AES-GCM',iv,additionalData:aad},k,bytes);
  const idb=makeDb({
    keys:[['device-key',k]],
    chunks:[['700:0',{iv:Array.from(iv),cipher,offset:0,aadVersion:1}]],
    meta:[['700',{editionId,size:bytes.length,sha256:sha,chunks:1,mime,
      offlineValidUntil:'2099-01-01T00:00:00Z',aadVersion:1,actorId:cachedActorId}]]
  });
  const handlers={},messages=[];
  let authCalls=0,opens=0,openedType='';
  const F={
    editionId,future:{loggedIn:true,userId:actorId},
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
      createObjectURL:blob=>{openedType=blob.type;return '/synthetic-local-blob';},revokeObjectURL(){}
    },setTimeout(){return 1;},clearTimeout(){},console};
  vm.runInNewContext(source,sandbox,{timeout:2000,filename:'future-reader-vault.js'});
  handlers['root:click']({target:{closest(){return {dataset:{f24Action:'offline'}};}}});
  await handlers['[data-vault-open]']();
  if(retryOffline){navigator.onLine=false;await handlers['[data-vault-open]']();}
  return {authCalls,opens,openedType,messages,storedReauthDenied:saved.get('pldrOfflineReauthDenied:'+editionId)||null};
}
(async()=>{
  const safe=await simulate({});
  assert.equal(safe.opens,1,'a source-authenticated PDF-like blob must be available offline');
  assert.equal(safe.openedType,'application/pdf','successful offline blob must be rendered as PDF only');
  const htmlType=await simulate({mime:'text/html'});
  assert.equal(htmlType.opens,0,'an HTML MIME type from local metadata must not open an origin-bearing Blob URL');
  assert.ok(htmlType.messages.some(m=>/pdf|format|mime|type|invalid|unsafe/i.test(m)),
    'unsafe cached MIME must show an understandable format rejection');
  const htmlBytes=await simulate({body:'<!doctype html><script>document.title="untrusted"</script>'});
  assert.equal(htmlBytes.opens,0,'HTML byte content with a forged PDF MIME must not open in a PDF reader');
  const jsType=await simulate({mime:'application/javascript'});
  assert.equal(jsType.opens,0,'cached executable JavaScript MIME must not be opened as PDF');
  console.log('R78 PDF-only signed offline Blob MIME + header open guard PASS.');
})().catch(error=>{console.error(error.stack||error);process.exit(1);});
