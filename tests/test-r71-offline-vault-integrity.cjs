'use strict';
/**
 * R71 RED-first: actual encrypted offline-vault JS must reject malformed 206
 * byte ranges and valid-looking wrong bytes (SHA-256 mismatch). Isolated fake
 * IndexedDB + real WebCrypto; no Hostinger, books, credentials, or live claims.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const {createHash,webcrypto}=require('node:crypto');
const vault=fs.readFileSync(path.join(__dirname,'..','pdf-library-foundation-12','assets','future-reader-vault.js'),'utf8');
const original=Buffer.from('%PDF-1.7\\nsynthetic-test-pdf-byte-content-2026','ascii');
const expectedHash=createHash('sha256').update(original).digest('hex');

function fakeIndexedDb(){
  const stores=new Map(['keys','chunks','meta'].map(k=>[k,new Map()]));
  const db={
    objectStoreNames:{contains:n=>stores.has(n)},
    createObjectStore(n){if(!stores.has(n))stores.set(n,new Map());},
    close(){},
    transaction(_names,mode){
      let pending=0,completed=false;
      const tx={oncomplete:null,onerror:null,onabort:null,objectStore(name){
        const store=stores.get(name);
        assert.ok(store,'unknown IndexedDB store '+name);
        const operation=(getValue)=>{
          pending++;
          const req={result:undefined,onsuccess:null,onerror:null};
          queueMicrotask(()=>{
            try {req.result=getValue();req.onsuccess?.();}
            catch(error){req.error=error;req.onerror?.();tx.onerror?.();}
            finally {
              pending--;
              if(!pending&&!completed)queueMicrotask(()=>{
                if(!pending&&!completed){completed=true;tx.oncomplete?.();}
              });
            }
          });
          return req;
        };
        return {
          get(key){return operation(()=>store.get(key));},
          put(value,key){return operation(()=>{store.set(key,value);return key;});},
          delete(key){return operation(()=>store.delete(key));},
          openCursor(){return operation(()=>null);}
        };
      }};
      return tx;
    }
  };
  const idb={
    open(){
      const request={result:db,onupgradeneeded:null,onsuccess:null,onerror:null};
      queueMicrotask(()=>request.onsuccess?.());
      return request;
    },
    deleteDatabase(){
      const request={onsuccess:null,onerror:null,onblocked:null};
      queueMicrotask(()=>{for(const store of stores.values())store.clear();request.onsuccess?.();});
      return request;
    }
  };
  return {idb,stores};
}
async function simulate(kind){
  const {idb,stores}=fakeIndexedDb();
  const handlers={};
  const messages=[];
  let ranges=0;
  const F={
    editionId:700, future:{loggedIn:true,userId:17},
    api:async name=>{
      if(name!=='offline-grant')throw new Error('Unexpected endpoint '+name);
      return {
        url:'/synthetic-offline',
        size:original.length,
        sha256:kind==='missing-hash'?'':expectedHash,
        actor_id:kind==='foreign-actor'?18:17,
        filename:'licensed-synthetic.pdf',mime_type:'application/pdf',
        offline_valid_until:'2099-01-01T00:00:00Z'
      };
    },
    idempotency:()=> 'synthetic-test-key',
    say:s=>messages.push(String(s)),
    openPanel(){},
    setWorkspace(){
      this.workspace={querySelector(sel){
        return {addEventListener(_event,cb){handlers[sel]=cb;}};
      }};
    },
    root:{addEventListener(event,cb){handlers['root:'+event]=cb;}}
  };
  const fetch=async(url,options={})=>{
    assert.equal(String(url),'/synthetic-offline');
    const match=/^bytes=(\d+)-(\d+)$/.exec(options.headers?.Range||'');
    assert.ok(match,'range request required');
    const start=Number(match[1]),end=Number(match[2]);
    ranges++;
    let body=original.subarray(start,end+1);
    if(kind==='wrong-digest')body=Buffer.from('X'.repeat(body.length));
    if(kind==='truncated')body=original.subarray(start,end);
    const responseStart=kind==='wrong-content-range'?start+1:start;
    return {
      ok:true,status:kind==='http-200'?200:206,
      headers:{get(field){return String(field).toLowerCase()==='content-range'
        ?'bytes '+responseStart+'-'+end+'/'+original.length:null;}},
      arrayBuffer:async()=>Uint8Array.from(body).buffer
    };
  };
  const window={
    PLDRF24:F,indexedDB:idb,crypto:webcrypto,
    addEventListener(){}
  };
  const storage=new Map();
  const localStorage={
    setItem(k,v){storage.set(k,String(v));},getItem:k=>storage.get(k)||null,
    removeItem:k=>storage.delete(k)
  };
  const sandbox={window,indexedDB:idb,crypto:webcrypto,localStorage,
    navigator:{onLine:true},fetch,Blob,TextEncoder,Date,
    setTimeout(_cb){return 1;},clearTimeout(){},console};
  vm.runInNewContext(vault,sandbox,{timeout:2000,filename:'future-reader-vault.js'});
  assert.equal(typeof handlers['root:click'],'function');
  handlers['root:click']({target:{closest(){return {dataset:{f24Action:'offline'}};}}});
  assert.equal(typeof handlers['[data-vault-store]'],'function');
  await handlers['[data-vault-store]']();
  return {messages,ranges,meta:stores.get('meta').get('700')};
}
(async()=>{
  const success=await simulate('valid');
  assert.ok(success.meta,'exact ranges and matching SHA-256 must store encrypted vault metadata');
  assert.ok(success.messages.some(x=>/stored with a non-extractable/i.test(x)));
  for(const kind of ['http-200','wrong-content-range','truncated','wrong-digest','missing-hash','foreign-actor']){
    const result=await simulate(kind);
    assert.equal(result.meta,undefined,kind+' must not persist a false verified offline copy');
    assert.equal(result.ranges,['missing-hash','foreign-actor'].includes(kind)?0:1,kind+' must stop at the first invalid grant/response');
    assert.ok(result.messages.some(x=>/range|checksum|digest|sha-256|invalid|manifest|actor|account|identity/i.test(x)),
      kind+' must explain why capture was rejected: '+JSON.stringify(result.messages));
  }
  console.log('R71 offline-vault exact byte ranges and full SHA-256: PASS.');
})().catch(error=>{console.error(error.stack||error);process.exit(1);});
