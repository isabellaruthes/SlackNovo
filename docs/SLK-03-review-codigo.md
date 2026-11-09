# SLK-03 — Review geral do código e backlog priorizado

## Resultado

Revisão concluída no checkout local em 08/11/2026, após o levantamento `SLK-02-levantamento-tecnico.md`. Foram identificados seis defeitos reproduzidos e melhorias de manutenção/desempenho. A conclusão desta atividade significa que o código foi analisado e os achados foram transformados em backlog; não significa que os defeitos foram corrigidos ou que o sistema está aprovado para produção.

Nenhum código de aplicação foi alterado nesta revisão. Nenhum commit foi realizado. As reproduções usaram uma conexão SQLite `:memory:` criada após o bootstrap do Laravel, com migrações aplicadas nesse banco isolado; o banco local e seus registros não foram utilizados nas reproduções.

Prioridades: **P1** = perda relevante da informação apresentada, corrigir antes de uso operacional do fluxo; **P2** = defeito funcional com cenário delimitado, corrigir antes da implantação; **P3** = defeito de apresentação ou melhoria de menor impacto. Não foi reproduzido um defeito P0 nesta atividade.

## Escopo e evidências

Inspecionados: rotas, autenticação, controllers públicos e administrativos, modelos, requests de validação, regras monetárias, templates relacionados aos fluxos, migrações e testes existentes. Os fluxos de venda/reembolso, consignação, upload/remoção de imagens, catálogo, dashboard, relatórios e cadastros auxiliares foram considerados.

Baseline herdada e comprovada na SLK-02: **60 testes aprovados, 1135 asserções**, com SQLite em memória. Não foi repetida a suíte inteira nesta revisão, pois não houve alteração de código. Foram feitas reproduções adicionais direcionadas de cenários ausentes na suíte, executando os controllers com requisições construídas localmente e verificando o retorno. Os PDFs produzidos também foram lidos por `pdfinfo` e `pdftotext` disponíveis no ambiente.

## Backlog de defeitos confirmados

| ID | Prioridade | Defeito | Evidência principal |
| --- | --- | --- | --- |
| REV-01 | P1 | Exportação perde visualização das vendas após o limite da primeira página | `RelatorioController.php:68`, `:72`, `:79` |
| REV-02 | P2 | Acentos corrompidos no PDF | `RelatorioController.php:71`, `:81` |
| REV-03 | P2 | PDF apresenta vendas reembolsadas sem identificar o reembolso | `RelatorioController.php:36`, `:45` |
| REV-04 | P2 | Dashboard duplica produto quando duas vendas têm o mesmo timestamp | `DashboardController.php:55`, `:62` |
| REV-05 | P2 | Filtro de pesquisa do dashboard aceita estrutura inválida e causa erro interno | `DashboardController.php:74` |
| REV-06 | P3 | Data do PDF ignora a formatação brasileira pretendida | `RelatorioController.php:50`, `app/Models/Venda.php:20` |

Os caminhos abreviados dos controllers na tabela referem-se a `app/Http/Controllers/Admin/`. As linhas correspondem ao estado inspecionado nesta revisão e podem mudar após correções.

### REV-01 — Paginar e quebrar linhas na exportação PDF

**Impacto:** o PDF não apresenta todas as vendas visualmente, embora seus bytes ainda contenham o texto. O gerador fixa uma única página A4 e desloca cada linha 14 pontos para baixo, sem verificar o limite inferior ou a largura. Relatórios operacionais com mais registros ficam incompletos para leitura/impressão. Nomes e compradores longos também podem ultrapassar a margem direita.

**Reprodução validada:** em banco em memória, criar 65 vendas com nome, comprador, valores e data válidos. Chamar `RelatorioController::exportar(Request::create('/admin/relatorios/exportar'))` e gravar o conteúdo retornado em um PDF temporário. `pdfinfo` informou `Pages: 1`; `pdftotext -layout` retornou registros somente até `#56`, enquanto o conteúdo bruto continha os registros até `#65`. O código sempre emite `/Count 1`.

**Recomendação:** gerar documento com paginação automática, cabeçalho, margens e quebra de texto; Dompdf já está instalado e pode ser utilizado. Caso se mantenha o gerador manual, implementar paginação e layout completos.

**Critérios de aceite:** relatório com pelo menos 100 vendas contém todos os IDs na extração textual e nas páginas; nome/comprador no limite de 50 caracteres não cortam a data ou valores; testar relatório vazio e filtro de período. Não basta verificar apenas o status HTTP ou a assinatura `%PDF`.

### REV-02 — Preservar caracteres portugueses no PDF

