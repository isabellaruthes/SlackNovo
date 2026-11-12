// SLK-09 UI/UX audit: node auditoria-ui.cjs <repo> <label> <outDir>  (run with build ready; PHP needs pdo_sqlite)
// Screenshots (desktop/mobile), horizontal overflow, axe-core WCAG 2 A/AA, nav behaviour, validation messages.
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const {spawn, execFileSync} = require('node:child_process');
// Requires: PLAYWRIGHT_MODULE=<.../node_modules/playwright> AXE_CORE=<.../node_modules/axe-core/axe.min.js>
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const axeSource = fs.readFileSync(process.env.AXE_CORE, 'utf8');

const [repo, label, outDir] = process.argv.slice(2);
const PHP_EXT = ['-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', '-d', 'extension=fileinfo', '-d', 'extension=gd'];
const VIEWPORTS = {desktop: {width: 1366, height: 900}, mobile: {width: 390, height: 844}};

(async () => {
  fs.mkdirSync(outDir, {recursive: true});
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'slk-audit-'));
  const database = path.join(temp, 'db.sqlite');
  fs.writeFileSync(database, '');
  const password = crypto.randomBytes(24).toString('hex');
  const port = 8040 + Math.floor(Math.random() * 50);
  const base = `http://127.0.0.1:${port}`;
  const env = {...process.env, SLK_REPO: repo, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: base, APP_KEY: 'base64:' + crypto.randomBytes(32).toString('base64'),
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', CACHE_STORE: 'array', SESSION_DRIVER: 'database', QUEUE_CONNECTION: 'sync', SLK_TEST_PASSWORD: password};
  const fixture = JSON.parse(execFileSync('php', [...PHP_EXT, path.join(__dirname, 'fixture.php'), repo], {env, encoding: 'utf8'}).trim().split('\n').pop());
  const server = spawn('php', [...PHP_EXT, '-S', `127.0.0.1:${port}`, '-t', 'public', 'tests/Browser/router.php'], {cwd: repo, env, stdio: 'ignore'});
  const report = {label, commit: execFileSync('git', ['rev-parse', '--short', 'HEAD'], {cwd: repo, encoding: 'utf8'}).trim(), fixture, pages: [], checks: {}};
  let browser;
  try {
    for (let i = 0; i < 80; i++) { try { if ((await fetch(base + '/login')).ok) break; } catch {} await new Promise(r => setTimeout(r, 150)); }
    browser = await chromium.launch({headless: true});
    const pages = [
      ['catalogo', '/', false], ['login', '/login', false],
      ['dashboard', '/dashboard', true], ['produtos', '/admin/produtos', true], ['produto-novo', '/admin/produtos/create', true],
      ['produto-editar', `/admin/produtos/${fixture.produto}/edit`, true], ['caixa', '/admin/caixa', true],
      ['categorias', '/admin/categorias', true], ['cores', '/admin/cores', true], ['materiais', '/admin/materiais', true], ['fornecedores', '/admin/fornecedores', true],
    ];
    for (const [vpName, viewport] of Object.entries(VIEWPORTS)) {
      const ctx = await browser.newContext({viewport});
      const page = await ctx.newPage();
      const pageErrors = [];
      page.on('pageerror', e => pageErrors.push(e.message));
      let logged = false;
      for (const [name, url, auth] of pages) {
        if (auth && !logged) {
          await page.goto(base + '/login');
          await page.fill('#nome', 'operador-teste');
          await page.fill('#password', password);
          await Promise.all([page.waitForURL('**/dashboard'), page.click('button[type=submit]')]);
          logged = true;
        }
        await page.goto(base + url, {waitUntil: 'networkidle'});
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        const cssLoaded = await page.evaluate(() => [...document.styleSheets].some(s => (s.href || '').includes('/build/')));
        await page.addScriptTag({content: axeSource});
        const axe = await page.evaluate(async () => {
          const r = await axe.run(document, {runOnly: {type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']}});
          return r.violations.map(v => ({id: v.id, impact: v.impact, nodes: v.nodes.length, help: v.help, exemplo: v.nodes[0]?.target?.join(' ')}));
        });
        await page.screenshot({path: path.join(outDir, `${vpName}-${name}.png`), fullPage: true});
        report.pages.push({viewport: vpName, page: name, overflow_px: overflow, css_do_app: cssLoaded, axe});
      }
      // Navigation behaviour
      await page.goto(base + '/dashboard', {waitUntil: 'networkidle'});
      const toggler = page.locator('.navbar-toggler');
      report.checks[`nav_${vpName}`] = {
        toggler_visivel: await toggler.isVisible(),
        link_produtos_visivel_inicial: await page.getByRole('link', {name: 'Produtos', exact: true}).first().isVisible(),
      };
      if (await toggler.isVisible()) {
        await toggler.click();
        await page.locator('#adminNavigation.show').waitFor();
        await page.waitForTimeout(400);
        report.checks[`nav_${vpName}`].aberto_aria_expanded = await toggler.getAttribute('aria-expanded');
        report.checks[`nav_${vpName}`].link_produtos_visivel_aberto = await page.getByRole('link', {name: 'Produtos', exact: true}).first().isVisible();
        await page.screenshot({path: path.join(outDir, `${vpName}-menu-aberto.png`), fullPage: false});
      }
      report.checks[`page_errors_${vpName}`] = pageErrors;
      await ctx.close();
    }
    // Validation message language (server-side), bypassing HTML5 required with novalidate.
    const ctx = await browser.newContext({viewport: VIEWPORTS.desktop});
    const page = await ctx.newPage();
    await page.goto(base + '/login');
    await page.fill('#nome', 'operador-teste');
    await page.fill('#password', password);
    await Promise.all([page.waitForURL('**/dashboard'), page.click('button[type=submit]')]);
    // Keyboard: the first Tab should reach a visible "skip to content" link.
    await page.goto(base + '/admin/produtos', {waitUntil: 'networkidle'});
    await page.keyboard.press('Tab');
    report.checks.primeiro_tab = await page.evaluate(() => {
      const el = document.activeElement; const r = el.getBoundingClientRect();
      return {texto: (el.textContent || '').trim(), href: el.getAttribute('href'), visivel: r.width > 1 && r.height > 1};
    });
    await page.screenshot({path: path.join(outDir, 'desktop-teclado-primeiro-tab.png'), fullPage: false});
    await page.goto(base + '/admin/caixa');
    await page.evaluate(() => document.querySelectorAll('form').forEach(f => f.noValidate = true));
    await page.fill('#saida_valor', '-5');
    await page.fill('#saida_motivo', 'Teste');
    await Promise.all([page.waitForLoadState('load'), page.getByRole('button', {name: 'Salvar saída'}).click()]);
    report.checks.mensagem_validacao = (await page.locator('.alert').allInnerTexts()).join(' | ');
    await page.screenshot({path: path.join(outDir, 'desktop-validacao-caixa.png'), fullPage: false});
    await page.goto(base + '/admin/produtos/create');
    await page.evaluate(() => document.querySelectorAll('form').forEach(f => f.noValidate = true));
    await Promise.all([page.waitForLoadState('load'), page.getByRole('button', {name: 'Salvar'}).click()]);
    report.checks.mensagem_validacao_produto = (await page.locator('.alert').allInnerTexts()).join(' | ');
    report.checks.campos_com_is_invalid = await page.locator('.is-invalid').count();
    await page.screenshot({path: path.join(outDir, 'desktop-validacao-produto.png'), fullPage: true});
    await ctx.close();
  } catch (e) {
    report.falha = String(e && e.stack || e);
    process.exitCode = 1;
  } finally {
    if (browser) await browser.close();
    server.kill();
    fs.writeFileSync(path.join(outDir, `auditoria-${label}.json`), JSON.stringify(report, null, 2));
  }
})();
