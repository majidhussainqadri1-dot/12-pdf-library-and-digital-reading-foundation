'use strict';
/**
 * R69 RED-first source behavior: a lawful anonymous reader must not send
 * private /reading/items or /reading/progress requests. Authenticated users
 * must continue saving their own private progress and notes.
 * No web, WordPress, PDF upload or production change occurs in this test.
 */
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const source=fs.readFileSync(path.join(__dirname,'..','pdf-library-foundation-12','assets','reader.js'),'utf8');
async function simulate(canSavePrivate){
  const calls=[];
  const timers=[];
  const frame={src:'',addEventListener(){}};
  const pageInput={value:'',addEventListener(){}};
  const status={textContent:''};
  const privateItems={innerHTML:'',textContent:'',querySelector(){return null;}};
  const root={
    querySelector(selector){
      if(selector==='[data-frame]')return frame;
      if(selector==='[data-page]')return pageInput;
      if(selector==='.pldr-reader-status')return status;
      if(selector==='[data-private-items]')return privateItems;
      return null;
    },
    querySelectorAll(){return [];},
    addEventListener(){}
  };
  const cfg={
    editionId:101,pages:5,startPage:1,url:'/library/delivery/synthetic/',
    progressRevision:'',canSavePrivate,
    rest:'/wp-json/pldr/v1/',nonce:'',
    strings:{loading:'Loading',saved:'Saved privately'}
  };
  const window={
    PLDR_READER:cfg,location:{hash:''},
    clearTimeout(){},
    setTimeout(fn,delay){timers.push({fn,delay});return timers.length;},
    matchMedia(){return {matches:false};}
  };
  const document={querySelector(sel){return sel==='[data-pldr-reader]'?root:null;}};
  const fetch=async(url,opts={})=>{
    calls.push({url:String(url),method:opts.method||'GET'});
    return {ok:true,status:200,json:async()=>({
      items:[],updated_at:'2026-11-14 15:00:01'
    })};
  };
  const sandbox={window,document,fetch,crypto:{randomUUID:()=>'synthetic-id'},
    console,FormData:function(){},navigator:{},Blob:function(){}};
  vm.runInNewContext(source,sandbox,{timeout:2000,filename:'reader.js'});
  // Simulate the 700 ms debounce without wall-clock sleeping.
  for(const item of timers.filter(t=>t.delay===700))item.fn();
  for(let i=0;i<6;i++)await Promise.resolve();
  return {calls,status:status.textContent,frame:frame.src};
}
(async()=>{
  for(const guestConfig of [false,undefined]){
    const guest=await simulate(guestConfig);
    assert.equal(guest.calls.length,0,
      'R69 guest issued private REST calls: '+JSON.stringify(guest.calls));
    assert.ok(guest.frame.startsWith('/library/delivery/synthetic/'),
      'public reader frame should remain usable without private state calls');
  }
  const user=await simulate(true);
  assert.ok(user.calls.some(c=>c.method==='GET' && c.url.includes('reading/items')),
    'authenticated reader must retain own private note loading');
  assert.ok(user.calls.some(c=>c.method==='POST' && c.url.includes('reading/progress')),
    'authenticated reader must retain own private progress save');
  console.log('R69 guest private reader request suppression, authenticated regression: PASS.');
})().catch(e=>{console.error(e.stack||e);process.exit(1);});
