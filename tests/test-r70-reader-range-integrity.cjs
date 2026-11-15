'use strict';
/**
 * File12 R70 RED-first: signed PDF downloads must reject untrustworthy HTTP
 * byte-range responses and a refreshed grant for different object bytes.
 * Runs actual assets/reader.js in an isolated synthetic VM; no real site.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const {createHash,webcrypto}=require('node:crypto');
const reader=fs.readFileSync(path.join(__dirname,'..','pdf-library-foundation-12','assets','reader.js'),'utf8');
const original=Buffer.from('abcdefghijkl','ascii');
const sha=createHash('sha256').update(original).digest('hex');
const other='a'.repeat(64)===sha?'b'.repeat(64):'a'.repeat(64);
function grant(hash=sha){
  return {job_id:'synthetic-job',delivery:{url:'/synthetic-signed-range',
    size:original.length,sha256:hash,filename:'licensed-synthetic.pdf',
    mime_type:'application/pdf'},range_bytes:4,checksum:'sha256:'+hash};
}
async function scenario(kind){
  let sessionCount=0;
  const rangeRequests=[];
  let attachmentClicks=0;
  const handlers={};
  const dm={hidden:true,scrollIntoView(){}};
  const dmStatus={textContent:''},dmChecksum={textContent:''};
  const progress={value:0};
  const frame={src:'',addEventListener(){}};
  const button=(name)=>({addEventListener(type,fn){handlers[name+':'+type]=fn;}});
  const start=button('start'),pause=button('pause'),resume=button('resume');
  const root={
    querySelector(sel){
      return ({'[data-download-manager]':dm,'[data-download-status]':dmStatus,
        '[data-download-checksum]':dmChecksum,'[data-download-progress]':progress,
        '[data-download-start]':start,'[data-download-pause]':pause,
        '[data-download-resume]':resume,'[data-frame]':frame})[sel]||null;
    },
    querySelectorAll(){return [];},
    addEventListener(type,fn){handlers['root:'+type]=fn;}
  };
  const document={
    querySelector(sel){return sel==='[data-pldr-reader]'?root:null;},
    createElement(tag){return {tagName:tag,click(){attachmentClicks++;},remove(){},set href(v){this._href=v;},set download(v){this._download=v;}};},
    body:{appendChild(){}}
  };
  const fetch=async(url,options={})=>{
    if(String(url).includes('/downloads/session')){
      sessionCount++;
      return {ok:true,status:200,json:async()=>grant(kind==='rotated-grant'&&sessionCount>1?other:sha)};
    }
    if(String(url)!=='/synthetic-signed-range')throw new Error('Unrecognized synthetic URL '+url);
    const range=(options.headers||{}).Range;
    const m=/^bytes=(\d+)-(\d+)$/.exec(range||'');
    assert.ok(m,'A bounded HTTP Range request is required');
    const start=Number(m[1]),end=Number(m[2]);
    rangeRequests.push([start,end]);
    if(kind==='rotated-grant'&&rangeRequests.length===1){
      return {ok:false,status:403,headers:{get:()=>null}};
    }
    const code=kind==='http-200'?200:206;
    let responseStart=start,responseEnd=end;
    if(kind==='wrong-range')responseStart=start+1;
    const part=kind==='short-body'?original.subarray(start,end):original.subarray(start,end+1);
    return {
      ok:true,status:code,
      headers:{get(n){return String(n).toLowerCase()==='content-range'?('bytes '+responseStart+'-'+responseEnd+'/'+original.length):null;}},
      blob:async()=>new Blob([part],{type:'application/pdf'})
    };
  };
  const window={
    PLDR_READER:{editionId:3,pages:2,startPage:1,url:'/synthetic-reader',
      canSavePrivate:false,canDownload:true,rest:'/wp-json/pldr/v1/',nonce:'',
      strings:{loading:'Loading'}},
    location:{hash:''},setTimeout(){return 1;},clearTimeout(){},
    matchMedia(){return {matches:true};},open(){},
  };
  const sandbox={window,document,fetch,Blob,FormData:class {},navigator:{},
    crypto:{randomUUID:()=>String(sessionCount),subtle:webcrypto.subtle},
    URL:{createObjectURL:()=>'/synthetic-blob',revokeObjectURL(){}},
    console,setTimeout(){},clearTimeout(){}};
  vm.runInNewContext(reader,sandbox,{filename:'reader.js',timeout:2000});
  const click=handlers['root:click'];
  assert.equal(typeof click,'function');
  await click({target:{closest(){return {dataset:{action:'download'}};}}});
  for(let n=0;n<10;n++)await Promise.resolve();
  assert.equal(typeof handlers['start:click'],'function');
  await handlers['start:click']();
  for(let n=0;n<10;n++)await Promise.resolve();
  return {rangeRequests,dmStatus:dmStatus.textContent,attachmentClicks,sessionCount};
}
(async()=>{
  const valid=await scenario('valid');
  assert.equal(valid.attachmentClicks,1,'correct 206 ranges must remain downloadable');
  assert.equal(valid.rangeRequests.length,3,'valid 4-byte ranges require exactly 3 requests');
  for(const kind of ['http-200','wrong-range','short-body']){
    const result=await scenario(kind);
    assert.equal(result.rangeRequests.length,1,kind+' must stop at the first untrusted range');
    assert.equal(result.attachmentClicks,0,kind+' must not create a download');
    assert.match(result.dmStatus,/range|length|mismatch|unexpected/i,
      kind+' must explain the rejected range');
  }
  const changed=await scenario('rotated-grant');
  assert.equal(changed.sessionCount,2,'expired token must obtain a replacement grant');
  assert.equal(changed.rangeRequests.length,1,'a different SHA-256 must not resume old chunks');
  assert.equal(changed.attachmentClicks,0,'replaced object version must not be downloaded');
  assert.match(changed.dmStatus,/checksum|object|changed|restart/i,
    'changed object identity must require a fresh download');
  console.log('R70 signed PDF byte-range and refreshed-object integrity: PASS');
})().catch(error=>{console.error(error.stack||error);process.exit(1);});
