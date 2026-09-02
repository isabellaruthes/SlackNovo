# Padrão de Commits e Fluxo de PR

Este documento define o padrão de commits e o fluxo de Pull Requests (PRs) a ser seguido por todo o time neste projeto.

## Padrão de Commits (Conventional Commits)

Formato: `tipo: descrição curta`

### Tipos utilizados

| Tipo       | Quando usar                                                  |
|------------|---------------------------------------------------------------|
| `feat`     | Nova funcionalidade                                            |
| `fix`      | Correção de bug                                                |
| `docs`     | Mudança em documentação                                        |
| `style`    | Formatação, sem alteração de lógica (espaços, ponto e vírgula) |
| `refactor` | Refatoração de código sem mudar comportamento                  |
| `test`     | Adição ou ajuste de testes                                      |
| `chore`    | Tarefas de manutenção (configs, dependências, etc.)             |

### Exemplos

```
feat: adiciona tela de login
fix: corrige erro ao salvar mensagem no chat
docs: atualiza README com instruções de instalação
refactor: simplifica lógica de autenticação
chore: atualiza dependências do projeto
```

### Boas práticas

- Escreva mensagens curtas e diretas, no imperativo (ex: "adiciona", "corrige", não "adicionado" ou "adicionando").
- Um commit deve representar uma única mudança lógica.
- Evite commits gigantes misturando várias alterações não relacionadas.

## Fluxo de Pull Request (PR)

1. **Crie uma branch por tarefa/feature**
   Nunca trabalhe direto na `main` ou na `develop`. Crie uma branch a partir da `develop` com nome descritivo:
   ```
   git checkout -b feature/tela-login
   git checkout -b fix/bug-envio-mensagem
   ```

2. **Faça commits organizados**
   Siga o padrão de commits descrito acima, com commits pequenos e frequentes.

3. **Suba a branch**
   ```
   git push -u origin nome-da-sua-branch
   ```

4. **Abra o Pull Request**
   No GitHub, abra um PR da sua branch para a `develop`. Inclua:
   - Título claro e objetivo
   - Descrição do que foi feito e por quê
   - Prints ou vídeos, se for mudança visual

5. **Solicite revisão**
   Peça para outro membro do time revisar o código antes de aprovar.

6. **Merge e limpeza**
   Após aprovação, faça o merge do PR na `develop`. Delete a branch da feature após o merge.

## Estrutura de branches

- `main` → versões estáveis, prontas para produção
- `develop` → branch de integração do desenvolvimento
- `feature/*` → novas funcionalidades
- `fix/*` → correções de bugs
