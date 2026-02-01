# ✅ CHECKLIST DE IMPLEMENTAÇÃO - Sistema de Avaliações

Siga estes passos para implementar o sistema completo no seu site.

---

## 📋 FASE 1: INSTALAÇÃO DA BASE DE DADOS

### Passo 1.1: Executar Instalador
- [ ] Abrir navegador
- [ ] Ir para: `http://localhost/SITE-GUEDES-OFICIAL-main/setup/install_ratings.php`
- [ ] Aguardar mensagem: "✓ INSTALAÇÃO CONCLUÍDA COM SUCESSO!"

### Passo 1.2: Verificar Instalação
- [ ] Abrir: `http://localhost/SITE-GUEDES-OFICIAL-main/tests/test_ratings_system.php`
- [ ] Confirmar: "✓ TODOS OS TESTES PASSARAM!"

### ✅ Resultado Esperado
```
✓ Tabela recipe_ratings criada
✓ Tabela recipe_comments criada
✓ Tabela user_infractions criada
✓ Colunas average_rating e total_ratings adicionadas
✓ 3 Triggers configurados
✓ Índices criados
```

---

## 📋 FASE 2: TESTAR COM EXEMPLO

### Passo 2.1: Abrir Exemplo
- [ ] Ir para: `http://localhost/SITE-GUEDES-OFICIAL-main/pages/exemplo-ratings.html`
- [ ] Verificar se a interface carrega corretamente

### Passo 2.2: Testar Funcionalidades
- [ ] Clicar numa estrela (1-5)
- [ ] Verificar se a avaliação é registada
- [ ] Escrever um comentário válido
- [ ] Verificar se o comentário aparece
- [ ] Tentar escrever palavrão → deve ser bloqueado
- [ ] Verificar notificação de infração
- [ ] Tentar adicionar 3º comentário → deve ser bloqueado

### ✅ Resultado Esperado
- Interface bonita com estrelas douradas
- Avaliações funcionando
- Comentários aparecendo
- Filtro de profanidade ativo
- Notificações funcionando

---

## 📋 FASE 3: INTEGRAR EM PÁGINAS EXISTENTES

### Passo 3.1: Identificar Página de Receitas
- [ ] Localizar ficheiro onde mostra detalhes da receita
  - Exemplo: `pages/explorar-receitas.html`
  - Ou: `pages/detalhes-receita.html`

### Passo 3.2: Adicionar Font Awesome
- [ ] Abrir o ficheiro identificado
- [ ] No `<head>`, adicionar:
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```
- [ ] Guardar ficheiro

### Passo 3.3: Incluir Script de Ratings
- [ ] No mesmo ficheiro, antes do `</body>`, adicionar:
```html
<script src="/js/ratings.js"></script>
```
- [ ] Guardar ficheiro

### Passo 3.4: Adicionar Container
- [ ] No HTML, onde quer mostrar as avaliações, adicionar:
```html
<div id="ratings-container"></div>
```
- [ ] Normalmente após os detalhes da receita
- [ ] Guardar ficheiro

### Passo 3.5: Inicializar Sistema
- [ ] No final do HTML (antes do `</body>`), adicionar:
```html
<script>
    // IMPORTANTE: Ajustar para obter o ID real da receita
    const recipeId = 1; // MUDAR para o ID correto
    
    document.addEventListener('DOMContentLoaded', () => {
        const ratingsUI = new RatingsUI(recipeId, 'ratings-container');
        ratingsUI.init();
    });
</script>
```
- [ ] **IMPORTANTE**: Ajustar `recipeId` para obter o ID real da receita
- [ ] Guardar ficheiro

### ✅ Resultado Esperado
```html
<head>
    <!-- Outros links... -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/.../all.min.css">
</head>
<body>
    <!-- Conteúdo da receita... -->
    
    <div id="ratings-container"></div>
    
    <script src="/js/ratings.js"></script>
    <script>
        const recipeId = 1;
        document.addEventListener('DOMContentLoaded', () => {
            const ratingsUI = new RatingsUI(recipeId, 'ratings-container');
            ratingsUI.init();
        });
    </script>
