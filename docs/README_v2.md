# ChefGuedes 2.0 - Plataforma de Culinária 👨‍🍳

Sistema completo de gestão de receitas com grupos, agendamento e assistente IA.

## 🚀 Funcionalidades Principais

### ✅ Sistema de Autenticação
- Registo e login seguro
- Gestão de sessões com tokens
- Perfis de utilizador personalizáveis
- Código único de utilizador para convites

### 👥 Sistema de Grupos
- **Criar grupos** de culinária
- **Convites com confirmação** - Não adiciona automaticamente
- **Aceitar/Recusar convites** através de notificações
- Gestão de membros (admin/membro)
- Agendamento de refeições em grupo

### 📅 Sistema de Agendamento
- **Agenda pessoal** de refeições
- **Agenda de grupo** compartilhada
- Planeamento por dia, semana e mês
- Tipos de refeição: Pequeno-almoço, Almoço, Jantar, Lanche
- Notificações automáticas para membros do grupo

### 🤖 Assistente IA Culinário
- **Sugestões de receitas** baseadas em:
  - Tempo disponível
  - Número de pessoas
  - Dificuldade
  - Preferências do utilizador
- **Plano semanal automático** de refeições
- **Melhorias para receitas** existentes
- Sugestões contextualizadas e personalizadas

### 🔔 Sistema de Notificações
- Convites de grupo
- Respostas a convites (aceite/recusado)
- Lembretes de refeições agendadas
- Notificações do sistema
- Contador de não lidas

### 📖 Gestão de Receitas
- Criar, editar e eliminar receitas
- Categorias e subcategorias
- Níveis de dificuldade
- Tempos de preparação e confecção
- Imagens em base64
- Visibilidade (pública/privada)
- Rascunhos

### 📊 Dashboard Completa
- Estatísticas do utilizador
- Resumo de atividades
- Receitas recentes
- Grupos ativos
- **Convites pendentes** (NOVO)
- Próximas refeições agendadas

## 📋 Requisitos

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx com mod_rewrite
- Extensão PDO MySQL

## 🔧 Instalação

### 1. Configurar Base de Dados

Criar base de dados e importar estrutura:

```sql
-- Opção A: Instalação completa (NOVO sistema)
mysql -u root -p < database/schema.sql

-- Opção B: Migração de v1.0 para v2.0 (sistema existente)
mysql -u root -p siteguedes < database/migrate_to_v2.sql
```

### 2. Configurar Conexão

