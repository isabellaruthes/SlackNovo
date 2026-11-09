# SLK-05 — Revisão de segurança do sistema

## Resultado

Revisão do código local concluída em 08/11/2026, como pré-requisito da SLK-06. Foram examinados autenticação, autorização, sessão, CSRF, saídas HTML/JavaScript, upload de imagens, consultas e exposição de dados. Não foram confirmadas falhas P0/P1 nesse recorte. Foram identificadas duas melhorias P2 de defesa do navegador e condições de implantação a validar antes da publicação.

Este resultado não equivale a certificação de segurança: produção, TLS, proxy, permissões do servidor, dependências vulneráveis e testes completos de intrusão não foram avaliados. Nenhum código de aplicação foi alterado, nenhum commit foi feito e não foi realizada auditoria online de dependências.

## Método e evidências executadas

1. Inspeção das rotas, controllers, validações, modelos, templates e configuração versionada.
2. Revisão dos testes existentes. A execução integral realizada na SLK-02 neste mesmo checkout passou: **60 testes, 1135 asserções, 15,83 s**, incluindo autenticação, CSRF e uploads. Esse resultado foi reutilizado; a suíte não foi repetida sem mudança de código.
3. Prova pontual pelo kernel HTTP do Laravel, com conexão SQLite substituída por `:memory:`, novas migrações apenas na memória, sessão/cache em array e debug desativado. Produto criado somente nesse banco isolado com `<script>alert(42)</script>` no nome e descrição:
   - `GET /`: HTTP 200, payload literal ausente e versão escapada presente.
   - `GET /?q=<payload codificado>`: HTTP 200, payload literal ausente e versão escapada presente.
   - `GET /admin/relatorios/produtos` anônimo: HTTP 302 para autenticação.
   - Resposta do catálogo gerada pela aplicação: ausentes `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` e `Strict-Transport-Security`.

A prova não consultou nem alterou registros do SQLite local. A ausência de cabeçalhos no kernel não comprova ausência no proxy de produção. Os testes de XSS foram verificações da resposta HTML; não houve execução em navegador.

## Achados priorizados

Prioridades: P1 = correção urgente de falha confirmada com impacto relevante; P2 = melhoria planejada de proteção; P3 = melhoria de documentação/operação. Itens condicionais de publicação estão explicitamente separados de falhas comprovadas.

### SEC-01 — Definir proteção contra enquadramento e política de cabeçalhos — P2

**Evidência:** `bootstrap/app.php:13` não adiciona middleware próprio de cabeçalhos; `public/.htaccess:1` contém regras de rewrite, sem política de segurança. A prova HTTP descrita acima confirmou ausência de `X-Frame-Options` e CSP na resposta da aplicação.

**Implicação:** o código não estabelece uma política para impedir enquadramento por outro site. Isso deixa a proteção contra clickjacking dependente da infraestrutura. Exploração prática e operações autenticadas por iframe não foram demonstradas; SameSite e contexto de navegação influenciam o cenário.

**Encaminhamento:** definir uma política coerente no app ou proxy, incluindo `frame-ancestors` e/ou X-Frame-Options, `nosniff` e Referrer-Policy. Avaliar CSP compatível com os scripts e estilos inline existentes, em vez de inserir uma política que quebre a interface. HSTS pertence ao ambiente HTTPS confirmado e não deve ser ativado indiscriminadamente no localhost.

**Aceite:** cabeçalhos verificados na resposta real do ambiente publicado; tentativa de enquadramento por origem externa bloqueada; fluxos de login, formulários e modais continuam operacionais.

### SEC-02 — Recursos CDN executáveis sem verificação de integridade — P2

**Evidência:** `resources/views/layouts/app.blade.php:8`, `:9` e `:40` carregam CSS, ícones e Bootstrap JS por jsDelivr sem atributo `integrity`; o script recebe acesso ao DOM também das páginas autenticadas.

**Implicação:** URLs têm versão fixada, porém o navegador não verifica o hash do conteúdo recebido. O sistema confia no conteúdo entregue pelo terceiro. Não há evidência de comprometimento do CDN ou pacote.

**Encaminhamento:** empacotar esses recursos no build local ou adicionar SRI e a configuração CORS correspondente para os arquivos fixados. Evitar duplicação de Bootstrap entre bundle e CDN.

**Aceite:** arquivos de terceiro executáveis são servidos localmente pelo build ou passam por verificação de integridade; login, navegação e modais funcionam após o build.

## Controles existentes validados

