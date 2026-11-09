# SLK-10 — Testes funcionais e evidências

Validação local concluída em 08/11/2026 após SLK-07 e SLK-09. Nenhum commit realizado. Resultado: **81 testes PHP aprovados, 1400 asserções**, build concluído e cenários de navegador desktop/mobile aprovados após corrigir uma colisão CSS identificada durante a validação.

## Execuções e isolamento

| Verificação | Resultado |
| --- | --- |
| `php artisan test --no-ansi` | 81 aprovados, 1400 asserções, 16,79 segundos |
| `npm run build` | Vite concluiu; 58 módulos transformados |
| Chromium headless com Playwright | Cenários abaixo aprovados; zero eventos `pageerror` |
| `git diff --check` | Sem problemas de whitespace |
| Sintaxe PHP/Node dos utilitários de navegador | Sem erros |

A suíte PHP usa SQLite `:memory:` conforme `phpunit.xml`. O navegador usa uma nova pasta temporária `/tmp/slacknovo-acceptance-*`, SQLite próprio, usuário temporário e servidor exclusivo na porta 8010. O processo encerra esse servidor ao final. Os arquivos temporários ficam disponíveis para diagnóstico, sem integrar o repositório.

O fixture verifica tanto o caminho temporário recebido quanto a conexão efetiva configurada no Laravel **antes** de executar migrações; configuração em cache apontando para outro banco aborta a execução. O router de teste utiliza o manifest do build e ignora o arquivo hot apenas nesse processo; o `public/hot` do servidor do usuário não é apagado nem alterado.

Após os testes, o banco local `database/database.sqlite` continuou com **0 usuários, 0 produtos, 0 vendas e 0 saídas**, iguais aos valores anteriores. O usuário do navegador não foi criado na instalação local.

## Cobertura funcional

| Área | Evidência e resultado |
| --- | --- |
| Autenticação | Suíte existente cobre limites, erros genéricos, sessão, CSRF, anônimos e revogação. Navegador confirmou login real, redirecionamento anônimo, logout e retorno ao login ao acessar dashboard novamente. |
| Cadastros auxiliares | Novo teste HTTP percorre criação, listagem, atualização, rejeição de nome vazio e exclusão de cores, materiais e fornecedores pelas rotas efetivas. Categorias já possuem teste funcional existente. |
| Catálogo | Novo teste verifica combinação de busca/categoria/tamanho/ordenação, exclusão de vendidos, total de 25 resultados, páginas de 24 + 1 itens e preservação de filtros no link seguinte. Navegador abre catálogo com 24 cartões e modal de detalhes de produto sem foto. |
| Produto sem imagem | HTML sem `img` apontando para `/storage/`; placeholder exibido. Verificado por HTTP e navegador. |
| Produtos | Novo teste cria e exclui produto com feedback visível e estado vazio. Edição preserva gênero atual; erro de validação mantém gênero submetido sem alterar o registro. Navegador edita produto sem tocar no gênero e confirma persistência após retornar à edição. |
| Caixa | Novo teste e navegador enviam o formulário real com `valor_venda`, registram venda, reembolso e saída. HTTP também verifica bloqueio de venda duplicada, entradas/saldo e status do produto após reembolso. |
| Feedback | Mensagens de sucesso e erro de negócio renderizadas; erros de validação expostos com `role=alert`. Navegador confirmou feedback após edição, venda, reembolso e saída. |
| Dashboard | `ReviewRegressionTest` cobre uma linha por produto, desempate de vendas, comprador atual e validação segura de filtros. Navegador confirmou acesso após login e links administrativos visíveis no desktop. |
| PDF | `ReviewRegressionTest` cobre relatório grande, acentos, datas, reembolsos, período e vazio. A SLK-07 registra prova adicional com ferramentas de PDF: 100 IDs extraídos, três páginas e texto preservado. |
| Mobile | Chromium em 390 × 844: menu inicia oculto, abre visivelmente com links, fecha após transição e reabre; `aria-expanded` acompanha estado. Página de produtos e catálogo sem overflow horizontal da página nos cenários verificados. Tabela de produtos mantém rolagem em seu contêiner. |
| Uploads e integridade | Suíte existente cobre rejeição de upload inválido, limpeza transacional, relações e migrações. Não foram repetidos cenários equivalentes em novos testes. |

## Defeito encontrado no navegador e reteste

A primeira execução encontrou o menu administrativo oculto mesmo com a classe Bootstrap `show`: a utilidade Tailwind `collapse` definia visibilidade incompatível com o componente Bootstrap. O agente de interface corrigiu a visibilidade de `.app-navbar .navbar-collapse`, preservando o controle de `display` pelo Bootstrap, e recompilou o CSS. A correção também restaurou os links no desktop.

No reteste, uma asserção inicial do utilitário verificava fechamento antes de a animação terminar. O utilitário foi ajustado para aguardar o elemento efetivamente oculto; esse ajuste de sincronização do teste é distinto do defeito CSS do aplicativo.

A execução final de navegador passou integralmente. A suíte PHP inteira foi executada uma vez após as alterações PHP; a correção posterior foi somente CSS e recebeu rebuild e reteste de navegador, sem repetição desnecessária de toda a suíte.

A revisão independente final do diff pelo coordenador não apontou novos achados nesse recorte. As evidências visuais também foram inspecionadas; isso complementa os testes sem ampliar sua cobertura declarada.

## Evidências visuais

- [Caixa desktop após venda, reembolso e saída](evidencias/SLK-10-desktop-caixa.png).
- [Menu administrativo aberto em celular](evidencias/SLK-10-mobile-menu.png).

As capturas contêm apenas massa fictícia do banco temporário. O arquivo desktop permite observar links administrativos visíveis, feedback e saldo esperado após reembolso/saída. A captura mobile mostra o menu expandido e a listagem em tela estreita.

## Como repetir

Testes PHP e build usam dependências do projeto:

```bash
php artisan test --no-ansi
npm run build
```

O utilitário `tests/Browser/acceptance.cjs` requer instalação externa de Playwright e Chromium; não foi adicionada dependência ao `package.json` ou aos locks. Informar os caminhos da instalação local:

```bash
PLAYWRIGHT_MODULE=/caminho/para/node_modules/playwright \
PLAYWRIGHT_BROWSERS_PATH=/caminho/para/browsers \
node tests/Browser/acceptance.cjs
```

Executar na raiz do projeto, com o build pronto e a porta 8010 livre. `SLK_TEST_PORT` permite escolher outra porta. Neste ambiente o sandbox bloqueia os sockets do navegador/servidor; a execução foi autorizada fora dele. O utilitário não exige desativar o sandbox de uma instalação comum nem modificar as permissões do projeto.

## Limites e conclusão

SLK-10 concluída no escopo local disponível: funcionalidades principais exercitadas, regressões comprovadas, correção do defeito encontrado no navegador retestada e evidências registradas. Isso não comprova homologação pelo negócio, produção/hospedagem, desempenho sob carga, concorrência real entre múltiplos operadores, todos os navegadores, auditoria WCAG completa ou ausência de vulnerabilidades.

Permanecem os encaminhamentos de SLK-03/SLK-05/SLK-06: provisionamento do primeiro operador, dados reais de contato, paginação em volume, revisão aprofundada do banco e proteções de implantação. A SLK-11 pode usar estas evidências como entrada; o ambiente de produção ainda precisa ser identificado e validado.