**Impacto:** o relatório e os nomes de produtos/compradores ficam ilegíveis ou incorretos. O gerador escreve bytes UTF-8 diretamente com uma fonte Type1 Helvetica sem configuração que represente esses caracteres.

**Reprodução validada:** exportar uma venda chamada `Peça ação 3`. A extração textual retornou `Peˆ§a aˆ§ˆ£o 3`, e o título `Relatório de Vendas` retornou `Relatˆ‡rio de Vendas`.

**Recomendação:** usar renderizador e fonte com suporte Unicode ou implementar uma codificação de PDF e fonte compatíveis. Integrar a correção com REV-01 evita manter um mecanismo de PDF incompleto.

**Critérios de aceite:** título, nome e comprador contendo `á à ã â é ê í ó ô õ ú ç` permanecem corretos tanto no documento renderizado quanto na extração textual. Aspas, parênteses e barras não quebram o arquivo.

### REV-03 — Identificar reembolsos na exportação

**Impacto:** caixa e indicadores de entradas excluem vendas reembolsadas, mas a exportação inclui todas as vendas e não imprime o campo `reembolsada`. O leitor não consegue distinguir uma venda ativa de uma devolvida e pode interpretar o valor devolvido como receita efetiva. Incluir histórico de reembolsos é válido; apresentá-lo sem o estado perde informação financeira relevante.

**Reprodução validada:** criar duas vendas, uma com `reembolsada=true` e outra `false`, exportar e conferir o texto. Ambas aparecem com o mesmo formato `Produto / Comprador / Valor / Data`, sem indicador do reembolso; a consulta do controller não filtra esse campo. Comparar com `CaixaController::index`, que soma apenas vendas ativas.

**Recomendação:** definir a exportação como histórico com coluna de situação e totais separados de vendas ativas/reembolsadas, ou filtrar apenas vendas ativas e informar esse escopo explicitamente. Não apagar o histórico para resolver a divergência.

**Critérios de aceite:** uma venda, reembolso e revenda produzem relatório que distingue os estados; o total líquido, se apresentado, concilia com as entradas do caixa para o mesmo escopo de datas; teste verifica a situação e os valores exportados.

### REV-04 — Selecionar uma única venda vigente por produto no dashboard

**Impacto:** o catálogo administrativo pode mostrar dois cartões do mesmo produto e comprador anterior como se fosse atual. O join seleciona `MAX(data_hora)` e depois todas as vendas com aquela data, sem desempate por ID ou filtro de reembolso. `data_hora` é gravada com precisão de segundos, e reembolso/revenda do mesmo produto são fluxos suportados.

**Reprodução validada:** criar um produto vendido, uma venda reembolsada e uma venda ativa para ele, ambas com `data_hora='2026-11-08 12:00:00'` e compradores `Anterior`/`Atual`. Invocar `DashboardController::index`. A coleção `produtos` retornou **2** entradas para **1** produto, contendo ambos os compradores. Essa massa respeita o estado de uma única venda ativa; não exige duas vendas simultaneamente ativas.

**Recomendação:** selecionar a venda ativa de forma determinística, com desempate por ID quando necessário, preservando uma linha por produto. Caso se deseje histórico para produtos disponíveis, tratá-lo separadamente dos dados da venda vigente.

**Critérios de aceite:** com reembolso e revenda no mesmo segundo, o dashboard retorna um único produto e o comprador da venda ativa; após reembolso, não apresenta a venda reembolsada como atual; a navegação/filtros não introduzem duplicatas.

### REV-05 — Validar os filtros do dashboard

**Impacto:** parâmetros com estrutura inválida provocam exceção em vez de erro de validação. A falha é restrita à rota autenticada; não foi demonstrado vazamento, SQL injection ou indisponibilidade geral. O catálogo público já possui validação equivalente que pode servir de referência.

**Reprodução validada:** chamar `DashboardController::index(Request::create('/dashboard', 'GET', ['q' => ['texto']]))`. Resultado: `ErrorException: Array to string conversion`, gerado no uso de `$request->string('q')` sem validação anterior.

**Recomendação:** validar `q` como string com tamanho limitado, `status` por enum, `categoria` como identificador inteiro e `tamanho` como string limitada, antes de convertê-los. Tratar a resposta de validação de forma utilizável na interface.

**Critérios de aceite:** entradas como `q[]=texto`, `status[]=vendido`, categorias não inteiras e pesquisas excessivamente longas produzem erro de validação controlado; filtros válidos continuam retornando os produtos esperados. Não retornar HTTP 500.

### REV-06 — Formatar a data da venda explicitamente

**Impacto:** o PDF mostra a data como `2026-11-08 12:00:00`, apesar de haver intenção no código de produzir `08/11/2026 12:00`. `optional(...)->format` consulta uma propriedade, não verifica a existência de um método; o modelo Venda também não faz cast de `data_hora` para datetime.

