// SLK-07 browser evidence.
// Usage: PLAYWRIGHT_MODULE=/path/to/node_modules/playwright node slk07-browser.cjs <repo> <label: antes|depois> <outDir>
// Requires `npm run build` in <repo> and PHP with pdo_sqlite. Playwright is external tooling (not a project dependency).
// Starts the app with the built-in PHP server on a fresh temporary SQLite DB and records
// observations + screenshots for BUG-07, BUG-09 and BUG-14. Observations are recorded, not asserted,
// so the same script documents the defect on main ("antes") and the fix ("depois").
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const {spawn, execFileSync} = require('node:child_process');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const [repo, label, outDir] = process.argv.slice(2);
const PHP_EXT = ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', '-d', 'extension=fileinfo', '-d', 'extension=gd'];

(async () => {
  fs.mkdirSync(outDir, {recursive: true});
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'slk07-browser-'));
  const database = path.join(temp, 'db.sqlite');
  fs.writeFileSync(database, '');
  const password = crypto.randomBytes(24).toString('hex');
  const port = label === 'antes' ? 8031 : 8032;
  const base = `http://127.0.0.1:${port}`;
  const env = {...process.env, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: base, APP_KEY: 'base64:' + crypto.randomBytes(32).toString('base64'),
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', CACHE_STORE: 'array', SESSION_DRIVER: 'database', QUEUE_CONNECTION: 'sync', SLK_TEST_PASSWORD: password};
  const ids = JSON.parse(execFileSync('php', [...PHP_EXT, path.join(__dirname, 'fixture-slk07.php'), repo], {env, encoding: 'utf8'}).trim().split('\n').pop());
  const server = spawn('php', [...PHP_EXT, '-S', `127.0.0.1:${port}`, '-t', 'public', 'tests/Browser/router.php'], {cwd: repo, env, stdio: 'ignore'});
  const results = {label, commit: execFileSync('git', ['rev-parse', '--short', 'HEAD'], {cwd: repo, encoding: 'utf8'}).trim(), ids, observacoes: {}};
  const shot = (page, name) => page.screenshot({path: path.join(outDir, `${name}.png`), fullPage: true});
  const alerta = async page => ((await page.locator('.alert').allInnerTexts()).join(' | ') || '(nenhum alerta)').replace(/\s+/g, ' ').trim();
  let browser;

  try {
    for (let i = 0; i < 80; i++) {
      try { if ((await fetch(base + '/login')).ok) break; } catch {}
      await new Promise(r => setTimeout(r, 150));
    }
    browser = await chromium.launch({headless: true});
    const login = async context => {
      const page = await context.newPage();
      await page.goto(base + '/login');
      await page.fill('#nome', 'operador-teste');
      await page.fill('#password', password);
      await Promise.all([page.waitForURL('**/dashboard'), page.click('button[type=submit]')]);
      return page;
    };

    // ---------- BUG-14: excluir produto com venda ativa ----------
    const ctx = await browser.newContext({viewport: {width: 1366, height: 900}});
    let page = await login(ctx);
    await page.goto(base + '/admin/produtos');
    const linhaVendido = page.locator('tr', {hasText: 'Camisa vendida (BUG-14)'});
    await linhaVendido.getByRole('button', {name: 'Excluir'}).click();
    await page.locator('#confirmDeleteModal.show').waitFor();
    await Promise.all([page.waitForLoadState('load'), page.click('#confirmDeleteButton')]);
    await page.waitForLoadState('networkidle');
    results.observacoes.bug14_exclusao_com_venda_ativa = {
      alerta: await alerta(page),
      produto_continua_listado: await page.locator('tr', {hasText: 'Camisa vendida (BUG-14)'}).count() > 0,
    };
    await shot(page, 'bug14-1-exclusao-com-venda-ativa');

    await page.goto(base + '/admin/caixa');
    const reembolso = page.locator('tr', {hasText: 'Cliente Exemplo'}).getByRole('button', {name: 'Reembolso'});
    if (await reembolso.count()) {
      await Promise.all([page.waitForLoadState('load'), reembolso.click()]);
      await page.goto(base + '/admin/produtos');
      const linha = page.locator('tr', {hasText: 'Camisa vendida (BUG-14)'});
      if (await linha.count()) {
        await linha.getByRole('button', {name: 'Excluir'}).click();
        await page.locator('#confirmDeleteModal.show').waitFor();
        await Promise.all([page.waitForLoadState('load'), page.click('#confirmDeleteButton')]);
        await page.waitForLoadState('networkidle');
        results.observacoes.bug14_exclusao_apos_reembolso = {
          alerta: await alerta(page),
          produto_continua_listado: await page.locator('tr', {hasText: 'Camisa vendida (BUG-14)'}).count() > 0,
        };
        await shot(page, 'bug14-2-exclusao-apos-reembolso');
      }
    }

    // ---------- BUG-09: formulário sem JavaScript ----------
    const noJs = await browser.newContext({viewport: {width: 1366, height: 900}, javaScriptEnabled: false});
    const pageNoJs = await login(noJs);
    for (const [chave, id, select] of [['roupa', ids.roupa, '#tamanho_roupa'], ['calcado', ids.calcado, '#tamanho_calcado']]) {
      await pageNoJs.goto(`${base}/admin/produtos/${id}/edit`);
      const antes = await pageNoJs.locator(select).inputValue();
      await Promise.all([pageNoJs.waitForLoadState('load'), pageNoJs.getByRole('button', {name: 'Atualizar'}).click()]);
      const alertaSalvar = await alerta(pageNoJs);
      await pageNoJs.goto(`${base}/admin/produtos/${id}/edit`);
      results.observacoes[`bug09_sem_js_${chave}`] = {
        tamanho_exibido_antes_de_salvar: antes,
        alerta_ao_salvar: alertaSalvar,
        tamanho_apos_salvar: await pageNoJs.locator(select).inputValue(),
        select_roupa_desabilitado: await pageNoJs.locator('#tamanho_roupa').isDisabled(),
        select_calcado_desabilitado: await pageNoJs.locator('#tamanho_calcado').isDisabled(),
      };
      await shot(pageNoJs, `bug09-sem-js-${chave}-apos-salvar`);
    }
    await noJs.close();

    // ---------- BUG-09: regressão com JavaScript (troca roupa -> calçado) ----------
    await page.goto(`${base}/admin/produtos/${ids.roupa}/edit`);
    await page.check('#tipo_calcado');
    await page.selectOption('#tamanho_calcado', '40');
    await Promise.all([page.waitForLoadState('load'), page.getByRole('button', {name: 'Atualizar'}).click()]);
    const alertaJs = await alerta(page);
    await page.goto(`${base}/admin/produtos/${ids.roupa}/edit`);
    results.observacoes.bug09_com_js_troca_para_calcado = {
      alerta_ao_salvar: alertaJs,
      tipo_apos_salvar: await page.locator('input[name=tipo_produto]:checked').inputValue(),
      tamanho_apos_salvar: await page.locator('#tamanho_calcado').inputValue(),
      select_roupa_visivel: await page.locator('#tamanho_roupa').isVisible(),
    };
    await shot(page, 'bug09-com-js-troca-para-calcado');

    // ---------- BUG-07: saída manual com o texto do pagamento ----------
    await page.goto(base + '/admin/caixa');
    await page.fill('#saida_valor', '1.00');
    await page.fill('#saida_motivo', `Pagamento consignado produto #${ids.consignado}`);
    await Promise.all([page.waitForLoadState('load'), page.getByRole('button', {name: 'Salvar saída'}).click()]);
    await page.goto(`${base}/admin/produtos/${ids.consignado}/edit`);
    await page.check('#estado_consignado');
    await page.fill('#produto_cliente_consignado', 'Consignante Exemplo');
    await page.check('#consignado_pago');
    await shot(page, 'bug07-1-marcando-consignado-pago');
    await Promise.all([page.waitForLoadState('load'), page.getByRole('button', {name: 'Atualizar'}).click()]);
    const alertaConsignado = await alerta(page);
    await page.goto(base + '/admin/caixa');
    const saidasCard = (await page.locator('.card-body', {hasText: 'Saídas:'}).innerText()).trim();
    results.observacoes.bug07_pagamento_consignado = {
      alerta_ao_salvar_produto: alertaConsignado,
      saida_manual: 'R$ 1,00',
      custo_devido_ao_consignante: 'R$ 10,00',
      total_de_saidas_no_caixa: saidasCard,
      linhas_de_saida: await page.locator('tbody tr', {hasText: 'Saída'}).count(),
    };
    await shot(page, 'bug07-2-caixa-apos-pagamento');
    results.erros_de_pagina = [];
  } catch (error) {
    results.falha_do_roteiro = String(error && error.stack || error);
    process.exitCode = 1;
  } finally {
    if (browser) await browser.close();
    server.kill();
    fs.writeFileSync(path.join(outDir, `resultados-${label}.json`), JSON.stringify(results, null, 2));
    console.log(JSON.stringify(results, null, 2));
  }
})();
