# Restrição: Apenas Administradores Podem Convidar Membros

## 🔒 Problema

Era necessário garantir que apenas o **dono/administrador** do grupo possa enviar convites para novos membros, impedindo que membros regulares (convidados) possam convidar outras pessoas.

---

## ✅ Solução Implementada

### 1. **Verificação na API** (Já Existia)

A API em [api/groups.php](api/groups.php) já tinha a verificação correta:

```php
// Verificar se o utilizador é admin do grupo
$stmt = $db->prepare("SELECT role FROM `group_members` WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $inviterId]);
$member = $stmt->fetch();

if (!$member || $member['role'] !== 'admin') {
    jsonError('Apenas administradores podem enviar convites.', 403);
}
```

Esta verificação está presente em:
- ✅ `send_invite` (linha 201)
- ✅ `add_member` (linha 504)

---

### 2. **Nova Função JavaScript** ([js/main-api.js](js/main-api.js))

Adicionada função `isGroupAdmin()` para verificar se o utilizador atual é administrador do grupo:

```javascript
// Verificar se o utilizador atual é admin do grupo
async function isGroupAdmin(groupId) {
    const currentUser = await getCurrentUser();
    if (!currentUser) return false;
    
    const members = await getGroupMembers(groupId);
    const userMember = members.find(m => m.user_id == currentUser.id);
    
    return userMember && userMember.role === 'admin';
}
```

**Como funciona:**
1. Busca o utilizador atual
2. Obtém todos os membros do grupo
3. Procura o utilizador na lista de membros
4. Retorna `true` se o role for 'admin', `false` caso contrário

---

### 3. **Interface Atualizada** ([pages/grupos.html](pages/grupos.html))

A função `loadMembers()` foi atualizada para:

**a) Verificar se o utilizador é admin:**
```javascript
const isAdmin = await isGroupAdmin(currentGroupId);
```

**b) Mostrar botão "Adicionar Membro" apenas para admins:**
```javascript
${isAdmin ? '<button class="btn btn-primary" onclick="openModal(\'modalAddMember\')">+ Adicionar Membro</button>' : ''}
```

**c) Mostrar ícones indicativos:**
- 👑 Admin
- 👤 Membro

**d) Exibir mensagem quando não é admin:**
```javascript
${!isAdmin ? '<p style="...">⚠️ Apenas administradores podem adicionar ou remover membros.</p>' : ''}
```

---

## 🎯 Comportamento

| Role | Botão Visível? | Pode Adicionar? | API Retorna |
|------|----------------|-----------------|-------------|
| **👑 Admin** | ✅ Sim | ✅ Sim | 200 - Sucesso |
| **👤 Membro** | ❌ Não | ❌ Não | 403 - Forbidden |
| **🚫 Não-Membro** | ❌ Não | ❌ Não | 403 - Forbidden |

---

## 🔐 Camadas de Proteção

### 1. **Camada de Interface (UX)**
- Botões escondidos para não-admins
- Mensagens claras sobre permissões
- Indicadores visuais de role (👑/👤)

### 2. **Camada de API (Backend)**
- Verificação de role na base de dados
- Retorna erro 403 se não for admin
- Mensagem: "Apenas administradores podem enviar convites"

### 3. **Camada de Validação**
- Verifica se o utilizador é membro do grupo
- Verifica se o role é 'admin'
- Valida sessionToken

---

## 🧪 Como Testar

### Teste 1: Como Administrador ✅

1. Login com conta que criou um grupo
2. Abrir [test-permissoes-grupo.html](test-permissoes-grupo.html)
3. Selecionar o grupo
4. **Resultado esperado:**
   - ✅ Aparece "👑 Você é ADMINISTRADOR"
   - ✅ Botão "Adicionar Membro" está visível
   - ✅ Consegue enviar convites
   - ✅ Consegue remover membros (exceto outros admins)

### Teste 2: Como Membro Regular ❌

1. Login com segunda conta
2. Aceitar convite de um grupo (como membro)
3. Abrir [test-permissoes-grupo.html](test-permissoes-grupo.html)
4. Selecionar o grupo onde é membro
5. **Resultado esperado:**
   - ✅ Aparece "👤 Você é MEMBRO"
   - ✅ Botão "Adicionar Membro" está **escondido**
   - ✅ Mensagem: "⚠️ Apenas administradores podem adicionar ou remover membros"
   - ✅ Se tentar via API direta, recebe erro 403

---

## 📊 Comparação Antes vs Depois

| Aspecto | ❌ Antes | ✅ Depois |
|---------|----------|-----------|
| **Botões na Interface** | Visíveis para todos | Apenas para admins |
| **Verificação Backend** | ✅ Já existia | ✅ Mantida |
| **Feedback Visual** | Não havia indicação | Ícones 👑/👤 e mensagens claras |
| **Função isGroupAdmin** | Não existia | ✅ Criada |
| **Experiência do Membro** | Confusa (botão não funcionava) | Clara (botão escondido + explicação) |

---

## 🔍 Arquivos Modificados

1. **`js/main-api.js`** - Adicionada função `isGroupAdmin()`
2. **`pages/grupos.html`** - `loadMembers()` atualizada para verificar permissões
3. **`test-permissoes-grupo.html`** - Página de teste criada

---

## 💡 Melhorias Implementadas

### Interface mais Intuitiva
- ✅ Ícones indicando role (👑 Admin / 👤 Membro)
- ✅ Botões escondidos quando sem permissão (em vez de mostrar erro)
- ✅ Mensagem explicativa para membros regulares

### Segurança em Camadas
- ✅ **Frontend:** Esconde opções não permitidas
- ✅ **Backend:** Valida permissões rigorosamente
- ✅ **Database:** Role armazenado na tabela `group_members`

### Feedback Claro
- ✅ "Você é ADMINISTRADOR" em verde
- ✅ "Você é MEMBRO" em azul
- ✅ Mensagem de aviso quando não tem permissão

---

## 🎨 Detalhes Visuais

### Badges de Role
```html
👑 Admin    → Background verde (#4caf50)
👤 Membro   → Background azul (#2196f3)
```

### Mensagem de Aviso (para membros)
```
⚠️ Apenas administradores podem adicionar ou remover membros.
```

### Status do Utilizador
```
👑 Você é ADMINISTRADOR  → Fundo verde
👤 Você é MEMBRO         → Fundo azul
```

---

## 🚀 Conclusão

Agora o sistema garante que:

1. ✅ **Apenas administradores** veem o botão "Adicionar Membro"
2. ✅ **Apenas administradores** conseguem enviar convites
3. ✅ **Membros regulares** veem mensagem clara sobre suas limitações
4. ✅ **API protegida** com verificação rigorosa de permissões
5. ✅ **Feedback visual** claro sobre role do utilizador

A experiência é mais intuitiva e segura, com proteção em todas as camadas do sistema.

---

**Data:** 15 de Janeiro de 2026  
**Autor:** GitHub Copilot