</body>
```

---

## 📋 FASE 4: AJUSTAR ID DA RECEITA

### Passo 4.1: Identificar Como Obtém ID
Dependendo da estrutura do site, o ID pode vir de:
- [ ] URL: `?recipe_id=123`
- [ ] Variável JavaScript existente
- [ ] Data attribute: `<div data-recipe-id="123">`
- [ ] Objeto global

### Passo 4.2: Exemplos de Código

**Se vier da URL:**
```javascript
// Obter de ?recipe_id=123
const urlParams = new URLSearchParams(window.location.search);
const recipeId = parseInt(urlParams.get('recipe_id'));
```

**Se vier de data attribute:**
```javascript
// Obter de <div data-recipe-id="123">
const recipeId = parseInt(document.querySelector('[data-recipe-id]').dataset.recipeId);
```

**Se vier de variável global:**
```javascript
// Se já existe currentRecipe.id
const recipeId = currentRecipe.id;
```

- [ ] Ajustar código para obter ID correto
- [ ] Testar se recipeId está correto: `console.log('Recipe ID:', recipeId)`

---

## 📋 FASE 5: TESTES FINAIS

### Passo 5.1: Teste Básico
- [ ] Abrir página da receita no navegador
- [ ] Verificar se interface de ratings carrega
- [ ] Verificar se estrelas aparecem corretamente
- [ ] Verificar se campo de comentário aparece

### Passo 5.2: Teste de Avaliação
- [ ] Fazer login no site
- [ ] Clicar numa estrela (1-5)
- [ ] Verificar mensagem de sucesso
- [ ] Atualizar página
- [ ] Verificar se avaliação permanece

### Passo 5.3: Teste de Comentário
- [ ] Escrever comentário válido (mínimo 3 caracteres)
- [ ] Clicar "Enviar Comentário"
- [ ] Verificar se aparece na lista
- [ ] Verificar se mostra seu nome e avatar
- [ ] Verificar se pode deletar (botão de lixeira)

### Passo 5.4: Teste de Profanidade
- [ ] Tentar comentar com palavrão
- [ ] Verificar se é bloqueado
- [ ] Verificar notificação de infração
- [ ] Ir para área de notificações
- [ ] Confirmar que recebeu aviso

### Passo 5.5: Teste de Limites
- [ ] Adicionar 1º comentário
- [ ] Adicionar 2º comentário
- [ ] Tentar adicionar 3º comentário
- [ ] Verificar mensagem: "Limite atingido"
- [ ] Tentar avaliar 2 vezes (deve apenas atualizar)

### Passo 5.6: Teste Responsivo
- [ ] Abrir DevTools (F12)
- [ ] Testar em Desktop (>1200px)
- [ ] Testar em Tablet (768-1199px)
- [ ] Testar em Mobile (<768px)
- [ ] Verificar se tudo funciona bem

### Passo 5.7: Teste de Notificações
- [ ] Com conta A, avaliar receita de conta B
- [ ] Login com conta B
- [ ] Verificar notificação de avaliação
- [ ] Com conta A, comentar em receita de conta B
- [ ] Login com conta B
- [ ] Verificar notificação de comentário

---

## 📋 FASE 6: RESOLUÇÃO DE PROBLEMAS

### Problema: Estrelas não aparecem
- [ ] Verificar se Font Awesome está carregado
- [ ] Abrir DevTools → Console
- [ ] Procurar erros de carregamento
- [ ] Verificar URL do Font Awesome

### Problema: Erro 401 (Unauthorized)
- [ ] Verificar se utilizador está logado
- [ ] Console: `localStorage.getItem('sessionToken')`
- [ ] Deve retornar um token
- [ ] Se não, fazer login primeiro

### Problema: Interface não carrega
- [ ] Verificar se ratings.js está incluído
- [ ] Verificar se recipeId é válido
- [ ] Console: `console.log('Recipe ID:', recipeId)`
- [ ] Verificar erros no console

### Problema: Estilos estranhos
- [ ] Verificar se styles.css está incluído
- [ ] Limpar cache do navegador (Ctrl+F5)
- [ ] Verificar se há conflitos de CSS

### Problema: "Receita não encontrada"
- [ ] Verificar se recipeId é um número válido
- [ ] Verificar se a receita existe na BD
- [ ] SQL: `SELECT * FROM recipes WHERE id = ?`

---

## 📋 FASE 7: PERSONALIZAÇÃO (OPCIONAL)

### Opção 1: Alterar Cores das Estrelas
- [ ] Abrir: `css/styles.css`
- [ ] Procurar: `.star-filled`
- [ ] Alterar cor: `color: #ffc107;`