Editar `api/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'siteguedes');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Configurar Servidor

**Apache (.htaccess já incluído)**

**Nginx:**
```nginx
location /api/ {
    try_files $uri $uri/ /api/index.php?$query_string;
}
```

### 4. Aceder ao Site

Abrir no navegador:
```
http://localhost/siteguedes/
```

## 📱 Estrutura do Projeto

```
siteguedes/
├── api/                    # APIs PHP
│   ├── db.php             # Conexão BD
│   ├── users.php          # Utilizadores
│   ├── recipes.php        # Receitas
│   ├── groups.php         # Grupos (com convites)
│   ├── schedules.php      # Agendamentos (NOVO)
│   ├── notifications.php  # Notificações (NOVO)
│   └── ai.php             # Assistente IA (NOVO)
├── css/
│   └── styles.css         # Estilos completos
├── js/
│   ├── auth-api.js        # Autenticação
│   └── main-api.js        # Funções principais
├── pages/                 # Páginas HTML
│   ├── dashboard.html     # Dashboard (MELHORADA)
│   ├── grupos.html        # Gestão de grupos
│   ├── perfil.html        # Perfil do utilizador
│   └── ...
├── database/              # Scripts SQL
│   ├── schema.sql         # Estrutura completa
│   └── migrate_to_v2.sql  # Migração v1→v2 (NOVO)
└── images/                # Imagens
```

## 🎯 Como Usar

### Criar Conta e Fazer Login
1. Aceder a `/login.html`
2. Clicar em "Criar conta"
3. Preencher dados (username, email, password)
4. Receber código de utilizador único (6 caracteres)

### Criar e Gerir Grupos

#### Criar Grupo:
1. Dashboard → "Novo Grupo"
2. Preencher nome e descrição
3. Grupo criado (você é admin)

#### Convidar Membros:
1. Abrir grupo em "Grupos"
2. Clicar "Adicionar Membro"
3. Inserir código do utilizador (6 caracteres)
4. **Convite enviado** (não adiciona automaticamente)

#### Aceitar/Recusar Convites:
1. Dashboard → Secção "Convites Pendentes"
2. Ver detalhes do grupo
3. Clicar ✓ (aceitar) ou ✗ (recusar)
4. Notificação enviada ao admin

### Agendar Refeições

#### Agenda Pessoal:
1. Dashboard → "Agendar refeição"
2. Escolher data, hora, tipo de refeição
3. Selecionar receita (opcional)
4. Guardar

#### Agenda de Grupo:
1. Grupos → Selecionar grupo
2. Tab "Agendamento Semanal"
3. Clicar em dia da semana
4. Preencher detalhes
5. **Todos os membros são notificados**

### Usar Assistente IA

#### Sugerir Receitas:
```javascript
const result = await getRecipeSuggestions({
    prep_time: 30,
    servings: 4,
    difficulty: 'Fácil'
});
```

#### Gerar Plano Semanal:
```javascript
const plan = await getWeeklyPlan('2025-01-13', 2, 2);
// 2 pessoas, 2 refeições por dia
```

#### Melhorar Receita:
```javascript
const tips = await getRecipeImprovements(recipeId);
```

## 🔒 Segurança

- ✅ Passwords com `password_hash()` e `password_verify()`
- ✅ Prepared statements (SQL injection protection)
- ✅ Validação de inputs
- ✅ Filtro de profanidade
- ✅ Tokens de sessão seguros
- ✅ Verificação de permissões (admin/membro)
- ✅ CORS configurado

## 🐛 Resolução de Problemas

### Erro: "Sessão inválida"
- Fazer logout e login novamente
- Verificar se cookies estão ativados

### Convites não aparecem
- Verificar se tabela `group_invites` existe
- Executar migração: `migrate_to_v2.sql`

### Agendamentos não funcionam
- Verificar se API `schedules.php` existe
- Confirmar se coluna `group_id` foi adicionada

### Notificações não aparecem
- Verificar se tabela `notifications` existe
- Limpar cache do navegador

## 📊 Base de Dados

### Tabelas Principais:
- `users` - Utilizadores
- `sessions` - Sessões ativas
- `recipes` - Receitas
- `groups` - Grupos
- `group_members` - Membros de grupos
- `group_invites` - Convites de grupo (NOVO)
- `schedules` - Agendamentos (MELHORADO)
- `notifications` - Notificações (NOVO)
- `ai_suggestions` - Sugestões IA (NOVO)
- `activities` - Histórico de atividades

## 🎨 Personalização

### Tema Claro/Escuro
O site suporta automaticamente tema escuro. Alterar em:
```javascript
toggleTheme(); // Alterna entre claro/escuro
```

### Cores Principais
Editar em `css/styles.css`:
```css
:root {
    --primary-color: #ff6b35;
    --secondary-color: #8B4513;
    --accent-color: #f7b32b;
}
```

## 📞 Suporte

Para questões ou problemas:
1. Verificar este README
2. Consultar código fonte comentado
3. Verificar console do navegador (F12)
4. Verificar logs do PHP

## 🔄 Atualizações Futuras

- [ ] Sistema de amizades
- [ ] Comentários em receitas
- [ ] Avaliações de receitas
- [ ] Integração com APIs de nutrição
- [ ] App mobile
- [ ] Modo offline
- [ ] Exportação de receitas em PDF
- [ ] Lista de compras automática

## 📜 Licença

© 2025 ChefGuedes. Todos os direitos reservados.

---

**Versão:** 2.0.0  
**Data:** Janeiro 2025  
**Status:** ✅ Produção
