/* ============================================
   ChefGuedes - Sistema de Autenticação
   Gerenciamento de usuários e sessões
   ============================================ */

// ===== FUNÇÕES DE AUTENTICAÇÃO =====

// Registar novo utilizador
function registerUser(username, email, password) {
    // Validações
    if (!username || username.length < 3) {
        return {
            success: false,
            message: 'O nome de utilizador deve ter pelo menos 3 caracteres.'
        };
    }
    
    if (!email || !isValidEmail(email)) {
        return {
            success: false,
            message: 'Email inválido.'
        };
    }
    
    if (!password || password.length < 6) {
        return {
            success: false,
            message: 'A palavra-passe deve ter pelo menos 6 caracteres.'
        };
    }
    
    // Obter utilizadores existentes
    const users = getFromStorage('chefguedes-users') || [];
    
    // Verificar se email já existe
    if (users.some(user => user.email === email)) {
        return {
            success: false,
            message: 'Este email já está registado.'
        };
    }
    
    // Verificar se username já existe
    if (users.some(user => user.username === username)) {
        return {
            success: false,
            message: 'Este nome de utilizador já está em uso.'
        };
    }
    
    // Criar novo utilizador
    const newUser = {
        id: Date.now().toString(),
        username: username,
        email: email,
        password: btoa(password), // Encoding simples (não é seguro para produção!)
        createdAt: new Date().toISOString(),
        profilePicture: '',
        bio: '',
        phone: '',
        location: '',
        preferences: {
            cuisines: [],
            restrictions: [],
            newsletter: false,
            notifications: true
        },
        favorites: []
    };
    
    users.push(newUser);
    saveToStorage('chefguedes-users', users);
    
    // Registar atividade
    addActivity('register', `Novo utilizador: ${username}`);
    
    return {
        success: true,
        message: 'Conta criada com sucesso!',
        user: newUser
    };
}

// Fazer login
function loginUser(email, password, rememberMe = false) {
    if (!email || !password) {
        return {
            success: false,
            message: 'Preencha todos os campos.'
        };
    }
    
    const users = getFromStorage('chefguedes-users') || [];
    const user = users.find(u => u.email === email);
    
    if (!user) {
        return {
            success: false,
            message: 'Email ou palavra-passe incorretos.'
        };
    }
    
    // Verificar palavra-passe
    if (atob(user.password) !== password) {
        return {
            success: false,
            message: 'Email ou palavra-passe incorretos.'
        };
    }
    
    // Criar sessão
    const session = {
        userId: user.id,
        username: user.username,
        email: user.email,
        loginTime: new Date().toISOString(),
        rememberMe: rememberMe
    };
    
    // Salvar sessão
    if (rememberMe) {
        localStorage.setItem('chefguedes-session', JSON.stringify(session));
    } else {
        sessionStorage.setItem('chefguedes-session', JSON.stringify(session));
    }
    
    // Registar atividade
    addActivity('login', `Login: ${user.username}`);
    
    return {
        success: true,
        message: 'Login efetuado com sucesso!',
        user: user
    };
}

// Fazer logout
function logoutUser() {
    const currentUser = getCurrentUser();
    
    if (currentUser) {
        addActivity('logout', `Logout: ${currentUser.username}`);
    }
    
    localStorage.removeItem('chefguedes-session');
    sessionStorage.removeItem('chefguedes-session');
    
    // Detectar se estamos numa subpasta (pages/) ou na raiz
    const isInPagesFolder = window.location.pathname.includes('/pages/');
    const loginPath = isInPagesFolder ? '../login.html' : 'login.html';
    
    // Redirecionar para página de login
    window.location.href = loginPath;
}

// Verificar se utilizador está logado
function isUserLoggedIn() {
    const localSession = localStorage.getItem('chefguedes-session');
    const sessionSession = sessionStorage.getItem('chefguedes-session');
    
    return !!(localSession || sessionSession);
}

