# CORREÇÃO: Botão de Receitas Privadas

## ❌ Problema Identificado
A receita "Bife de Vaca com Molho de Natas e Cogumelos" foi criada com:
- `visibility = 'private'` 
- `is_draft = 0` (PÚBLICO) ❌ INCORRETO

Isso aconteceu porque:
1. O formulário estava sempre enviando `isDraft: false` independentemente da escolha do utilizador
2. A opção padrão era "Pública" em vez de "Privada"

## ✅ Correções Aplicadas

### 1. Arquivo: pages/nova-receita.html
**Linha 304** - Corrigida lógica de isDraft:
```javascript
// ANTES
isDraft: false  // Sempre público

// DEPOIS  
isDraft: document.querySelector('input[name="visibility"]:checked').value === 'private'
// Agora: private = true (privado), public = false (público)
```

**Linhas 122-133** - Alterada opção padrão:
```html
<!-- ANTES: Pública por padrão -->
<input type="radio" name="visibility" value="public" checked>

<!-- DEPOIS: Privada por padrão -->
<input type="radio" name="visibility" value="private" checked>
```

### 2. Receitas Corrigidas no Banco de Dados
- ✅ Mojito (ID: 16) - agora PRIVADA
- ✅ Bife de Vaca com Molho de Natas e Cogumelos (ID: 18) - agora PRIVADA

### 3. Status Atual
**Utilizador: teste**
- Receitas públicas: 0
- Receitas privadas: 2 (Mojito e Bife)

## 🔒 Comportamento Atual

### Ao Criar Nova Receita:
1. **Opção padrão:** 🔒 Privada (apenas eu)
2. **Usuário pode escolher:** 
   - 🔒 Privada → `isDraft=true`, `visibility=private`
   - 🌐 Pública → `isDraft=false`, `visibility=public`

### API (recipes.php):
- Padrão se não enviado: `isDraft=true`, `visibility=private`

## ✅ Resultado
Todas as novas receitas serão criadas como **PRIVADAS** por padrão, evitando publicações acidentais!