| Área | Evidência | Resultado e limite |
| --- | --- | --- |
| Acesso privado | `routes/web.php:22`; `AuthenticationSecurityTest.php:179` | Dashboard, administração, JSON e exportação exigem autenticação e sessão válida; teste percorre endpoints privados para anônimos. |
| Senhas | `app/Models/User.php:9`, `:13`, `:15`; `AuthController.php:41`; `CriticalFlowsTest.php:20` | Modelo aponta para `usuarios`, oculta `senha` na serialização e fornece coluna correta ao autenticador; teste valida rehash. Não há fluxo público de cadastro ou alteração de senha. |
| Limites de login | `AuthController.php:15`, `:28`, `:38`, `:71`; `AuthenticationSecurityTest.php:41` | Limite de 5 falhas por conta e 30 tentativas por IP em 60 s; erros genéricos e entradas inválidas cobertos. Não foi medida proteção distribuída sob carga nem avaliada configuração de proxies confiáveis. |
| Sessões | `AuthController.php:51`, `:54`, `:62`; `routes/web.php:22` | Login regenera sessão, logout invalida sessão e token; `auth.session` revoga sessão por troca de hash. Testes cobrem replay de cookie e revogação. |
| CSRF | `bootstrap/app.php:9`, `routes/web.php`; `AuthenticationSecurityTest.php:202` | Grupo web aplica proteção padrão; testes habilitam CSRF explicitamente e percorrem POST/PUT/PATCH/DELETE sem token, esperando 419. Formulários inspecionados têm `@csrf`. |
| XSS | `resources/views/catalog/dashboard.blade.php:23`, `:67`, `:91`; `resources/views/layouts/app.blade.php:56` | Interpolações Blade escapadas e confirmação de exclusão usa `textContent`. Prova de nome/descrição/busca com script foi escapada. Busca estática não encontrou `{!!`, `innerHTML`, `document.write` ou `eval` no código próprio examinado. Não cobre todas as combinações ou comportamento do navegador. |
| Uploads | `ProdutoRequest.php:23`; `ProdutoController.php:97`, `:134`; `ProductSecurityTest.php:138` | Imagem validada, limite de 5120 KiB, caminho de armazenamento gerado pelo framework; testes rejeitam PHP disfarçado, SVG e arquivo acima do limite. Limpeza restringe caminho ao diretório de produtos. Não foram testados todos os polyglots, volume/cotas ou configuração de execução no servidor. |
| Escrita e atribuição | `ProdutoController.php:70`, `:109`; `ProdutoRequest.php:21`; `MoneyRules.php:9` | Produto usa apenas entrada validada e modelos declaram fillable; relações e limites monetários validados. Não é garantia para novas rotas futuras. |
| Consultas | `CatalogController.php:32`; `DashboardController.php:55`; `RelatorioController.php:13` | Valores são passados a Query Builder; expressões raw inspecionadas derivam de nomes de colunas escolhidos pelo código, sem concatenação direta de entrada do usuário. Nenhuma injeção SQL foi reproduzida. |
| Dados públicos | `CatalogController.php:31`; `resources/views/catalog/dashboard.blade.php:60` | Catálogo filtra produtos disponíveis e expõe nome, preço de venda e descrição, sem imprimir preço de compra/consignante. JSON completo de produtos está atrás de autenticação; necessidade de restringir seus campos depende dos usuários autorizados definidos pelo negócio. |

## Autorização e requisitos não estabelecidos

`routes/web.php:22` protege o grupo inteiro por autenticação, sem papéis distintos. `ProdutoRequest::authorize()` retorna true em `ProdutoRequest.php:10`, com autorização de acesso feita pelas rotas. Isso é compatível com o modelo atual de todos os usuários cadastrados serem operadores administrativos.

Não foi inventado um requisito de vários níveis de administrador nem classificada sua ausência como vulnerabilidade. Se houver intenção de cadastrar clientes ou operadores com permissões distintas, será necessário definir uma matriz e implementá-la antes de conceder esses acessos. A criação do primeiro operador continua ausente do seeder; deve ser documentada com hash seguro e sem credenciais padrão versionadas.

## Configuração e condições de publicação

Estas condições estão **pendentes por falta de acesso ao ambiente hospedado**, sem comprovação de falha em produção:

- `.env.example:2` e `:4` são apropriados para desenvolvimento (`local`, debug ativo). Produção deve usar configuração própria e debug desativado; `config/app.php:42` já permite isso por variável de ambiente. Não considerar o `.env.example` uma configuração pronta de publicação.
- `config/session.php:172` permite cookie Secure por ambiente; `:185` mantém HttpOnly por padrão e `:202` define SameSite lax. Confirmar HTTPS e cookie Secure efetivo no servidor. Sessão sem criptografia do payload (`:50`) não significa cookie de sessão em texto aberto, mas reforça a necessidade de proteger banco/backups.
- `public/` deve ser a raiz pública; `.env`, SQLite, logs, `.git`, fontes e backups devem permanecer fora da área servida. A configuração real de Apache/Nginx e execução de arquivos enviados não está disponível para avaliação.
- `.gitignore:3` exclui `.env`, e `git ls-files '.env*'` retornou somente `.env.example`. Isso confirma ausência de `.env` no snapshot rastreado atual, não ausência de segredos em todo histórico Git. Não foi feita varredura de segredos no histórico.
- Falta documentação de backup, restauração, rotação de credenciais e provisionamento de operadores. Tratar como documentação operacional P3, com validação antes de exposição pública.

## Conclusão para dependências

SLK-05 está concluída no escopo de revisão local: controles existentes examinados, baseline de testes identificado, provas isoladas realizadas e melhorias registradas com limites. **SLK-06 pode iniciar**, utilizando SEC-01/SEC-02 e as condições de publicação como entradas, sem transformar condições não verificadas em falhas confirmadas.