// Obter utilizador atual
function getCurrentUser() {
    const localSession = localStorage.getItem('chefguedes-session');
    const sessionSession = sessionStorage.getItem('chefguedes-session');
    
    const session = JSON.parse(localSession || sessionSession || 'null');
    
    if (!session) return null;
    
    const users = getFromStorage('chefguedes-users') || [];
    return users.find(u => u.id === session.userId);
}

// Atualizar perfil do utilizador
function updateUserProfile(updates) {
    const currentUser = getCurrentUser();
    
    if (!currentUser) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    const users = getFromStorage('chefguedes-users') || [];
    const userIndex = users.findIndex(u => u.id === currentUser.id);
    
    if (userIndex === -1) {
        return {
            success: false,
            message: 'Utilizador não encontrado.'
        };
    }
    
    // Atualizar dados
    users[userIndex] = {
        ...users[userIndex],
        ...updates,
        id: currentUser.id, // Garantir que o ID não seja alterado
        password: users[userIndex].password, // Manter palavra-passe
        createdAt: users[userIndex].createdAt // Manter data de criação
    };
    
    saveToStorage('chefguedes-users', users);
    
    // Registar atividade
    addActivity('profile_update', `Perfil atualizado: ${currentUser.username}`);
    
    return {
        success: true,
        message: 'Perfil atualizado com sucesso!',
        user: users[userIndex]
    };
}

// Alterar palavra-passe
function changePassword(currentPassword, newPassword) {
    const currentUser = getCurrentUser();
    
    if (!currentUser) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    // Verificar palavra-passe atual
    if (atob(currentUser.password) !== currentPassword) {
        return {
            success: false,
            message: 'Palavra-passe atual incorreta.'
        };
    }
    
    if (newPassword.length < 6) {
        return {
            success: false,
            message: 'A nova palavra-passe deve ter pelo menos 6 caracteres.'
        };
    }
    
    const users = getFromStorage('chefguedes-users') || [];
    const userIndex = users.findIndex(u => u.id === currentUser.id);
    
    if (userIndex === -1) {
        return {
            success: false,
            message: 'Utilizador não encontrado.'
        };
    }
    
    users[userIndex].password = btoa(newPassword);
    saveToStorage('chefguedes-users', users);
    
    // Registar atividade
    addActivity('password_change', `Palavra-passe alterada: ${currentUser.username}`);
    
    return {
        success: true,
        message: 'Palavra-passe alterada com sucesso!'
    };
}

// Adicionar receita aos favoritos
function toggleFavorite(recipeId) {
    const currentUser = getCurrentUser();
    
    if (!currentUser) {
        return {
            success: false,
            message: 'Faça login para adicionar favoritos.'
        };
    }
    
    const users = getFromStorage('chefguedes-users') || [];
    const userIndex = users.findIndex(u => u.id === currentUser.id);
    
    if (userIndex === -1) {
        return {
            success: false,
            message: 'Utilizador não encontrado.'
        };
    }
    
    if (!users[userIndex].favorites) {
        users[userIndex].favorites = [];
    }
    
    const favoriteIndex = users[userIndex].favorites.indexOf(recipeId);
    
    if (favoriteIndex > -1) {
        // Remover dos favoritos
        users[userIndex].favorites.splice(favoriteIndex, 1);
        saveToStorage('chefguedes-users', users);
        
        return {
            success: true,
            message: 'Removido dos favoritos.',
            isFavorite: false
        };
    } else {
        // Adicionar aos favoritos
        users[userIndex].favorites.push(recipeId);
        saveToStorage('chefguedes-users', users);
        
        addActivity('favorite', `Adicionou uma receita aos favoritos`);
        
        return {
            success: true,
            message: 'Adicionado aos favoritos!',
            isFavorite: true
        };
    }
}

// Verificar se receita é favorita
function isFavoriteRecipe(recipeId) {
    const currentUser = getCurrentUser();
    
    if (!currentUser || !currentUser.favorites) {
        return false;
    }
    
    return currentUser.favorites.includes(recipeId);
}

