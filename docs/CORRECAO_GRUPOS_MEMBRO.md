# Correção: Grupos Não Aparecem na Dashboard Após Aceitar Convite

## 🐛 Problema Identificado

Quando um utilizador aceitava um convite para um grupo, ele era corretamente adicionado à tabela `group_members` na base de dados, mas o grupo **não aparecia na dashboard** nem na página de grupos.

### Causa Raiz

O código estava filtrando grupos apenas pelo campo `created_by`, mostrando apenas grupos criados pelo utilizador, ignorando completamente os grupos onde ele foi convidado e aceitou o convite.

**Código problemático:**
```javascript
// ❌ ERRADO - Apenas grupos criados
const userGroups = allGroups.filter(g => g.created_by == currentUser?.id);
```

---

## ✅ Solução Implementada

### 1. **Novo Endpoint na API** (`api/groups.php`)

Criado endpoint `GET /api/groups.php?user_groups=true` que retorna **todos os grupos onde o utilizador é membro** (tanto criador quanto convidado):

```php
if ($method === 'GET' && isset($_GET['user_groups'])) {
    // Buscar todos os grupos onde o utilizador é membro
    $sql = "
        SELECT DISTINCT g.*, u.username as created_by_name,
               (SELECT COUNT(*) FROM `group_members` WHERE group_id = g.id) as member_count,
               gm.role as user_role
        FROM `groups` g
        LEFT JOIN users u ON g.created_by = u.id
        INNER JOIN `group_members` gm ON g.id = gm.group_id
        WHERE gm.user_id = ?
        ORDER BY g.created_at DESC
    ";
}
```

**Destaques:**
- ✅ Usa `INNER JOIN` com `group_members` para pegar apenas grupos onde o utilizador é membro
- ✅ Retorna o `role` do utilizador no grupo (admin ou member)
- ✅ Requer autenticação via `sessionToken`

---

### 2. **Nova Função JavaScript** (`js/main-api.js`)

Adicionada função `getUserGroups()` que chama o novo endpoint:

```javascript
async function getUserGroups() {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return [];
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php?user_groups=true&sessionToken=${sessionToken}`);
        const result = await response.json();
        
        if (result.success && result.data.groups) {
            return result.data.groups;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter grupos do utilizador:', error);
        return [];
    }
}
```

---

### 3. **Atualização de getUserStats** (`js/main-api.js`)

Modificada para usar `getUserGroups()` em vez de filtrar manualmente:

```javascript
// ✅ CORRETO - Todos os grupos do utilizador
async function getUserStats() {
    const currentUser = await getCurrentUser();
    
    if (!currentUser) {
        return { recipes: 0, groups: 0, favorites: 0 };
    }
    
    const recipes = await getAllRecipes();
    const userGroups = await getUserGroups(); // ← Usa o novo endpoint
    
    const userRecipes = recipes.filter(r => r.author_id == currentUser.id);
    
    return {
        recipes: userRecipes.length,
        groups: userGroups.length, // ← Agora conta corretamente
        favorites: currentUser.favorites ? currentUser.favorites.length : 0
    };
}
```

---

### 4. **Atualização do Dashboard** (`pages/dashboard.html`)

Função `loadMyGroups()` atualizada para usar `getUserGroups()`:

```javascript
async function loadMyGroups() {
    const userGroups = await getUserGroups(); // ← Usa o novo endpoint
    
    const container = document.getElementById('myGroups');
    
    if (userGroups.length === 0) {
        container.innerHTML = '<p>Nenhum grupo criado ainda. <a href="grupos.html">Criar primeiro grupo</a></p>';
        return;
    }
    
    container.innerHTML = userGroups.map(group => `
        <div class="activity-item">
            <strong><a href="grupos.html?id=${group.id}">${group.name}</a></strong>
            <p>
                ${group.member_count || 0} membros • 
                ${group.user_role === 'admin' ? 'Administrador' : 'Membro'} • ← Mostra o role
                Criado em ${formatDate(group.created_at)}
            </p>
        </div>
    `).join('');
}
```

**Melhorias:**
- ✅ Mostra **todos** os grupos do utilizador (criados ou convidados)
- ✅ Indica se o utilizador é **Administrador** ou **Membro** do grupo
- ✅ Mensagem mais amigável quando não há grupos

---

### 5. **Atualização da Página de Grupos** (`pages/grupos.html`)

Duas ocorrências corrigidas:

**a) Verificação de tab de agendamento:**
```javascript
// ✅ ANTES
const groups = await getAllGroups();
const userGroups = groups.filter(g => g.created_by === currentUser?.id);

