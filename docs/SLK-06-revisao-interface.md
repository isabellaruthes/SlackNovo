# SLK-06 — Revisão de interface e experiência do usuário

Revisão local concluída em 08/11/2026 após a SLK-05. Escopo: catálogo, login, navegação administrativa, produtos, caixa, feedback e acessibilidade básica dos templates. Os achados abaixo descrevem o código anterior às melhorias da SLK-09. As referências de linha são daquele estado; arquivos foram modificados depois.

| ID | Prioridade | Evidência anterior | Impacto e critério de aceite |
| --- | --- | --- | --- |
| UX-01 | P1 | `resources/views/admin/caixa/index.blade.php:23–27` omitia `valor_venda`, exigido por `CaixaController::registrarVenda` | Venda pelo formulário do caixa sempre falha na validação. Formulário deve permitir valor válido e concluir a venda. |
| UX-02 | P1 | `resources/views/admin/produtos/_form.blade.php:85` não selecionava gênero atual nem `old()` | Editar e salvar produto sem tocar no gênero envia vazio e pode apagar o dado. Valor existente e valor submetido após erro devem permanecer selecionados. |
| UX-03 | P1 | `resources/views/layouts/app.blade.php:24` renderizava somente conteúdo; partial de alertas não era incluída | Mensagens de sucesso, erro e validação não eram exibidas nos módulos que dependem do layout. Resposta após operação deve exibir feedback acessível. |
| UX-04 | P2 | `resources/views/components/navbar.blade.php:4` sem botão de expansão e sem collapse funcional | Navegação em telas pequenas sem padrão responsivo Bootstrap. Botão deve controlar menu e expor estado de expansão. |
| UX-05 | P2 | `resources/views/catalog/dashboard.blade.php:64,85` criava imagem mesmo quando `imagen` é nula | Produtos sem foto, aceitos pelo cadastro, causavam requisição a `/storage/` e imagem quebrada. Mostrar estado sem imagem sem gerar essa requisição. |
| UX-06 | P2 | Formulário de produto e filtros públicos tinham labels sem `for`/`id`; caixa dependia de placeholders | Campos sem associação explícita prejudicam foco por label e nomes acessíveis. Campos alterados devem ter associação válida; tamanho deve acompanhar seletor visível. |
| UX-07 | P2 | `resources/views/admin/produtos/index.blade.php:8` sem wrapper responsivo e sem estado vazio | Tabela longa pode extravasar viewport e listagem vazia não orienta usuário. Tabela deve rolar dentro do contêiner e apresentar mensagem de vazio. |
| UX-08 | P3 | Catálogo contém contatos/endereço e redes sociais de exemplo | Informações não representam operação real. Substituir por dados confirmados pelo responsável antes da publicação; não inventar contatos. |

## Método e limites

Inspeção direta de templates, seus campos e contratos de validação dos controllers, apoiada pelo levantamento SLK-02 e revisão SLK-05. A compilação Blade valida geração de templates, sem comprovar por si só fluxo HTTP, responsividade visual ou execução de JavaScript. A validação funcional independente cabe à SLK-10 após as melhorias. Não foram alegados resultados de auditoria WCAG completa, leitor de tela ou produção.

SLK-06 concluída como revisão com evidências e critérios. SLK-07 pode tratar bugs de backend encontrados pela revisão geral; SLK-09 aplica as correções de interface descritas no documento correspondente. Nenhum commit realizado.
