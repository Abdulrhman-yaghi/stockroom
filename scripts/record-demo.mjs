import { chromium, expect } from '@playwright/test';
import fs from 'node:fs/promises';
const base=process.env.DEMO_URL || 'http://127.0.0.1:8000';
const browser=await chromium.launch();
await fs.mkdir('.recordings',{recursive:true});
const auth=await browser.newContext();const login=await auth.newPage();
await login.goto(base+'/admin/login');await login.getByLabel('Email address').fill('admin@stockroom.test');await login.locator('input[type=password]').fill('StockroomDemo!2026');await login.getByRole('button',{name:'Sign in'}).click();await login.waitForURL('**/admin');const storageState=await auth.storageState();await auth.close();
const clips=[];
async function scene(name,title,caption,route,action){
 const context=await browser.newContext({storageState,viewport:{width:1440,height:900},recordVideo:{dir:'.recordings',size:{width:1440,height:900}}});
 const page=await context.newPage();const started=Date.now();await page.goto(base+route);await page.waitForTimeout(route==='/admin'?4500:1000);const start=(Date.now()-started)/1000;
 await page.mouse.move(1400,850);await page.waitForTimeout(1400);if(action)await action(page);await page.waitForTimeout(2800);const duration=(Date.now()-started)/1000-start;
 const video=page.video();await context.close();const file=await video.path();clips.push({name,title,caption,file,start,duration});console.log('Recorded '+name);
}
await scene('overview','01 / THE BIG PICTURE','Confirmed sales, recent orders and low stock in one place.','/admin');
await scene('inventory','02 / INVENTORY WITH A REASON','Every stock adjustment records the actor and the reason.','/admin/products',async p=>{
 await p.getByRole('searchbox').fill('USB C hub');await p.getByRole('searchbox').press('Enter');await p.waitForTimeout(1200);await p.getByRole('row').filter({hasText:'USB C hub'}).getByRole('button',{name:'Adjust stock',exact:true}).click();const d=p.getByRole('dialog');await d.getByLabel('Quantity change').fill('5');await d.getByLabel('Reason').fill('Demo delivery received');await p.waitForTimeout(1800);await d.getByRole('button',{name:'Submit',exact:true}).click();await expect(d).not.toBeVisible();
});
await scene('order','03 / PLACE AN ORDER','The server checks stock and uses stored product prices.','/admin/orders',async p=>{
 await p.getByRole('button',{name:'Place order',exact:true}).click();const d=p.getByRole('dialog');await d.getByRole('button',{name:/^Customer/}).click();await p.getByRole('option',{name:'Demo Buyer 1',exact:true}).click();await d.getByRole('button',{name:/^Product/}).click();await p.getByRole('option',{name:/Studio headphones/}).click();await d.getByRole('spinbutton').fill('2');await p.waitForTimeout(1800);await p.screenshot({path:'docs/assets/order-form.png'});await d.getByRole('button',{name:'Create',exact:true}).click();await expect(d).not.toBeVisible();
});
await scene('cancel','04 / CANCEL WITHOUT DOUBLE RESTOCKING','Cancellation returns inventory exactly once.','/admin/orders',async p=>{
 await p.getByRole('button',{name:'Cancel order',exact:true}).first().click();await p.waitForTimeout(1800);await p.getByRole('alertdialog').getByRole('button',{name:'Confirm',exact:true}).click();await expect(p.getByRole('alertdialog')).not.toBeVisible();
});
await scene('reports','05 / REPORTS THAT RUN IN THE BACKGROUND','Choose dates. Queue the export. Download a private CSV.','/admin/reports',async p=>{
 await p.getByRole('button',{name:'Generate sales report',exact:true}).click();await p.waitForTimeout(1800);await p.getByRole('dialog').getByRole('button',{name:'Create',exact:true}).click();await expect(p.getByRole('dialog')).not.toBeVisible();await p.waitForTimeout(5000);await expect(p.getByRole('link',{name:'Download CSV'}).first()).toBeVisible();const download=p.waitForEvent('download');await p.getByRole('link',{name:'Download CSV'}).first().click();await download;
});
await scene('activity','06 / FOLLOW THE CHANGE','Trace order, inventory and report events back to their actor.','/admin/activities');
await scene('guide','07 / A CLEAR STARTING POINT','A built in guide makes the workflow easy to explore.','/admin/guide',async p=>{await p.evaluate(()=>window.scrollTo({top:380,behavior:'smooth'}));await p.waitForTimeout(1800);});
await fs.writeFile('.recordings/clips.json',JSON.stringify(clips,null,2));await browser.close();console.log('Recording complete');
