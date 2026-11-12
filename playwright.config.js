import { defineConfig } from '@playwright/test';
export default defineConfig({testDir:'./tests/browser',timeout:60000,workers:1,use:{baseURL:'http://127.0.0.1:8000',viewport:{width:1440,height:1000},trace:'retain-on-failure'},webServer:{command:'php artisan serve --host=127.0.0.1 --port=8000',url:'http://127.0.0.1:8000/admin/login',reuseExistingServer:true}});
