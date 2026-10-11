const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {spawnSync} = require('node:child_process');

const root = path.resolve(__dirname, '../..');
const base = process.env.PETAL_QA_URL || 'http://127.0.0.1:8000';
if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base)) throw new Error('QA may target only a local development server.');
const output = path.join(root, 'storage/logs/screenshots');
fs.mkdirSync(output, {recursive:true});
const tag = crypto.randomBytes(8).toString('hex');
const emails = [`qa-${tag}-1@example.test`, `qa-${tag}-2@example.test`];
const password = crypto.randomBytes(12).toString('base64url');
const credentials = fs.readFileSync(path.join(root, 'storage/demo-credentials.txt'),'utf8');
const adminEmail = credentials.match(/^Email: (.+)$/m)[1].trim();
const adminPassword = credentials.match(/^Password: (.+)$/m)[1].trim();
let browser;
let checks = 0;
let checkoutLayoutsChecked = false;
const good = name => { checks++; console.log('PASS ' + name); };

async function registerAndLogin(page, email) {
  await page.goto(base+'/register.php');
  await page.locator('[name=name]').fill('QA Flower Customer');
  await page.locator('[name=email]').fill(email);
  await page.locator('[name=password]').fill(password);
  await page.locator('form[action$="/actions/register.php"] button[type=submit]').click();
  await page.waitForURL('**/login.php');
  await page.goto(base+'/login.php?return=/shop.php');
  await page.locator('[name=email]').fill(email);
  await page.locator('[name=password]').fill(password);
  await page.locator('form[action$="/actions/login.php"] button[type=submit]').click();
  await page.waitForURL('**/shop.php');
}

async function place(page, method) {
  await page.goto(base+'/product.php?id=1');
  await page.locator('form[action$="/actions/cart.php"] [name=quantity]').fill('1');
  await page.locator('form[action$="/actions/cart.php"] button[type=submit]').click();
  await page.goto(base+'/checkout.php');
  if(!checkoutLayoutsChecked) {
    for(const width of [375,768,1024,1440]) {
      await page.setViewportSize({width,height:1000});
      for(const route of ['/cart.php','/checkout.php']) {
        await page.goto(base+route);
        assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,route+' at '+width+'px');
      }
    }
  }
  await page.locator(`[name=fulfillment_method][value=${method}]`).check();
  const date = await page.locator('[name=scheduled_date]').getAttribute('min');
  await page.locator('[name=scheduled_date]').fill(date);
  if(method==='delivery') {
    await page.locator('[name=recipient_name]').fill('QA Recipient');
    await page.locator('[name=phone]').fill('09123456789');
    await page.locator('[name=address]').fill('Demo flower lane, school test address');
  }
  await page.locator('[name=gift_message]').fill('<script>window.qaInjected=true</script> Happy flowers!');
  await page.locator('form[action$="/actions/checkout.php"] button[type=submit]').click();
  await page.waitForURL('**/payment.php');
  if(!checkoutLayoutsChecked) {
    for(const width of [375,768,1024,1440]) {
      await page.setViewportSize({width,height:1000});
      assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'payment at '+width+'px');
      if(width===375) await page.screenshot({path:path.join(output,'payment-mobile.png'),fullPage:true});
    }
    checkoutLayoutsChecked=true;good('populated bag, checkout, and payment at four viewport widths');
  }
  assert.match(await page.locator('body').innerText(), /No money is charged/);
  assert.equal(await page.locator('input[type=password]').count(),0);
  const key=await page.locator('[name=checkout_key]').inputValue();
  const token=await page.locator('[name=_csrf]').first().inputValue();
  await page.locator('form[action$="/actions/checkout.php"] button[type=submit]').click();
  await page.waitForURL('**/confirmation.php?id=*');
  const id=new URL(page.url()).searchParams.get('id');
  const text=await page.locator('body').innerText();
  assert.match(text,/simulated/i);
  assert.match(text,/Happy flowers!/);
  assert.equal(await page.evaluate(()=>window.qaInjected),undefined);
  return {id,key,token};
}

