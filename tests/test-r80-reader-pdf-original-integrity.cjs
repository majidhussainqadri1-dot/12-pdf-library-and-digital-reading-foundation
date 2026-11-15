'use strict';
/**
 * File12 R80 RED-first: reject a non-PDF original even if HTTP ranges and
 * advertised SHA-256 match. Reject untrusted manifest MIME/extension.
 * Runs actual assets/reader.js in an isolated synthetic VM; no real site.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const {createHash,webcrypto}=require('node:crypto');
const reader=fs.readFileSync(path.join(__dirname,'..','pdf-library-foundation-12','assets','reader.js'),'utf8');
const validPdf=Buffer.from('%PDF-1.7\nabcX','ascii');
const fakeHtml=Buffer.from('<html>oops!!','ascii');
const sha=createHash('sha256').update(validPdf).digest('hex');
const other='a'.repeat(64)===sha?'b'.repeat(64):'a'.repeat(64);
function grant(kind){
  const body=kind==='fake-pdf' ? fakeHtml : validPdf;
  const hash=createHash('sha256').update(body).digest('hex');
  return {job_id:'synthetic-job',delivery:{url:'/synthetic-signed-range',
    size:body.length,sha256:hash,filename:kind==='unsafe-extension'?'payload.html':'licensed-synthetic.pdf',
    mime_type:kind==='wrong-mime'?'text/html':'application/pdf'},range_bytes:4,checksum:'sha256:'+hash};
}
async function scenario(kind){
  let sessionCount=0;
  const rangeRequests=[];
  const content=kind==='fake-pdf'?fakeHtml:validPdf;
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
      return {ok:true,status:200,json:async()=>grant(kind)};
    }
    if(String(url)!=='/synthetic-signed-range')throw new Error('Unrecognized synthetic URL '+url);
    const range=(options.headers||{}).Range;
    const m=/^bytes=(\d+)-(\d+)$/.exec(range||'');
    assert.ok(m,'A bounded HTTP Range request is required');
    const start=Number(m[1]),end=Number(m[2]);
    rangeRequests.push([start,end]);
    const code=206;
    let responseStart=start,responseEnd=end;
    if(kind==='wrong-range')responseStart=start+1;
    const part=content.subarray(start,end+1);
    return {
      ok:true,status:code,
      headers:{get(n){return String(n).toLowerCase()==='content-range'?('bytes '+responseStart+'-'+responseEnd+'/'+content.length):null;}},
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
  const legitimate=await scenario('valid');
  assert.equal(legitimate.attachmentClicks,1,'valid tiny PDF fixture remains downloadable');
  const html=await scenario('fake-pdf');
  assert.equal(html.attachmentClicks,0,'HTML-disguised original must never be saved, even when SHA-256 matches');
  assert.match(html.dmStatus,/PDF|signature|header|file/i,'non-PDF bytes must have a clear rejection');
  const mime=await scenario('wrong-mime');
  assert.equal(mime.attachmentClicks,0,'non-PDF manifest MIME must be denied');
  assert.match(mime.dmStatus,/mime|PDF|type/i,'bad MIME must be explained');
  const ext=await scenario('unsafe-extension');
  assert.equal(ext.attachmentClicks,0,'unsafe non-PDF attachment filename must be rejected');
  assert.match(ext.dmStatus,/extension|filename|PDF/i,'unsafe extension must be explained');
  console.log('R80 signed PDF download filename/MIME/header content gates: PASS');
})().catch(error=>{console.error(error.stack||error);process.exit(1);});
