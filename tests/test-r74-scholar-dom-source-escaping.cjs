'use strict';
/**
 * File12 R74 RED-first: incoming provider fields are untrusted, even when the
 * current PHP adapter intends integers. Actual scholar asset must never
 * interpolate them into an HTML parser sink; render as text instead.
 * Isolated DOM recorders; no network, plugins, or production writes.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const file=path.join(__dirname,'..','pdf-library-foundation-12','assets','future-reader-scholar.js');
const source=fs.readFileSync(file,'utf8');
const attack='<img src=x onerror=evilR74()>';
const htmlWrites=[],safeTextWrites=[],events={};
function node(tag){
  const n={
    tagName:tag,dataset:{},children:[],style:{setProperty(){}},
    addEventListener(){},
    append(...children){this.children.push(...children);},
    appendChild(child){this.children.push(child);return child;},
    replaceChildren(...children){this.children=children;},
    setAttribute(){},
    set innerHTML(value){htmlWrites.push(String(value));this._html=String(value);},
    get innerHTML(){return this._html||'';},
    set textContent(value){safeTextWrites.push(String(value));this._text=String(value);},
    get textContent(){return this._text||'';}
  };
  return n;
}
const workspace=node('section');
const F={
  root:{addEventListener(type,callback){events[type]=callback;}},
  workspace,editionId:55,
  cfg:{publicId:'synthetic'},
  page:()=>1,openPanel(){},say(){},
  escape:value=>String(value??'').replace(/[&<>"']/g,ch=>({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[ch])),
  setWorkspace(value){
    if(typeof value==='string')workspace.innerHTML=value;
    else workspace.replaceChildren(value);
  },
  api:async route=>{
    if(route.startsWith('compare?'))return {
      same_pages:attack,changed:[{page:attack,similarity:attack,
        left_excerpt:attack,right_excerpt:attack}],pages_without_comparable_ocr:attack
    };
    if(route.startsWith('ocr-quality/'))return {
      average_quality:attack,minimum_quality:attack,maximum_quality:attack,
      pages:attack,corrections:[],heatmap:[{page_number:attack,quality_score:attack}]
    };
    if(route.startsWith('search-heatmap/'))return {
      matches:attack,pages:[{page:attack,matches:attack}]
    };
    throw new Error('Unexpected fixture endpoint '+route);
  }
};
const document={
  createElement:node,
  createTextNode:content=>{const n=node('#text');n.textContent=content;return n;}
};
const ctx={window:{PLDRF24:F},document,prompt:()=> '2',console,Event:class{}};
vm.runInNewContext(source,ctx,{timeout:2000,filename:'future-reader-scholar.js'});
async function fire(action){
  assert.equal(typeof events.click,'function','scholar action handler must load');
  await events.click({target:{closest(){return {dataset:{f24Action:action}};}}});
}
(async()=>{
  for(const action of ['compare','ocr-lab','heatmap'])await fire(action);
  const unsafe=htmlWrites.filter(value=>value.includes('evilR74()'));
  assert.deepEqual(unsafe,[],'API data reached innerHTML parser: '+unsafe.join(' | ').slice(0,500));
  assert.ok(safeTextWrites.some(value=>value.includes('evilR74()')),
    'Untrusted returned source text must still be visible safely through textContent');
  console.log('R74 provider-sourced scholar fields rendered as safe text, not HTML: PASS.');
})().catch(error=>{console.error(error.stack||error);process.exit(1);});
