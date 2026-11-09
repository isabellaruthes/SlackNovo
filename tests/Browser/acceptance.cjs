// External test tooling: PLAYWRIGHT_MODULE=/path/to/playwright node tests/Browser/acceptance.cjs
// Uses only a newly created temporary SQLite database; does not touch the local application's DB.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const {spawn, execFileSync} = require('node:child_process');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

(async () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'slacknovo-acceptance-'));
  const database = path.join(temp, 'acceptance.sqlite');
  fs.writeFileSync(database, '');
  const password = crypto.randomBytes(24).toString('hex');
  const port = Number(process.env.SLK_TEST_PORT || 8010);
  const base = `http://127.0.0.1:${port}`;
  const env = {...process.env, APP_ENV:'local', APP_DEBUG:'false', APP_URL:base, DB_CONNECTION:'sqlite', DB_DATABASE:database, DB_URL:'', CACHE_STORE:'array', SESSION_DRIVER:'database', QUEUE_CONNECTION:'sync', SLK_TEST_PASSWORD:password};
  console.log(execFileSync('php', ['tests/Browser/fixture.php'], {env, encoding:'utf8'}).trim());
  const serverLog = fs.openSync(path.join(temp, 'server.log'), 'w');
  const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', 'public', 'tests/Browser/router.php'], {env, stdio:['ignore',serverLog,serverLog]});
  let browser;
  const results = [];
  try {
    for (let i=0; i<50; i++) {
      if (server.exitCode !== null) throw new Error('Acceptance server exited; inspect '+temp);
      try { if ((await fetch(base+'/login')).ok) break; } catch {}
      await new Promise(resolve=>setTimeout(resolve,100));
    }
    browser = await chromium.launch({headless:true});
    const page = await browser.newPage({viewport:{width:1440,height:1000}});
    const errors = [];
    page.on('pageerror', error=>errors.push(error.message));
    await page.goto(base, {waitUntil:'networkidle'});
    assert.equal(await page.locator('.card').count(),24);
    assert.equal(await page.locator('img[src$="/storage/"]').count(),0);
    await page.getByRole('button',{name:'Ver detalhes'}).first().click();
    await page.locator('.modal.show').waitFor();
    await page.locator('.modal.show').getByRole('button',{name:'Fechar'}).click();
    results.push('Desktop: 24 cards, imagem ausente e modal de detalhes');
    console.log(results.at(-1));
    await page.goto(base+'/dashboard');
    assert.match(page.url(), /\/login$/);
    await page.getByLabel('Usuário').fill('aceite-browser');
    await page.getByLabel('Senha').fill(password);
    await Promise.all([page.waitForURL('**/dashboard'),page.getByRole('button',{name:'Entrar no sistema'}).click()]);
    assert.equal(await page.locator('#adminNavigation').getByRole('link',{name:'Caixa',exact:true}).isVisible(),true);
    assert.equal(await page.locator('#adminNavigation').getByRole('link',{name:'Produtos',exact:true}).isVisible(),true);
    await page.goto(base+'/admin/produtos/1/edit');
    assert.equal(await page.locator('[name="genero"]').inputValue(),'feminino');
    await Promise.all([page.waitForURL('**/admin/produtos'),page.getByRole('button',{name:'Atualizar',exact:true}).click()]);
    assert.match(await page.locator('[role="status"]').innerText(), /atualizado/);
    await page.goto(base+'/admin/produtos/1/edit');
    assert.equal(await page.locator('[name="genero"]').inputValue(),'feminino');
    results.push('Login, persistência de gênero e feedback após editar');
    console.log(results.at(-1));
    await page.goto(base+'/admin/caixa');
    await page.getByLabel('Nome da venda',{exact:true}).fill('Venda browser');
    await page.getByLabel('ID do produto',{exact:true}).fill('1');
    await page.getByLabel('Valor da venda (R$)',{exact:true}).fill('30.00');
    await page.getByLabel('Comprador',{exact:true}).fill('Cliente browser');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Salvar venda',exact:true}).click()]);
    assert.match(await page.locator('[role="status"]').innerText(), /Venda registrada/);
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Reembolso',exact:true}).click()]);
    assert.match(await page.locator('[role="status"]').innerText(), /reembolsada/);
    await page.getByLabel('Valor da saída (R$)',{exact:true}).fill('5.00');
    await page.getByLabel('Motivo',{exact:true}).fill('Embalagem browser');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Salvar saída',exact:true}).click()]);
    assert.match(await page.locator('[role="status"]').innerText(), /Saída registrada/);
    results.push('Venda pelo Caixa, reembolso e saída reais com CSRF');
    console.log(results.at(-1));
    await page.screenshot({path:path.join(temp,'desktop-caixa.png'),fullPage:true});
    await page.setViewportSize({width:390,height:844});
    await page.goto(base+'/admin/produtos');
    const toggle = page.getByRole('button',{name:'Abrir menu administrativo'});
    assert.equal(await toggle.getAttribute('aria-expanded'),'false');
    assert.equal(await page.locator('#adminNavigation').isVisible(),false);
    await toggle.click();
    await page.locator('#adminNavigation.show').waitFor();
    assert.equal(await toggle.getAttribute('aria-expanded'),'true');
    assert.equal(await page.locator('#adminNavigation').getByRole('link',{name:'Caixa',exact:true}).isVisible(),true);
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth + 1),true);
    await page.screenshot({path:path.join(temp,'mobile-produtos.png'),fullPage:true});
    await toggle.click();
    await page.locator('#adminNavigation.show').waitFor({state:'hidden'});
    await page.locator('#adminNavigation').waitFor({state:'hidden'});
    assert.equal(await toggle.getAttribute('aria-expanded'),'false');
    assert.equal(await page.locator('#adminNavigation').isVisible(),false);
    await toggle.click();
    await page.locator('#adminNavigation.show').waitFor();
    await Promise.all([page.waitForURL(base+'/'),page.getByRole('button',{name:'Sair',exact:true}).click()]);
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth + 1),true);
    await page.screenshot({path:path.join(temp,'mobile-catalogo.png'),fullPage:true});
    await page.goto(base+'/dashboard');
    assert.match(page.url(),/\/login$/);
    results.push('Mobile 390px: menu abre/fecha, sem overflow horizontal e logout revoga acesso');
    assert.deepEqual(errors,[]);
    console.log(JSON.stringify({results, pageErrors:errors, artifacts:temp},null,2));
  } finally {
    if (browser) await browser.close();
    server.kill('SIGTERM');
    fs.closeSync(serverLog);
  }
})().catch(error=>{console.error(error);process.exitCode=1});