async function run() {
  browser=await chromium.launch({headless:true});
  const desktop=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
  const page=await desktop.newPage();
  const pageErrors=[]; page.on('pageerror',error=>pageErrors.push(error.message));
  await page.goto(base+'/');
  await page.screenshot({path:path.join(output,'home-desktop.png'),fullPage:true});
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  assert.equal(await page.locator('img').evaluateAll(images=>images.some(img=>!img.complete||img.naturalWidth===0)),false);
  good('desktop homepage renders without overflow or broken images');
  await page.goto(base+'/shop.php');
  assert.equal(await page.locator('.product-card').count(),12);
  good('all twelve seeded arrangements render');
  await page.goto(base+'/cart.php'); assert.match(page.url(),/login.php/);
  await page.goto(base+'/admin/index.php'); assert.match(page.url(),/login.php/);
  const wrongMethod=await desktop.request.get(base+'/actions/cart.php'); assert.equal(wrongMethod.status(),405);
  const noCsrf=await desktop.request.post(base+'/actions/cart.php',{form:{action:'add',product_id:'1',quantity:'1'}}); assert.equal(noCsrf.status(),403);
  const privateRoute=await desktop.request.get(base+'/config/local.php'); assert.equal(privateRoute.status(),404);
  good('guest access, HTTP methods, CSRF, and private file boundaries');
  await registerAndLogin(page,emails[0]);
  const denied=await desktop.request.get(base+'/admin/index.php'); assert.equal(denied.status(),403);
  const deniedAction=await desktop.request.post(base+'/admin/actions/product-save.php',{form:{name:'Tampered'}}); assert.equal(deniedAction.status(),403);
  good('customer registration/login and admin role restriction');
  const delivery=await place(page,'delivery');
  good('delivery checkout, simulated payment, and escaped gift message');
  const repeat=await desktop.request.post(base+'/actions/checkout.php',{form:{action:'pay',checkout_key:delivery.key,_csrf:delivery.token},maxRedirects:0});
  assert.equal(repeat.status(),303); assert.match(repeat.headers()['location'],new RegExp('id='+delivery.id+'$'));
  good('repeated payment submission returns original order');
  await page.goto(base+'/orders.php'); assert.match(await page.locator('body').innerText(),/PTM-/);
  good('customer order history');

  const mobile=await browser.newContext({viewport:{width:375,height:812},reducedMotion:'reduce'});
  const mobilePage=await mobile.newPage();
  await mobilePage.goto(base+'/');
  await mobilePage.screenshot({path:path.join(output,'home-mobile.png'),fullPage:true});
  assert.equal(await mobilePage.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  const menu=mobilePage.locator('.menu-toggle');
  await menu.click(); assert.equal(await menu.getAttribute('aria-expanded'),'true');
  good('375px homepage and mobile navigation');

  const noJs=await browser.newContext({javaScriptEnabled:false,viewport:{width:768,height:1024},reducedMotion:'reduce'});
  const second=await noJs.newPage();
  await registerAndLogin(second,emails[1]);
  const ownership=await noJs.request.get(base+'/order.php?id='+delivery.id); assert.equal(ownership.status(),404);
  const confirmationOwnership=await noJs.request.get(base+'/confirmation.php?id='+delivery.id); assert.equal(confirmationOwnership.status(),404);
  const pickup=await place(second,'pickup');
  await second.goto(base+'/order.php?id='+pickup.id);
  await second.screenshot({path:path.join(output,'pickup-tablet.png'),fullPage:true});
  assert.equal(await second.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  good('768px no-JavaScript pickup checkout and cross-customer ownership');

  const adminContext=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
  const admin=await adminContext.newPage();
  await admin.goto(base+'/login.php');
  await admin.locator('[name=email]').fill(adminEmail);
  await admin.locator('[name=password]').fill(adminPassword);
  await admin.locator('form[action$="/actions/login.php"] button[type=submit]').click();
  await admin.waitForURL('**/admin/index.php');
  await admin.screenshot({path:path.join(output,'admin-desktop.png'),fullPage:true});
  good('administrator login and dashboard');
  async function change(id,status) {
    await admin.goto(base+'/admin/order.php?id='+id);
    const csrf=await admin.locator('[name=_csrf]').first().inputValue();
    return adminContext.request.post(base+'/admin/actions/order-status.php',{form:{_csrf:csrf,order_id:id,id,next_status:status,status},maxRedirects:0});
  }
  const cancel=await change(delivery.id,'cancelled'); assert.equal(cancel.status(),303);
  await page.goto(base+'/order.php?id='+delivery.id); assert.match(await page.locator('body').innerText(),/refunded/i);
  good('admin cancellation and simulated refund in customer receipt');
  const completedDelivery=await place(page,'delivery');
  for(const status of ['preparing','out_for_delivery','completed']) { const result=await change(completedDelivery.id,status); assert.equal(result.status(),303); }
  await page.goto(base+'/order.php?id='+completedDelivery.id); assert.match(await page.locator('body').innerText(),/Completed/);
  good('delivery prepare, dispatch, and completion');
  for(const status of ['preparing','ready_for_pickup','completed']) { const result=await change(pickup.id,status); assert.equal(result.status(),303); }
  await second.goto(base+'/order.php?id='+pickup.id); assert.match(await second.locator('body').innerText(),/Completed/);
  const blocked=await change(pickup.id,'cancelled'); assert.equal(blocked.status(),303);
  await second.reload(); assert.match(await second.locator('body').innerText(),/Completed/);
  good('pickup fulfillment and completed-order cancellation cutoff');
  await admin.setViewportSize({width:375,height:812});
  await admin.goto(base+'/admin/products.php');
  await admin.screenshot({path:path.join(output,'admin-mobile.png'),fullPage:true});
  assert.equal(await admin.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  good('mobile admin products layout');
  let responsivePages=0;
  async function inspectPages(target,routes) {
    for(const width of [375,768,1024,1440]) {
      await target.setViewportSize({width,height:1000});
      for(const route of routes) {
        const response=await target.goto(base+route);
        assert.equal(response.status(),200,route+' at '+width+'px');
        if(await target.evaluate(()=>document.documentElement.scrollWidth>innerWidth)) {
          await target.screenshot({path:path.join(output,'overflow.png'),fullPage:true});
          console.log('Overflow elements: '+JSON.stringify(await target.locator('body *').evaluateAll(elements=>elements.filter(element=>element.getBoundingClientRect().right>innerWidth).map(element=>({tag:element.tagName,class:element.className,width:element.getBoundingClientRect().width})).slice(-12))));
        }
        assert.equal(await target.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,route+' overflow at '+width+'px');
        const decoded=await target.locator('img').evaluateAll(async images=>Promise.all(images.map(img=>{img.loading='eager';return img.decode().then(()=>true,()=>false);})));
        assert.equal(decoded.every(Boolean),true,route+' broken image');
        if(width===375 && route.startsWith('/admin/order.php?')) await target.screenshot({path:path.join(output,'admin-order-mobile.png'),fullPage:true});
        responsivePages++;
      }
    }
  }
  await inspectPages(mobilePage,['/','/shop.php','/product.php?id=1','/login.php','/register.php']);
  await inspectPages(page,['/cart.php','/orders.php','/order.php?id='+completedDelivery.id,'/confirmation.php?id='+completedDelivery.id]);
  await inspectPages(admin,['/admin/index.php','/admin/products.php','/admin/product-edit.php?id=1','/admin/category-edit.php','/admin/categories.php','/admin/orders.php','/admin/order.php?id='+completedDelivery.id]);
  good(responsivePages+' page/viewport combinations render without overflow or broken images');
  await mobilePage.goto(base+'/shop.php?q=Tulip'); assert.equal(await mobilePage.locator('.product-card').count(),1);
  await mobilePage.goto(base+'/shop.php?category=1'); assert.equal(await mobilePage.locator('.product-card').count(),4);
  for(const sort of ['featured','newest','price_asc','price_desc','name']) {
    await mobilePage.goto(base+'/shop.php?sort='+sort);
    assert.equal(await mobilePage.locator('.product-card').count(),12);
    if(sort.startsWith('price_')) {
      const prices=await mobilePage.locator('.product-title-row>span').allTextContents();
      const amounts=prices.map(value=>Number(value.replace(/[^0-9.]/g,'')));
      assert.deepEqual(amounts,[...amounts].sort((a,b)=>sort==='price_asc'?a-b:b-a));
    }
  }
  good('catalog search, category filter, and sorting');
  await mobilePage.setViewportSize({width:375,height:812});
  await mobilePage.goto(base+'/'); await mobilePage.keyboard.press('Tab');
  assert.equal(await mobilePage.locator('.skip-link').evaluate(element=>element===document.activeElement),true);
  await mobilePage.locator('.menu-toggle').click(); await mobilePage.keyboard.press('Escape');
  assert.equal(await mobilePage.locator('.menu-toggle').getAttribute('aria-expanded'),'false');
  good('keyboard skip link and Escape navigation');
  await page.goto(base+'/orders.php');
  const logoutToken=await page.locator('form[action$="/logout.php"] [name=_csrf]').inputValue();
  const invalidLogout=await desktop.request.post(base+'/logout.php',{form:{_csrf:'invalid'}}); assert.equal(invalidLogout.status(),403);
  const logout=await desktop.request.post(base+'/logout.php',{form:{_csrf:logoutToken},maxRedirects:0}); assert.equal(logout.status(),303);
  const afterLogout=await desktop.request.get(base+'/orders.php',{maxRedirects:0}); assert.equal(afterLogout.status(),303);assert.match(afterLogout.headers()['location'],/login.php/);
  good('CSRF-protected logout clears account access');
  assert.deepEqual(pageErrors,[]); good('no customer JavaScript errors');
  console.log(checks+' browser workflow checks passed.');
}

run().catch(error=>{console.error(error.stack);process.exitCode=1;}).finally(async()=>{
  if(browser) await browser.close();
  const cleanup=spawnSync('C:/xampp/php/php.exe',['tests/e2e/cleanup.php'],{cwd:root,input:JSON.stringify(emails),encoding:'utf8',windowsHide:true});
  if(cleanup.status!==0) { console.error(cleanup.stderr);process.exitCode=1; } else console.log(cleanup.stdout.trim());
});