### Opção 2: Alterar Limite de Comentários
- [ ] Abrir: `api/ratings.php`
- [ ] Procurar: `if ($commentCount >= 2)`
- [ ] Alterar número conforme desejado

### Opção 3: Adicionar/Remover Palavras Proibidas
- [ ] Abrir: `api/profanity-filter.php`
- [ ] Procurar: `function getProfanityList()`
- [ ] Adicionar/remover palavras da lista

### Opção 4: Customizar Mensagens
- [ ] Abrir: `js/ratings.js`
- [ ] Procurar: `showSuccess()` e `showError()`
- [ ] Implementar toast notifications personalizadas

---

## 📋 FASE 8: DOCUMENTAÇÃO

### Documentar Implementação
- [ ] Anotar páginas onde foi integrado
- [ ] Anotar personalizações feitas
- [ ] Guardar este checklist preenchido
- [ ] Criar backup da base de dados

### Consultar Documentação
- [ ] Ler: `docs/README_RATINGS.md`
- [ ] Ler: `docs/INSTALACAO_RAPIDA_RATINGS.md`
- [ ] Consultar: `docs/SISTEMA_RATINGS_COMENTARIOS.md`
- [ ] Ver: `pages/exemplo-ratings.html`

---

## 📋 CHECKLIST FINAL

### Base de Dados
- [ ] ✅ Instalador executado
- [ ] ✅ Testes passaram
- [ ] ✅ Tabelas criadas
- [ ] ✅ Triggers funcionando

### Integração
- [ ] ✅ Font Awesome adicionado
- [ ] ✅ ratings.js incluído
- [ ] ✅ Container adicionado
- [ ] ✅ RatingsUI inicializado
- [ ] ✅ recipeId configurado corretamente

### Funcionalidades
- [ ] ✅ Avaliações funcionam
- [ ] ✅ Comentários funcionam
- [ ] ✅ Filtro de profanidade ativo
- [ ] ✅ Limites funcionando
- [ ] ✅ Notificações ativas

### Testes
- [ ] ✅ Testado em desktop
- [ ] ✅ Testado em mobile
- [ ] ✅ Testado filtro profanidade
- [ ] ✅ Testado limites
- [ ] ✅ Testado notificações

### Responsividade
- [ ] ✅ Desktop (>1200px)
- [ ] ✅ Tablet (768-1199px)
- [ ] ✅ Mobile (<768px)

---

## 🎉 IMPLEMENTAÇÃO COMPLETA!

Se todos os itens estão ✅, o sistema está **100% funcional**!

### O que foi implementado:
- ⭐ Avaliação por estrelas (1-5)
- 💬 Sistema de comentários (máx. 2)
- 🛡️ Filtro de profanidade
- 🚨 Sistema de infrações
- 📊 Média automática
- 🔔 Notificações

### Próximos passos:
1. Monitorizar utilização
2. Recolher feedback dos utilizadores
3. Ajustar conforme necessário
4. Celebrar! 🎉

---

**Sistema pronto para produção!** ✅

Data de conclusão: ____ / ____ / ____
Implementado por: _________________________
Notas: ___________________________________
