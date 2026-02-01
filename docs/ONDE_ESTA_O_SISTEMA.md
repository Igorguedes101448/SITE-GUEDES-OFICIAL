# 🎯 ONDE ESTÁ O SISTEMA DE AVALIAÇÕES E COMENTÁRIOS

## ✅ IMPLEMENTADO EM: `pages/receita-detalhes.html`

O sistema de avaliações e comentários foi adicionado na **página de detalhes da receita**.

---

## 📍 COMO ACEDER

### 1. Abrir o site:
```
http://localhost/SITE-GUEDES-OFICIAL-main/index.html
```

### 2. Clicar em "Explorar Receitas"

### 3. Clicar em QUALQUER receita da lista

### 4. A página de detalhes abrirá com:
- 📋 Informações da receita
- 🍳 Ingredientes e modo de preparo
- ⭐ **SISTEMA DE AVALIAÇÕES** (estrelas)
- 💬 **SISTEMA DE COMENTÁRIOS**

---

## 🔍 ESTRUTURA VISUAL

```
┌─────────────────────────────────────────┐
│  [Título da Receita]                    │
│  Tempo | Dificuldade | Categoria        │
├─────────────────────────────────────────┤
│                                         │
│  [Imagem da Receita]                    │
│                                         │
├─────────────────────────────────────────┤
│  Descrição da receita...                │
├─────────────────────────────────────────┤
│  INGREDIENTES    │    MODO DE PREPARO   │
│  • Item 1        │    1. Passo 1        │
│  • Item 2        │    2. Passo 2        │
├─────────────────────────────────────────┤
│  [Voltar às Receitas]                   │
├─────────────────────────────────────────┤
│                                         │
│  ⭐ AVALIAÇÕES E COMENTÁRIOS ⭐          │
│  ┌───────────────────────────────────┐ │
│  │ 4.5 ⭐⭐⭐⭐⭐ (10 avaliações)       │ │
│  │                                   │ │
│  │ 5 ⭐ ████████████ 6                │ │
│  │ 4 ⭐ ██████       3                │ │
│  │ 3 ⭐ ██           1                │ │
│  │ 2 ⭐              0                │ │
│  │ 1 ⭐              0                │ │
│  └───────────────────────────────────┘ │
│                                         │
│  Avaliar esta receita                   │
│  ⭐ ⭐ ⭐ ⭐ ⭐  (clique para avaliar)   │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ Comentários (2)                   │ │
│  │                                   │ │
│  │ [Escreva seu comentário...]       │ │
│  │ [Enviar Comentário]               │ │
│  │                                   │ │
│  │ ┌─────────────────────────────┐   │ │
│  │ │ João Silva    ⭐⭐⭐⭐⭐       │   │ │
│  │ │ Há 2 horas                  │   │ │
│  │ │ Receita deliciosa!          │   │ │
│  │ └─────────────────────────────┘   │ │
│  └───────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## ⚠️ IMPORTANTE

### O sistema SÓ aparece para:
✅ Receitas da base de dados (criadas por utilizadores)
❌ Receitas portuguesas pré-definidas (IDs começam com 'rp')

### Razão:
As receitas portuguesas são exemplos fixos no código, não estão na base de dados. O sistema de avaliações precisa de um ID na base de dados para funcionar.

---

## 🧪 COMO TESTAR

1. **Fazer Login** (obrigatório)
   ```
   http://localhost/SITE-GUEDES-OFICIAL-main/login.html
   ```

2. **Criar uma Receita Nova**
   - Ir para "Explorar Receitas"
   - Clicar em "+ Nova Receita"
   - Preencher e guardar

3. **Abrir a Receita Criada**
   - Clicar na receita na lista
   - Ver página de detalhes

4. **Verás o Sistema Completo:**
   - ⭐ Estrelas para avaliar (1-5)
   - 💬 Campo para comentar
   - 📊 Estatísticas de avaliações
   - 📝 Lista de comentários

---

## ✨ FUNCIONALIDADES DISPONÍVEIS

### Avaliações
- Clica nas estrelas (1 a 5)
- Tua avaliação fica destacada
- Média atualiza automaticamente
- Gráfico de distribuição de estrelas

### Comentários
- Escreve comentário (3-1000 caracteres)
- Máximo 2 comentários por receita
- Contador de caracteres em tempo real
- Pode deletar teus comentários

### Filtro de Profanidade
- Tenta escrever palavrão → será bloqueado
- Receberás notificação de aviso
- Infração registada

---

## 🎯 NAVEGAÇÃO RÁPIDA

```
index.html
    ↓
Explorar Receitas (explorar-receitas.html)
    ↓
[Clicar numa receita]
    ↓
receita-detalhes.html?id=X  ← AQUI ESTÁ O SISTEMA!
```

---

## 📱 URLS DIRETAS

Para testar, criar primeiro uma receita e depois aceder:

```
http://localhost/SITE-GUEDES-OFICIAL-main/pages/receita-detalhes.html?id=1
http://localhost/SITE-GUEDES-OFICIAL-main/pages/receita-detalhes.html?id=2
http://localhost/SITE-GUEDES-OFICIAL-main/pages/receita-detalhes.html?id=3
```

(Substituir pelo ID real da receita na base de dados)

---

## 🔧 SE NÃO APARECER

1. **Verificar se está logado**
   - Sistema requer autenticação

2. **Verificar console do navegador (F12)**
   - Ver se há erros JavaScript

3. **Verificar se a receita é da BD**
   - URL deve ser: `?id=número`
   - NÃO: `?id=rp_xxxxx` (receitas portuguesas)

4. **Executar instalador da BD**
   ```
   http://localhost/SITE-GUEDES-OFICIAL-main/setup/install_ratings.php
   ```

---

## ✅ CHECKLIST RÁPIDO

- [ ] Base de dados instalada
- [ ] Fazer login no site
- [ ] Ir para "Explorar Receitas"
- [ ] Clicar numa receita
- [ ] Ver sistema de ratings no final da página
- [ ] Clicar nas estrelas para avaliar
- [ ] Escrever comentário
- [ ] Testar filtro de profanidade

---

**SISTEMA ESTÁ FUNCIONANDO!** 
Basta seguir os passos acima para ver tudo em ação! 🎉
