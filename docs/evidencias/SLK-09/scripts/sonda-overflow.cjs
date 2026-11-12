// node probe.cjs <repo> <url-path>: lists elements overflowing a 390px viewport.
const fs=require('fs'),os=require('os'),path=require('path'),crypto=require('crypto'),{spawn,execFileSync}=require('child_process');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const [repo,url]=process.argv.slice(2); const X=['-d','extension=pdo_sqlite','-d','extension=sqlite3','-d','extension=fileinfo','-d','extension=gd'];
(async()=>{const t=fs.mkdtempSync(path.join(os.tmpdir(),'slk-audit-'));const db=path.join(t,'db.sqlite');fs.writeFileSync(db,'');const pw=crypto.randomBytes(16).toString('hex');const port=8099;const base='http://127.0.0.1:'+port;
const env={...process.env,SLK_REPO:repo,APP_ENV:'local',APP_DEBUG:'false',APP_KEY:'base64:'+crypto.randomBytes(32).toString('base64'),DB_CONNECTION:'sqlite',DB_DATABASE:db,DB_URL:'',CACHE_STORE:'array',SESSION_DRIVER:'database',SLK_TEST_PASSWORD:pw};
execFileSync('php',[...X,path.join(__dirname,'fixture.php'),repo],{env});const srv=spawn('php',[...X,'-S','127.0.0.1:'+port,'-t','public','tests/Browser/router.php'],{cwd:repo,env,stdio:'ignore'});
try{for(let i=0;i<80;i++){try{if((await fetch(base+'/login')).ok)break}catch{}await new Promise(r=>setTimeout(r,150))}
const b=await chromium.launch();const p=await b.newPage({viewport:{width:390,height:844}});await p.goto(base+'/login');await p.fill('#nome','operador-teste');await p.fill('#password',pw);await Promise.all([p.waitForURL('**/dashboard'),p.click('button[type=submit]')]);
await p.goto(base+'/'+url,{waitUntil:'networkidle'});
console.log(JSON.stringify(await p.evaluate(()=>{const w=document.documentElement.clientWidth;return [...document.querySelectorAll('body *')].filter(e=>e.getBoundingClientRect().right>w+1).slice(0,12).map(e=>({tag:e.tagName,cls:e.className.toString().slice(0,60),txt:(e.textContent||'').trim().slice(0,30),right:Math.round(e.getBoundingClientRect().right),w:Math.round(e.getBoundingClientRect().width)}))}),null,1));await b.close()}finally{srv.kill()}})();