// Obter receitas favoritas
function getFavoriteRecipes() {
    const currentUser = getCurrentUser();
    
    if (!currentUser || !currentUser.favorites) {
        return [];
    }
    
    const allRecipes = getAllRecipes();
    return allRecipes.filter(recipe => currentUser.favorites.includes(recipe.id));
}

// ===== FUNÇÕES DE VALIDAÇÃO =====

// Validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validar senha forte
function isStrongPassword(password) {
    // Pelo menos 8 caracteres, 1 maiúscula, 1 minúscula, 1 número
    const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
    return strongRegex.test(password);
}

// ===== PROTEÇÃO DE ROTAS =====

// Verificar se a página requer autenticação
function requireAuth() {
    if (!isUserLoggedIn()) {
        // Salvar URL atual para redirecionar após login
        sessionStorage.setItem('chefguedes-redirect', window.location.pathname);
        window.location.href = '../login.html';
        return false;
    }
    return true;
}

// Redirecionar após login
function redirectAfterLogin() {
    const redirect = sessionStorage.getItem('chefguedes-redirect');
    sessionStorage.removeItem('chefguedes-redirect');
    
    // Verificar se é admin
    const currentUser = getCurrentUser();
    const isAdmin = currentUser && currentUser.is_admin === 1;
    
    if (redirect && redirect !== '/login.html' && redirect !== '/registo.html') {
        window.location.href = redirect;
    } else if (isAdmin) {
        window.location.href = 'pages/admin.html';
    } else {
        window.location.href = 'pages/dashboard.html';
    }
}

// ===== DADOS DE DEMONSTRAÇÃO =====

// Criar utilizador de demonstração (apenas para testes)
function createDemoUser() {
    const users = getFromStorage('chefguedes-users') || [];
    
    // Verificar se já existe
    if (users.some(u => u.email === 'demo@chefguedes.pt')) {
        return;
    }
    
    const demoUser = {
        id: 'demo-user-001',
        username: 'Demo Chef',
        email: 'demo@chefguedes.pt',
        password: btoa('demo123'), // password: demo123
        createdAt: new Date().toISOString(),
        profilePicture: '',
        bio: 'Utilizador de demonstração do ChefGuedes',
        phone: '+351 912 345 678',
        location: 'Lisboa, Portugal',
        preferences: {
            cuisines: ['Portuguesa', 'Italiana', 'Asiática'],
            restrictions: ['Vegetariano'],
            newsletter: true,
            notifications: true
        },
        favorites: []
    };
    
    users.push(demoUser);
    saveToStorage('chefguedes-users', users);
}

