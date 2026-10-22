# Servidor MCP do Linear

O servidor MCP do Linear permite que o agente (Claude Code ou OpenCode) leia e altere os issues do workspace **GER-PROJ** direto do terminal: listar tarefas, criar issues e mudar o status.

- **Tipo:** HTTP (remoto, mantido pelo Linear)
- **URL:** `https://mcp.linear.app/mcp`
- **Autenticação:** OAuth pelo navegador, com a sua conta do Linear

Antes de começar, você precisa de uma conta no Linear com acesso ao workspace GER-PROJ.

## Claude Code

### 1. Adicionar o servidor

Na raiz do projeto, rode:

```bash
claude mcp add --transport http linear-server https://mcp.linear.app/mcp
```

Esse comando usa o escopo `local`: a configuração fica só na sua máquina, em `~/.claude.json`, e não entra no git. Para compartilhar com a equipe, use `--scope project`, que grava o servidor no `.mcp.json` da raiz.

### 2. Autenticar

Abra o Claude Code no projeto e digite:

```
/mcp
```

Selecione `linear-server` e escolha **Authenticate**. O navegador abre a página do Linear: faça login e autorize o acesso. O token fica salvo e não é preciso repetir esse passo.

### 3. Conferir

```bash
claude mcp get linear-server
```

O resultado deve mostrar `Status: ✔ Connected`. Depois, peça ao agente algo como *"liste os issues do Linear"* para testar.

## OpenCode

Adicione o servidor no `opencode.json`, dentro do bloco `mcp`:

```json
"linear": {
    "type": "remote",
    "url": "https://mcp.linear.app/mcp",
    "enabled": true
}
```

Na primeira vez que o servidor for usado, o OpenCode abre o navegador para o login. Também dá para autenticar antes com `opencode mcp auth linear`.

## Remover

```bash
claude mcp remove linear-server -s local
```

## Limitações

O MCP do Linear **não** consegue:

- excluir issues (no máximo, mudar o status para *Canceled*);
- criar ou renomear status do fluxo de trabalho.

Essas ações precisam ser feitas na interface do Linear.
