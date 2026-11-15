'use strict';
/**
 * R74 supplementary RED: the actual personal reader must fail closed on
 * malformed companion URLs and avoid unescaped API metrics inside HTML.
 * Synthetic provider payloads only; no real users or server writes.
 */
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const source=fs.readFileSync(path.join(__dirname,'..','pdf-library-foundation-12','assets','future-reader-personal.js'),'utf8');
const htmlWrites=[],hrefWrites=[],safeText=[],handlers={};
const attack='<img src=x onerror=evilR74()>';
function node(name){
  return {tagName:name,children:[],hidden:false,dataset:{},style:{setProperty(){}},
    addEventListener(){},
    appendChild(child){this.children.push(child);return child;},
    append(...items){this.children.push(...items);},
    replaceChildren(...items){this.children=items;},
    set href(value){hrefWrites.push(String(value));this._href=String(value);},
    get href(){return this._href||'';},
    set innerHTML(value){htmlWrites.push(String(value));this._html=String(value);},
    get innerHTML(){return this._html||'';},
    set textContent(value){safeText.push(String(value));this._text=String(value);},
    get textContent(){return this._text||'';}
  };
}
const F={
  root:{addEventListener(type,fn){handlers[type]=fn;},querySelector(){return null;}},
  future:{loggedIn:true},editionId:55,lastSelection:'remedy',pageInput:null,sessionVersion:0,
  startedAt:0,lastHeartbeatAt:0,layout:'single',
  workspace:node('section'),contextBox:node('section'),
  page:()=>1,zoom:()=> 'page-width',openPanel(){},say(){},
  escape:value=>String(value??'').replace(/[&<>"']/g,ch=>({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[ch])),
  idempotency:()=> 'synthetic',
  setWorkspace(value){if(typeof value==='string')this.workspace.innerHTML=value;
    else this.workspace.replaceChildren(value);},
  api:async(route,opts={})=>{
    if(route.startsWith('context/'))return {items:[
      {title:attack,owner:'test',url:'javascript:evilR74()',summary:attack},
      {title:'Genuine chapter',owner:'File 06',url:'/library/document/safe-edition/',summary:'safe'},
      {title:'External',owner:'Unknown',url:'https://malicious.invalid/phishing',summary:'external'}
    ]};
    if(route==='insights?days=30')return {
      days:attack,reading_seconds:3600,documents:attack,distinct_pages:attack,
      completed_documents:attack,most_used:[{title:attack,seconds:60}]
    };
    if(route.startsWith('accessibility/'))return {
      score:attack,status:attack,verified:false,findings:[attack],public_badge_allowed:false
    };
    if(route.startsWith('handoff/'))return {};
    if(route==='insights'&&opts.method==='POST')return {};
    throw Error('Unexpected synthetic route '+route);
  }
};
const document={createElement:node,addEventListener(){}};
const window={PLDRF24:F,location:{origin:'https://sabrihomeopathy.com'},
  getSelection:()=>({toString:()=> 'remedy'}),addEventListener(){}};
const sandbox={window,document,navigator:{userAgent:'synthetic-test'},URL,
  console,prompt:()=> '',setInterval(){},setTimeout(){},Event:class{}};
vm.runInNewContext(source,sandbox,{filename:'future-reader-personal.js',timeout:2000});
async function fire(action){
  assert.equal(typeof handlers.click,'function');
  await handlers.click({target:{closest(){return {dataset:{f24Action:action}};}}});
}
(async()=>{
  for(const action of ['context','insights','a11y'])await fire(action);
  assert.ok(hrefWrites.some(x=>x.startsWith('/library/document/')||x.startsWith('https://sabrihomeopathy.com/')),
    'safe relative canonical context URL should still render as a link');
  assert.ok(hrefWrites.every(x=>x.startsWith('https://sabrihomeopathy.com/')),
    'untrusted provider URL leaked an unsafe/external protocol: '+JSON.stringify(hrefWrites));
  assert.ok(!htmlWrites.some(x=>x.includes(attack)),
    'unescaped provider/private metric values reached an HTML parser: '+htmlWrites.join(' | ').slice(0,350));
  assert.ok(safeText.some(x=>x.includes('evilR74()'))||htmlWrites.some(x=>x.includes('&lt;img')),
    'provider label and metrics must remain visible, safely encoded');
  console.log('R74 private reading metrics and companion URLs fail-closed: PASS');
})().catch(e=>{console.error(e.stack||e);process.exit(1);});