// Criar receitas de demonstração
function createDemoRecipes() {
    const recipes = getAllRecipes();
    
    if (recipes.length > 0) {
        return; // Já existem receitas
    }
    
    const demoRecipes = [
        {
            id: 'recipe-001',
            title: 'Bacalhau à Brás',
            category: 'Prato Principal',
            description: 'Um clássico da cozinha portuguesa. Delicioso bacalhau desfiado com batata palha e ovos.',
            ingredients: 'Bacalhau demolhado (400g)\nBatatas (500g)\nCebolas (2 unidades)\nOvos (6 unidades)\nAlho (3 dentes)\nSalsa picada\nAzeite\nAzeitonas pretas\nSal e pimenta',
            instructions: '1. Demolhar o bacalhau durante 24-48 horas, mudando a água várias vezes\n2. Cozer o bacalhau e desfiar, retirando peles e espinhas\n3. Cortar as batatas em palitos finos e fritar\n4. Refogar a cebola e o alho picados em azeite\n5. Juntar o bacalhau desfiado e deixar saltear\n6. Adicionar as batatas fritas escorridas\n7. Bater os ovos e juntar ao preparado, mexendo rapidamente\n8. Temperar com sal e pimenta\n9. Servir decorado com azeitonas e salsa',
            image: '',
            author: 'Demo Chef',
            createdAt: new Date('2025-01-15').toISOString()
        },
        {
            id: 'recipe-002',
            title: 'Arroz de Pato',
            category: 'Prato Principal',
            description: 'Prato tradicional português perfeito para ocasiões especiais.',
            ingredients: 'Pato inteiro (1 unidade)\nArroz carolino (400g)\nChouriço (200g)\nPresunto (100g)\nCebola (2 unidades)\nAlho (4 dentes)\nFolha de louro\nVinho branco\nCaldo de pato\nLaranja\nAzeite\nSal e pimenta',
            instructions: '1. Cozinhar o pato em água com cebola, alho e louro até ficar tenro\n2. Desfiar o pato e reservar o caldo\n3. Cortar o chouriço em rodelas e o presunto em cubos\n4. Refogar cebola picada e adicionar o arroz\n5. Juntar o caldo do pato gradualmente até o arroz ficar cozido\n6. Misturar o pato desfiado, chouriço e presunto\n7. Levar ao forno para gratinar\n8. Servir com gomos de laranja',
            image: '',
            author: 'Demo Chef',
            createdAt: new Date('2025-01-20').toISOString()
        },
        {
            id: 'recipe-003',
            title: 'Pastel de Nata',
            category: 'Sobremesa',
            description: 'O doce mais famoso de Portugal! Deliciosos pastéis de nata caseiros.',
            ingredients: 'Massa folhada (300g)\nLeite (500ml)\nAçúcar (250g)\nGemas (6 unidades)\nFarinha (2 colheres de sopa)\nCanela em pau\nRaspa de limão\nCanela em pó (para polvilhar)',
            instructions: '1. Preparar um creme: ferver o leite com a canela em pau e raspa de limão\n2. Misturar o açúcar com a farinha e as gemas\n3. Juntar o leite quente aos poucos, mexendo sempre\n4. Levar ao lume até engrossar\n5. Forrar formas de pastéis com a massa folhada\n6. Rechear com o creme\n7. Levar ao forno a 250°C até a superfície caramelizar\n8. Polvilhar com canela',
            image: '',
            author: 'Demo Chef',
            createdAt: new Date('2025-02-01').toISOString()
        },
        {
            id: 'recipe-004',
            title: 'Caldo Verde',
            category: 'Entrada',
            description: 'Sopa tradicional portuguesa perfeita para dias frios.',
            ingredients: 'Batatas (6 unidades)\nCouve galega (400g)\nChouriço (200g)\nCebola (1 unidade)\nAlho (3 dentes)\nAzeite\nSal',
            instructions: '1. Cozer as batatas com a cebola e o alho\n2. Triturar até obter um puré cremoso\n3. Cortar a couve em juliana bem fina\n4. Juntar a couve ao caldo e deixar ferver 5 minutos\n5. Fritar as rodelas de chouriço\n6. Servir o caldo com o chouriço e um fio de azeite',
            image: '',
            author: 'Demo Chef',
            createdAt: new Date('2025-02-05').toISOString()
        },
        {
            id: 'recipe-005',
            title: 'Francesinha',
            category: 'Prato Principal',
            description: 'Prato icônico do Porto. Uma explosão de sabores!',
            ingredients: 'Pão de forma (4 fatias)\nBife de vaca (2 unidades)\nSalsicha fresca (2 unidades)\nPresunto (4 fatias)\nQueijo (200g)\nMolho de tomate\nCerveja\nMostarda\nPiri-piri\nBatatas fritas\nOvo (1 unidade)',
            instructions: '1. Grelhar os bifes e as salsichas\n2. Preparar o molho: tomate, cerveja, mostarda e piri-piri\n3. Montar: pão, bife, presunto, salsicha, pão\n4. Cobrir com queijo e levar ao forno\n5. Cobrir com o molho bem quente\n6. Servir com batata frita e ovo estrelado',
            image: '',
            author: 'Demo Chef',
            createdAt: new Date('2025-02-10').toISOString()
        }
    ];
    
    saveToStorage('chefguedes-recipes', demoRecipes);
}

// Inicializar dados de demonstração
// createDemoUser();
// createDemoRecipes();
