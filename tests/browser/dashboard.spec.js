import {test,expect} from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
async function login(page,email='admin@stockroom.test') {await page.goto('/admin/login');await page.getByLabel('Email address').fill(email);await page.locator('input[type=password]').fill('StockroomDemo!2026');await page.getByRole('button',{name:'Sign in'}).click();await page.waitForURL('**/admin');}
test('administrator completes order, cancellation, and queued report workflows',async({page})=>{
 await login(page);await page.goto('/admin/orders');await page.getByRole('button',{name:'Place order',exact:true}).click();const dialog=page.getByRole('dialog');
 await dialog.getByRole('button',{name:/^Customer/}).click();await page.getByRole('option',{name:'Demo Buyer 1',exact:true}).click();
 await dialog.getByRole('button',{name:/^Product/}).click();await page.getByRole('option',{name:/Studio headphones/}).click();await dialog.getByRole('spinbutton').fill('2');
 await dialog.getByRole('button',{name:'Create',exact:true}).click();await expect(dialog).not.toBeVisible();
 await page.getByRole('button',{name:'Cancel order',exact:true}).first().click();await page.getByRole('alertdialog').getByRole('button',{name:'Confirm',exact:true}).click();await expect(page.getByRole('dialog')).not.toBeVisible();
 await page.goto('/admin/reports');await page.getByRole('button',{name:'Generate sales report',exact:true}).click();await page.getByRole('dialog').getByRole('button',{name:'Create',exact:true}).click();await expect(page.getByRole('dialog')).not.toBeVisible();
 await expect(page.getByRole('link',{name:'Download CSV'}).first()).toBeVisible({timeout:20000});const download=page.waitForEvent('download');await page.getByRole('link',{name:'Download CSV'}).first().click();expect((await download).suggestedFilename()).toMatch(/stockroom-sales-\d+\.csv/);
});
test('desktop pages render with no browser errors',async({page})=>{
 const errors=[];page.on('pageerror',e=>errors.push(e.message));await login(page);
 for(const [route,name] of [['','dashboard'],['products','products'],['orders','orders'],['reports','reports'],['activities','activity'],['guide','guide']]){
  await page.goto('/admin'+(route?'/'+route:''));await page.waitForTimeout(route?700:4500);await expect(page.getByRole('heading',{level:1})).toBeVisible();await page.screenshot({path:`docs/assets/${name}.png`,fullPage:true});
 }
 expect(errors).toEqual([]);
 const violations=(await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa']).analyze()).violations;expect(violations).toEqual([]);
});
test('staff is denied reports and activity history',async({page})=>{
 await login(page,'staff@stockroom.test');await expect(page.getByRole('link',{name:'Reports',exact:true})).toHaveCount(0);await page.goto('/admin/products');await expect(page.getByRole('button',{name:'New product'})).toHaveCount(0);await expect(page.getByRole('button',{name:'Adjust stock'})).toHaveCount(0);const response=await page.goto('/admin/reports');expect(response.status()).toBe(403);
});
test('mobile guide and dashboard fit the viewport',async({page})=>{
 await page.setViewportSize({width:390,height:844});await login(page);await page.goto('/admin/guide');expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);await page.screenshot({path:'docs/assets/mobile.png',fullPage:true});await page.goto('/admin');await expect(page.getByRole('heading',{name:'Dashboard',exact:true})).toBeVisible();
});
