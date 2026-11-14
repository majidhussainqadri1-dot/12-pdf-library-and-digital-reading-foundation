/**
 * V12.3 QA-15 limited real-browser evidence on a disposable empty WordPress
 * Library. NOT a WCAG audit, not a real book/reader journey and not Hostinger.
 */
import { chromium } from 'playwright';
import fs from 'node:fs/promises';
const base=process.env.FILE12_BROWSER_BASE;
if (process.env.CI!=='true' ||
    process.env.FILE12_CI_EPHEMERAL_DB!=='yes' ||
    base!=='http://127.0.0.1:8080') {
  throw new Error('Browser tests refuse non-disposable CI target');
}
const cases=[320,390,768,1024,1440];
const results=[];
await fs.mkdir('browser-evidence',{recursive:true});
const browser=await chromium.launch({headless:true});
try {
  for(const width of cases){
    const ctx=await browser.newContext({
      viewport:{width,height:900},deviceScaleFactor:1,
      reducedMotion:'reduce',colorScheme:'light'
    });
    const page=await ctx.newPage();
    const errors=[];
    page.on('pageerror',e=>errors.push(e.message));
    const response=await page.goto(base+'/library/',{waitUntil:'networkidle',timeout:60000});
    const status=response?.status()||0;
    const title=await page.locator('.pldr-shell h1').first().textContent().catch(()=>null);
    const layout=await page.evaluate(()=>{
      const root=document.querySelector('.pldr-shell');
      const input=document.querySelector('#pldr-q');
      const filters=document.querySelector('.pldr-filters');
      const bounds=root?.getBoundingClientRect();
      return {
        root:!!root,
        search:!!input && input.getBoundingClientRect().width>0,
        filters:!!filters,
        mainWidth:bounds?.width||0,
        bodyWidth:document.body.scrollWidth,
        viewportWidth:window.innerWidth,
        documentWidth:document.documentElement.scrollWidth,
        primaryBackground:getComputedStyle(document.querySelector('.pldr-hero')||document.body).backgroundImage
      };
    });
    await page.screenshot({path:'browser-evidence/library-'+width+'px.png',fullPage:true});
    const result={width,status,title,layout,pageerrors:errors};
    results.push(result);
    if(status!==200)throw new Error('Library HTTP '+status+' @'+width);
    if(!layout.root||!layout.search||!layout.filters||!/PDF Library/i.test(title||'')) {
      throw new Error('Library semantic/search/filter foundation absent @'+width);
    }
    if(Math.max(layout.bodyWidth,layout.documentWidth)>width+2){
      throw new Error('Unexpected horizontal overflow @'+width+': '+JSON.stringify(layout));
    }
    if(errors.length)throw new Error('Unhandled browser JS exception @'+width+': '+errors.join(' | '));
    // Check that principal search input is keyboard focusable and visible.
    await page.locator('#pldr-q').focus();
    if(!await page.locator('#pldr-q').evaluate(el=>el===document.activeElement)){
      throw new Error('Search box cannot hold keyboard focus @'+width);
    }
    // Simulate RTL presentation only; do not claim an Urdu/Arabic translation.
    await page.locator('.pldr-shell').evaluate(el=>el.setAttribute('dir','rtl'));
    const rtl=await page.evaluate(()=>({
       direction:getComputedStyle(document.querySelector('.pldr-shell')).direction,
       bodyWidth:document.body.scrollWidth,
       documentWidth:document.documentElement.scrollWidth,
       viewportWidth:window.innerWidth
    }));
    await page.screenshot({path:'browser-evidence/library-rtl-'+width+'px.png',fullPage:true});
    if(rtl.direction!=='rtl' || Math.max(rtl.bodyWidth,rtl.documentWidth)>width+2)
       throw new Error('Synthetic RTL layout overflow @'+width+': '+JSON.stringify(rtl));
    result.rtl=rtl;
    await ctx.close();
  }
  const case320=results.find(x=>x.width===320);
  if(!case320?.layout.search)throw new Error('320px minimum width not accepted');
  await fs.writeFile('browser-evidence/qa15-disposable-results.json',
    JSON.stringify({scope:'disposable WordPress empty catalog only, NO staging/live claim',
      source:'actual Chromium browser',results},null,2));
  console.log('FILE12 CHROMIUM QA15 DISPOSABLE: '+cases.length+' viewport+RTL layouts PASS; no licensed book/reader test implied.');
} finally{
  await browser.close();
}