**Reprodução validada:** o mesmo PDF temporário das reproduções anteriores continha `Data: 2026-11-08 12:00:00`, confirmado no conteúdo bruto e na extração textual.

**Recomendação:** definir cast datetime para `data_hora` ou converter explicitamente por Carbon com tratamento de nulos, e chamar o método de formatação diretamente.

**Critérios de aceite:** uma venda em `2026-11-08 12:00:00` é apresentada em `08/11/2026 12:00`; data ausente recebe uma indicação definida; o fuso horário do relatório é documentado.

## Melhorias separadas dos defeitos

| ID | Prioridade | Melhoria e justificativa | Critério de aceite proposto |
| --- | --- | --- | --- |
| MEL-01 | P2 | Paginar o dashboard e o histórico de caixa. Ambos carregam coleções completas com `get()`; caixa ainda ordena e mescla em memória. A exportação também carrega todas as vendas de uma vez. Não foi feito teste de carga nem comprovado um limiar de falha. | Navegação paginada com filtros preservados; medir tempo/memória em volume representativo acordado com a operação. |
| MEL-02 | P2 | Documentar/provisionar o primeiro administrador. A tabela local está vazia e o seeder não cria usuário. | Procedimento repetível que não sobrescreve usuário existente nem fixa credencial no repositório. |
| MEL-03 | P2 | Parametrizar contato, WhatsApp e endereço do catálogo. O template contém número e endereço de exemplo. | Configuração do estabelecimento refletida nos links e texto; teste do formato do destino. |
| MEL-04 | P3 | Extrair regras de venda, reembolso e consignação dos controllers para serviços de domínio. | Mesmos contratos HTTP e invariantes cobertos por testes; transações/bloqueios preservados. |
| MEL-05 | P3 | Consolidar os modelos duplicados `User` e `Usuario`, e atualizar a factory padrão que ainda descreve campos `name/email/password`. | Factory cria um usuário autenticável na tabela efetiva; configuração e testes utilizam uma representação consistente. |
| MEL-06 | P3 | Revisar dependências e recursos legados após avaliar uso real. Dompdf está instalado e não usado; coexistem CSS Bootstrap/Tailwind e scripts não importados pelo entrypoint. | Remover somente o que tiver ausência de uso comprovada, com build e fluxos de interface validados. |

## Encaminhamento à revisão de banco e segurança

A SLK-04 deve aprofundar estes pontos antes de qualquer migração de correção:

- `vendas.id_produto` é acrescentado como inteiro nullable sem FK na migração `2026_05_04_115014`; conferir exclusão de produto e preservação do histórico, antes de optar por restrição, soft delete ou outra política. Não foi classificado como perda automática de vendas: o código atual não declara cascade nessa coluna.
- A identidade do pagamento consignado é inferida pelo texto `Pagamento consignado produto #ID` em `saida_caixas.motivo`, sem vínculo explícito com produto/pagamento. Avaliar colisões, rastreabilidade e integridade conforme a regra do negócio.
- A venda usa transação e `lockForUpdate`, mas a validação em SQLite isolado não comprova concorrência em MySQL/PostgreSQL nem tratamento de contenda SQLite. Avaliar índices e garantias de uma venda ativa por produto no banco alvo.
- Há migrações legadas que criam tabelas inicialmente vazias ou auxiliares e depois alteram o esquema. Fazer análise de upgrade e rollback em cópia representativa antes de limpeza de migrações.

A revisão de segurança SLK-05 deve aprofundar as configurações de ambiente/deploy, autorização esperada pelo negócio, dependências e controles de aplicação. Não se classificou ausência de papéis como vulnerabilidade por si só: o comportamento atual é permitir os módulos a qualquer usuário autenticado e a exigência de perfis não foi fornecida.

## Ordem sugerida e validação de conclusão

1. Corrigir REV-01/REV-02 conjuntamente no gerador PDF, depois REV-03/REV-06 no conteúdo do relatório.
2. Corrigir REV-04 e REV-05 com testes direcionados de regressão.
3. Usar os achados na SLK-04/SLK-05 e na revisão de interface SLK-06; tratar melhorias acordadas antes da bateria funcional SLK-10.

O baseline verde não detectava esses cenários. As correções precisam incluir testes do conteúdo do PDF, desempate de vendas e entradas inválidas; repetir apenas o baseline não é evidência suficiente de correção.

Critério de conclusão da SLK-03: áreas do código inventariadas, achados reproduzidos, riscos separados de defeitos confirmados e backlog com prioridade, impacto e aceite. **Atendido no ambiente local.** Testes de navegador, carga, auditoria de dependências online, concorrência real e produção não foram executados nesta atividade.
