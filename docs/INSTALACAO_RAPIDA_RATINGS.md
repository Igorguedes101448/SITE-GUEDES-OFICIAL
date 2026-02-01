# 🚀 Guia Rápido de Instalação - Sistema de Avaliações

## ⚡ Instalação em 3 Passos

### 1️⃣ Instalar Base de Dados
Abra no navegador:
```
http://localhost/SITE-GUEDES-OFICIAL-main/setup/install_ratings.php
```

Deverá ver:
```
✓ Tabela recipe_ratings criada com sucesso!
✓ Tabela recipe_comments criada com sucesso!
✓ INSTALAÇÃO CONCLUÍDA COM SUCESSO!
```

---

### 2️⃣ Adicionar às Páginas de Receitas

Abra o ficheiro da página onde mostra os detalhes da receita (ex: `explorar-receitas.html` ou `detalhes-receita.html`).

**Adicionar no `<head>`:**
```html
<!-- Font Awesome para as estrelas -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

**Adicionar antes do `</body>`:**
```html
<!-- Sistema de Ratings -->
<script src="/js/ratings.js"></script>
```

**Adicionar onde quer mostrar as avaliações:**
```html
<div id="ratings-container"></div>
```

---

### 3️⃣ Inicializar o Sistema

Adicione este código JavaScript na página:

```html
<script>
    // Obter ID da receita (ajustar conforme seu código)
    const recipeId = 1; // MUDAR para o ID real da receita
    
    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', () => {
        const ratingsUI = new RatingsUI(recipeId, 'ratings-container');
        ratingsUI.init();
    });
</script>
```

---

## 🎯 Exemplo Completo

```html
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Detalhes da Receita</title>
    <link rel="stylesheet" href="/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Conteúdo da receita -->
    <h1>Nome da Receita</h1>
    <p>Descrição...</p>
    
    <!-- Sistema de Avaliações e Comentários -->
    <div id="ratings-container"></div>
    
    <!-- Scripts -->
    <script src="/js/ratings.js"></script>
    <script>
        const recipeId = 1; // ID da receita atual
        document.addEventListener('DOMContentLoaded', () => {
            const ratingsUI = new RatingsUI(recipeId, 'ratings-container');
            ratingsUI.init();
        });
    </script>
</body>
</html>
```

---

## ✅ Funcionalidades

✨ **Avaliações:**
- Cada utilizador pode avaliar 1 vez (1-5 estrelas)
- Média calculada automaticamente
- Distribuição de estrelas visível

💬 **Comentários:**
- Até 2 comentários por utilizador por receita
- Mínimo 3 caracteres, máximo 1000
- Filtro de palavrões automático

🛡️ **Segurança:**
- Deteta e bloqueia palavrões
- Notifica utilizador de infrações
- Sistema de avisos progressivos

🔔 **Notificações:**
- Autor recebe notificação de novas avaliações
- Autor recebe notificação de novos comentários
- Avisos automáticos para infrações

---

## 🔧 Resolução de Problemas

### Problema: Estrelas não aparecem
**Solução:** Verificar se Font Awesome está carregado
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

### Problema: Erro 401 (Unauthorized)
**Solução:** Utilizador precisa estar logado. Verificar se `sessionToken` existe:
```javascript
const token = localStorage.getItem('sessionToken');
console.log('Token:', token); // Deve ter um valor
```

### Problema: Estilos não aplicados
**Solução:** Verificar se `styles.css` está incluído:
```html
<link rel="stylesheet" href="/css/styles.css">
```

### Problema: "Receita não encontrada"
**Solução:** Verificar se o `recipeId` está correto:
```javascript
console.log('Recipe ID:', recipeId); // Verificar valor
```

---

## 📱 Teste Rápido

1. **Abrir página da receita**
2. **Clicar numa estrela** → Deve registar avaliação
3. **Escrever comentário** → Deve aparecer na lista
4. **Tentar comentar com palavrão** → Deve ser bloqueado e receber notificação
5. **Verificar notificações** → Deve aparecer aviso de infração

---

## 📚 Documentação Completa

Para mais detalhes, consultar:
- [SISTEMA_RATINGS_COMENTARIOS.md](SISTEMA_RATINGS_COMENTARIOS.md) - Documentação completa
- [exemplo-ratings.html](../pages/exemplo-ratings.html) - Exemplo prático

---

## 🎉 Pronto!

O sistema está instalado e funcional! 

**Próximos passos:**
1. Testar avaliações
2. Testar comentários
3. Verificar filtro de profanidade
4. Personalizar mensagens (opcional)
5. Ajustar estilos CSS (opcional)

---

**Dúvidas?** Consultar a documentação completa ou verificar os ficheiros de exemplo.