// ✅ DEPOIS
const userGroups = await getUserGroups();
```

**b) Função loadGroups:**
```javascript
async function loadGroups() {
    const userGroups = await getUserGroups(); // ← Usa o novo endpoint
    
    const container = document.getElementById('groupsList');
    
    if (userGroups.length === 0) {
        container.innerHTML = '<p>Ainda não pertence a nenhum grupo. Clique em "Criar Novo Grupo" ou aguarde um convite.</p>';
        return;
    }
    
    container.innerHTML = userGroups.map(group => `
        <div class="dashboard-card" onclick="selectGroup('${group.id}')">
            <h3>${group.name}</h3>
            <p>${group.description || 'Sem descrição'}</p>
            <p>${group.member_count || 0} membros • ${group.user_role === 'admin' ? 'Administrador' : 'Membro'}</p>
        </div>
    `).join('');
}
```

---

## 🧪 Como Testar

### Teste Manual:

1. **Abrir:** `test-grupo-membro.html` no navegador
2. **Fazer login** com uma conta
3. **Testar funções:**
   - "Buscar Todos os Grupos" → Deve mostrar TODOS os grupos do sistema
   - "Buscar Meus Grupos" → Deve mostrar apenas grupos onde você é membro
   - "Buscar Estatísticas" → Deve contar corretamente

### Teste de Convite (Cenário Real):

1. **Login com Conta A** → Criar um grupo "Grupo Teste"
2. **Obter código** de Conta B em Perfil
3. **Conta A:** Enviar convite para Conta B
4. **Login com Conta B** → Ver convite pendente na dashboard
5. **Aceitar convite** → Grupo deve aparecer imediatamente em:
   - ✅ Dashboard (seção "Meus Grupos")
   - ✅ Página Grupos
   - ✅ Estatística "Meus Grupos" deve aumentar

---

## 📊 Comparação Antes vs Depois

| Aspecto | ❌ Antes | ✅ Depois |
|---------|----------|-----------|
| **Grupos na Dashboard** | Apenas grupos criados | Todos os grupos (criador + membro) |
| **Contagem de Grupos** | Incorreta (apenas criados) | Correta (todos) |
| **Indicador de Role** | Não existia | Mostra "Administrador" ou "Membro" |
| **Após aceitar convite** | Nada aparece | Grupo aparece imediatamente |
| **Mensagem quando vazio** | "Nenhum grupo criado" | "Não pertence a nenhum grupo" |

---

## 🔍 Arquivos Modificados

1. **`api/groups.php`** - Novo endpoint `user_groups`
2. **`js/main-api.js`** - Nova função `getUserGroups()` e `getUserStats()` atualizada
3. **`pages/dashboard.html`** - `loadMyGroups()` atualizada
4. **`pages/grupos.html`** - `loadGroups()` e verificação de tab atualizadas
5. **`test-grupo-membro.html`** - Página de teste criada

---

## ✨ Benefícios

- ✅ **Corrige bug crítico:** Grupos aparecem após aceitar convite
- ✅ **Melhora UX:** Utilizador vê imediatamente que faz parte do grupo
- ✅ **Indicadores visuais:** Mostra role do utilizador (Admin/Membro)
- ✅ **Estatísticas precisas:** Conta todos os grupos corretamente
- ✅ **Consistência:** Mesma lógica em dashboard e página de grupos

---

## 🚀 Conclusão

O problema foi **completamente resolvido**. Agora, quando um utilizador aceita um convite:

1. ✅ É adicionado a `group_members` (já funcionava)
2. ✅ O grupo aparece na dashboard imediatamente
3. ✅ O grupo aparece na página de grupos
4. ✅ As estatísticas são atualizadas corretamente
5. ✅ O utilizador vê seu role no grupo (Admin ou Membro)

**Data:** 15 de Janeiro de 2026
**Autor:** GitHub Copilot
