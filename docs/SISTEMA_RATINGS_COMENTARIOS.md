# Sistema de Avaliações e Comentários - ChefGuedes

## 📋 Índice
1. [Visão Geral](#visão-geral)
2. [Funcionalidades](#funcionalidades)
3. [Instalação](#instalação)
4. [Estrutura da Base de Dados](#estrutura-da-base-de-dados)
5. [API Endpoints](#api-endpoints)
6. [Integração Frontend](#integração-frontend)
7. [Sistema de Profanidade](#sistema-de-profanidade)
8. [Notificações e Infrações](#notificações-e-infrações)

---

## 🎯 Visão Geral

Sistema completo de avaliações por estrelas (1-5) e comentários para receitas, com:
- ⭐ Avaliação por estrelas (cada utilizador pode avaliar 1 vez)
- 💬 Sistema de comentários (máximo 2 comentários por utilizador por receita)
- 🛡️ Filtro de profanidade automático
- 🚨 Sistema de infrações com notificações
- 📊 Cálculo automático de médias de avaliação
- 🔔 Notificações para autores de receitas

---

## ✨ Funcionalidades

### Avaliações
- ✅ Avaliação de 1 a 5 estrelas
- ✅ Cada utilizador pode avaliar apenas 1 vez (pode atualizar)
- ✅ Média calculada automaticamente via triggers SQL
- ✅ Distribuição de estrelas (quantas pessoas deram 5, 4, 3, 2, 1 estrela)
- ✅ Notificação para o autor da receita quando recebe avaliação

### Comentários
- ✅ Máximo de 2 comentários por utilizador por receita
- ✅ Limite de 1000 caracteres por comentário
- ✅ Mínimo de 3 caracteres
- ✅ Filtro de profanidade integrado
- ✅ Utilizador pode deletar seus próprios comentários
- ✅ Administradores podem deletar qualquer comentário
- ✅ Exibição da avaliação do utilizador junto ao comentário

### Sistema de Profanidade
- ✅ Lista extensa de palavras proibidas (português e inglês)
- ✅ Detecção automática de variações (caracteres especiais)
- ✅ Registro de infrações na base de dados
- ✅ Notificações automáticas de aviso
- ✅ Aviso progressivo (1ª infração: alerta, 2ª+: aviso severo de banimento)

---

## 🚀 Instalação

### Passo 1: Executar Script de Instalação

Execute o instalador para criar todas as tabelas, índices e triggers:

```bash
# Via navegador
http://localhost/SITE-GUEDES-OFICIAL-main/setup/install_ratings.php

# Via linha de comando
php setup/install_ratings.php
```

### Passo 2: Verificar Instalação

O script criará:
- ✅ Tabela `recipe_ratings`
- ✅ Tabela `recipe_comments`
- ✅ Tabela `user_infractions`
- ✅ Colunas `average_rating` e `total_ratings` na tabela `recipes`
- ✅ Índices para performance
- ✅ 3 Triggers para cálculo automático de médias

### Passo 3: Integrar no Frontend

Adicione ao HTML da sua página de receitas:

```html
<!-- Font Awesome (se ainda não tiver) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Script de ratings -->
<script src="/js/ratings.js"></script>

<!-- Container onde o sistema será renderizado -->
<div id="ratings-container"></div>

<script>
    // Inicializar (substitua 1 pelo ID real da receita)
    const recipeId = 1;
    const ratingsUI = new RatingsUI(recipeId, 'ratings-container');
    ratingsUI.init();
</script>
```

---

## 🗄️ Estrutura da Base de Dados

### Tabela: `recipe_ratings`
```sql
CREATE TABLE recipe_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_recipe_rating (user_id, recipe_id)
);
```

### Tabela: `recipe_comments`
```sql
CREATE TABLE recipe_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tabela: `user_infractions`
```sql
CREATE TABLE user_infractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    infraction_type ENUM('profanity_comment', 'profanity_recipe', 'spam', 'harassment', 'other'),
    infraction_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Colunas Adicionadas à Tabela `recipes`
```sql
ALTER TABLE recipes 
ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN total_ratings INT DEFAULT 0;
```

---

## 🔌 API Endpoints

### Base URL: `/api/ratings.php`

### 1. Obter Avaliações e Comentários
```http
GET /api/ratings.php?recipe_id={id}
Authorization: Bearer {token} (opcional, mas recomendado)
```

**Resposta:**
```json
{
    "success": true,
    "message": "Avaliações e comentários carregados.",
    "stats": {
        "average_rating": 4.5,
        "total_ratings": 10,
        "five_stars": 6,
        "four_stars": 3,
        "three_stars": 1,
        "two_stars": 0,
        "one_star": 0
    },
    "comments": [...],
    "user_rating": 5,
    "user_comment_count": 1
}
```

### 2. Adicionar/Atualizar Avaliação
```http
POST /api/ratings.php
Authorization: Bearer {token}
Content-Type: application/json

{
    "action": "rate",
    "recipe_id": 1,
    "rating": 5
}
```

**Resposta:**
```json
{
    "success": true,
    "message": "Avaliação registada com sucesso!"
}
```

### 3. Adicionar Comentário
```http
POST /api/ratings.php
Authorization: Bearer {token}
Content-Type: application/json

{
    "action": "comment",
    "recipe_id": 1,
    "comment": "Receita deliciosa!"
}
```

**Resposta (Sucesso):**
```json
{
    "success": true,
    "message": "Comentário adicionado com sucesso!"
}
```

**Resposta (Profanidade Detectada):**
```json
{
    "success": false,
    "message": "O seu comentário contém linguagem inadequada e foi rejeitado. Uma notificação de aviso foi enviada..."
}
```

### 4. Deletar Comentário
```http
POST /api/ratings.php
Authorization: Bearer {token}
Content-Type: application/json

{
    "action": "delete_comment",
    "comment_id": 123
}
```

### 5. Obter Infrações do Utilizador
```http
GET /api/ratings.php?user_infractions=true
Authorization: Bearer {token}
```

---

## 🎨 Integração Frontend

### Exemplo Completo

```javascript
// Importar a classe (já incluída em ratings.js)
const ratingsUI = new RatingsUI(recipeId, 'ratings-container');

// Inicializar
await ratingsUI.init();

// O sistema automaticamente:
// - Carrega todas as avaliações e comentários
// - Renderiza a interface
// - Configura todos os event listeners
// - Gerencia submissões e atualizações
```

### Customização de Mensagens

```javascript
class CustomRatingsUI extends RatingsUI {
    showSuccess(message) {
        // Implementar seu próprio sistema de notificações
        // Ex: Toast, Modal, etc.
        console.log('Success:', message);
    }
    
    showError(message) {
        // Implementar seu próprio sistema de erros
        console.error('Error:', message);
    }
}

const ratingsUI = new CustomRatingsUI(recipeId, 'ratings-container');
```

---

## 🛡️ Sistema de Profanidade

### Como Funciona

1. **Verificação Automática**: Todo comentário é verificado antes de ser guardado
2. **Lista Extensa**: Inclui palavrões em português e inglês
3. **Detecção Inteligente**: Detecta variações com caracteres especiais (p0rra, f*ck, etc.)
4. **Registro**: Todas as tentativas são registadas na tabela `user_infractions`

### Adicionar Novas Palavras

Editar o arquivo `/api/profanity-filter.php`:

```php
function getProfanityList() {
    return [
        // Adicionar novas palavras aqui
        'nova_palavra',
        'outra_palavra',
        // ...
    ];
}
```

---

## 🚨 Notificações e Infrações

### Sistema de Infrações

- **1ª Infração**: Alerta básico
- **2ª+ Infrações**: Aviso severo de possível banimento

### Notificação Automática

Quando detectado profanidade, o sistema:
1. Rejeita o comentário
2. Registra a infração em `user_infractions`
3. Cria notificação automática para o utilizador
4. Envia mensagem personalizada baseada no número de infrações

### Consultar Infrações

```javascript
const api = new RatingsAPI();
const infractions = await api.getUserInfractions();
console.log(infractions);
```

---

## 📱 Responsividade

O sistema é totalmente responsivo:
- ✅ Desktop (1200px+)
- ✅ Tablet (768px - 1199px)
- ✅ Mobile (< 768px)

---

## 🔒 Segurança

- ✅ Autenticação via Bearer Token
- ✅ Validação de sessão em todos os endpoints
- ✅ Prepared Statements (PDO) para prevenir SQL Injection
- ✅ Escape de HTML para prevenir XSS
- ✅ Validação de input (comprimento, caracteres, etc.)
- ✅ Limitação de ações (1 avaliação, 2 comentários por utilizador)
- ✅ Filtro de profanidade integrado

---

## 🎯 Próximos Passos Recomendados

1. **Executar o instalador** para criar as tabelas
2. **Testar a API** com Postman ou similar
3. **Integrar em páginas existentes** como explorar-receitas.html
4. **Customizar o CSS** se necessário (em styles.css)
5. **Configurar notificações** toast/modal personalizadas

---

## 📞 Suporte

Para questões ou problemas:
1. Verificar os logs do PHP (`error_log`)
2. Verificar console do navegador para erros JavaScript
3. Consultar a tabela `user_infractions` para debug de filtro de profanidade

---

## ✅ Checklist de Implementação

- [ ] Executar `install_ratings.php`
- [ ] Verificar criação das tabelas
- [ ] Adicionar Font Awesome ao HTML
- [ ] Incluir `ratings.js` nas páginas
- [ ] Adicionar container `<div id="ratings-container"></div>`
- [ ] Inicializar RatingsUI com ID da receita
- [ ] Testar avaliação por estrelas
- [ ] Testar comentários
- [ ] Testar filtro de profanidade
- [ ] Verificar notificações
- [ ] Testar responsividade

---

**Desenvolvido para ChefGuedes** 🍳
**Versão:** 1.0.0
**Data:** 2026
