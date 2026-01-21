# 🛡️ Sistema de Administração - ChefGuedes

Sistema completo de moderação e gestão para o ChefGuedes.

## ⚡ Instalação Rápida

### 1️⃣ Atualizar Base de Dados
```sql
-- Execute no phpMyAdmin:
database/update_admin_system.sql
```

### 2️⃣ Criar Conta Admin
Acesse: `http://localhost/siteguedes/setup-admin.html`

### 3️⃣ Fazer Login
Acesse: `http://localhost/siteguedes/login.html`

## 📋 Funcionalidades

### 👥 Gestão de Utilizadores
- ⚠️ **Avisar** - Sistema de warnings
- ⏸️ **Suspender** - Bloqueio temporário (definir dias)
- 🚫 **Banir** - Bloqueio permanente
- ✅ **Reativar** - Remover suspensão ou banimento
- 🗑️ **Apagar** - Remoção definitiva

### 📖 Gestão de Receitas
- 🔍 Visualizar todas as receitas
- 🗑️ Apagar receitas inapropriadas
- 📊 Ver estatísticas

### 📊 Dashboard
- Total de utilizadores
- Utilizadores banidos/suspensos
- Total de receitas
- Ações realizadas hoje

## 🔒 Segurança

✅ Apenas admins podem aceder ao painel  
✅ Não é possível banir/apagar outros admins  
✅ Todas as ações ficam registadas  
✅ Histórico completo de moderação  

### ⚠️ Após Instalação

**APAGUE estes ficheiros por segurança:**
```bash
setup-admin.html
install_admin_new.php
ADMIN_INSTALACAO.txt
README_ADMIN.md
```

## 📁 Ficheiros Criados/Modificados

### Novos Ficheiros
- `database/update_admin_system.sql` - SQL de atualização
- `setup-admin.html` - Interface de instalação
- `install_admin_new.php` - Script de criação do admin
- `ADMIN_INSTALACAO.txt` - Guia completo
- `README_ADMIN.md` - Este ficheiro

### Ficheiros Modificados
- `api/admin.php` - API melhorada
- `js/admin.js` - JavaScript atualizado
- `pages/admin.html` - Interface atualizada
- `login.php` - Verificação de suspensão/banimento
- `api/users.php` - Verificação de suspensão/banimento

### Estrutura BD Atualizada
**Tabela `users` - Novas colunas:**
- `is_admin` - Identifica administradores
- `banned` - Utilizador banido (sim/não)
- `banned_reason` - Motivo do banimento
- `suspended_until` - Data fim da suspensão
- `warning_count` - Contador de avisos

**Nova Tabela `admin_actions`:**
Registo completo de todas as ações administrativas

**Nova Tabela `reports`:**
Sistema de denúncias (preparado para futuro)

## 🎯 Como Usar

### Exemplo: Banir Utilizador
1. Acesse painel admin
2. Aba "Utilizadores"
3. Clique "Banir" no utilizador desejado
4. Digite o motivo
5. Confirme

### Exemplo: Suspender por 7 dias
1. Clique "Suspender"
2. Digite: `7` (dias)
3. Digite o motivo
4. Confirme

### Exemplo: Dar Aviso
1. Clique "Avisar (X)" onde X é o número atual de avisos
2. Digite o motivo
3. O contador incrementa

## 🔧 Troubleshooting

### Não consigo aceder ao painel admin
- Verifique se executou o SQL
- Confirme que a conta tem `is_admin=1`
- Limpe cookies/cache do navegador

### Erro ao criar admin
- Execute o SQL primeiro
- Verifique se já existe um admin
- Veja logs de erro do PHP

### Botões não funcionam
- Limpe cache do navegador (Ctrl+Shift+Del)
- Abra Console (F12) para ver erros
- Verifique se `js/admin.js` está a carregar

## ✅ Checklist

- [ ] Executei `update_admin_system.sql`
- [ ] Criei conta admin
- [ ] Fiz login como admin
- [ ] Testei funcionalidades
- [ ] Apaguei ficheiros de instalação
- [ ] Mudei password padrão

## 📞 Verificação

Execute no MySQL para verificar:

```sql
-- Ver estrutura da tabela users
DESCRIBE users;

-- Ver tabelas de admin
SHOW TABLES LIKE 'admin%';

-- Ver conta admin
SELECT id, username, email, is_admin FROM users WHERE is_admin = 1;
```

---

**✨ Sistema instalado e pronto a usar!**
