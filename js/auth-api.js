/* ============================================
   ChefGuedes - Sistema de Autenticação (API)
   Gerenciamento de usuários e sessões com MySQL
   ============================================ */

// URL base da API
const API_BASE = window.location.origin + '/siteguedes/api';

// ===== GESTÃO DE SESSÃO =====

// Obter token da sessão
function getSessionToken() {
    return localStorage.getItem('chefguedes-session-token') || sessionStorage.getItem('chefguedes-session-token');
}

// Guardar token da sessão
function saveSessionToken(token, rememberMe = false) {
    if (rememberMe) {
        localStorage.setItem('chefguedes-session-token', token);
        sessionStorage.removeItem('chefguedes-session-token');
    } else {
        sessionStorage.setItem('chefguedes-session-token', token);
        localStorage.removeItem('chefguedes-session-token');
    }
}

// Remover token da sessão
function clearSessionToken() {
    localStorage.removeItem('chefguedes-session-token');
    sessionStorage.removeItem('chefguedes-session-token');
}

// Cache do utilizador atual
let currentUserCache = null;

// ===== FUNÇÕES DE AUTENTICAÇÃO =====

// Registar novo utilizador
async function registerUser(username, email, password) {
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
    
    try {
        const response = await fetch(`${API_BASE}/users.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'register',
                username: username,
                email: email,
                password: password
            })
        });
        
        if (!response.ok) {
            console.error('Erro HTTP:', response.status, response.statusText);
            return {
                success: false,
                message: `Erro do servidor: ${response.status}. Verifique se a base de dados foi criada.`
            };
        }
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao registar:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor. Verifique se o WAMP está a correr e a base de dados foi criada.'
        };
    }
}

// Fazer login
async function loginUser(email, password, rememberMe = false) {
    if (!email || !password) {
        return {
            success: false,
            message: 'Preencha todos os campos.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/users.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'login',
                email: email,
                password: password,
                rememberMe: rememberMe
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data.sessionToken) {
            saveSessionToken(result.data.sessionToken, rememberMe);
            currentUserCache = result.data.user;
        }
        
        return result;
    } catch (error) {
        console.error('Erro ao fazer login:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Fazer logout
async function logoutUser(redirectUrl = null) {
    const sessionToken = getSessionToken();
    
    try {
        await fetch(`${API_BASE}/users.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'logout',
                sessionToken: sessionToken
            })
        });
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
    }
    
    clearSessionToken();
    currentUserCache = null;
    
    // Redirecionar para página especificada ou página inicial
    if (redirectUrl) {
        window.location.href = redirectUrl;
    } else {
        // Detectar se estamos numa subpasta (pages/) ou na raiz
        const isInPagesFolder = window.location.pathname.includes('/pages/');
        const loginPath = isInPagesFolder ? '../login.html' : 'login.html';
        window.location.href = loginPath;
    }
}

// Verificar se utilizador está logado
function isUserLoggedIn() {
    return !!getSessionToken();
}

// Obter utilizador atual
async function getCurrentUser(forceRefresh = false) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        currentUserCache = null;
        return null;
    }
    
    // Se temos cache e não é refresh forçado, retornar cache
    if (currentUserCache && !forceRefresh) {
        return currentUserCache;
    }
    
    try {
        const response = await fetch(`${API_BASE}/users.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'verify_session',
                sessionToken: sessionToken
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data.user) {
            currentUserCache = result.data.user;
            return currentUserCache;
        } else {
            clearSessionToken();
            currentUserCache = null;
            return null;
        }
    } catch (error) {
        console.error('Erro ao obter utilizador:', error);
        clearSessionToken();
        currentUserCache = null;
        return null;
    }
}

// Atualizar perfil do utilizador
async function updateUserProfile(updates) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/users.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_profile',
                sessionToken: sessionToken,
                ...updates
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data.user) {
            currentUserCache = result.data.user;
        }
        
        return result;
    } catch (error) {
        console.error('Erro ao atualizar perfil:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Alterar palavra-passe
async function changePassword(currentPassword, newPassword) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    if (newPassword.length < 6) {
        return {
            success: false,
            message: 'A nova palavra-passe deve ter pelo menos 6 caracteres.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/users.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'change_password',
                sessionToken: sessionToken,
                currentPassword: currentPassword,
                newPassword: newPassword
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao alterar palavra-passe:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Adicionar receita aos favoritos
async function toggleFavorite(recipeId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Faça login para adicionar favoritos.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/recipes.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'toggle_favorite',
                sessionToken: sessionToken,
                recipeId: recipeId
            })
        });
        
        const result = await response.json();
        
        // Atualizar cache
        if (result.success) {
            await getCurrentUser(true);
        }
        
        return result;
    } catch (error) {
        console.error('Erro ao atualizar favorito:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Verificar se receita é favorita
async function isFavoriteRecipe(recipeId) {
    const user = await getCurrentUser();
    
    if (!user || !user.favorites) {
        return false;
    }
    
    return user.favorites.includes(recipeId.toString());
}

// Obter receitas favoritas
async function getFavoriteRecipes() {
    const user = await getCurrentUser();
    
    if (!user || !user.favorites || user.favorites.length === 0) {
        return [];
    }
    
    try {
        const response = await fetch(`${API_BASE}/recipes.php`);
        const result = await response.json();
        
        if (result.success && result.data.recipes) {
            return result.data.recipes.filter(recipe => 
                user.favorites.includes(recipe.id.toString())
            );
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter favoritos:', error);
        return [];
    }
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
async function requireAuth() {
    if (!isUserLoggedIn()) {
        // Salvar URL atual para redirecionar após login
        sessionStorage.setItem('chefguedes-redirect', window.location.pathname);
        window.location.href = '../login.html';
        return false;
    }
    
    // Verificar se a sessão ainda é válida
    const user = await getCurrentUser();
    if (!user) {
        sessionStorage.setItem('chefguedes-redirect', window.location.pathname);
        window.location.href = '../login.html';
        return false;
    }
    
    return true;
}

// Redirecionar após login
async function redirectAfterLogin() {
    const redirect = sessionStorage.getItem('chefguedes-redirect');
    sessionStorage.removeItem('chefguedes-redirect');
    
    // Verificar se é admin
    const user = await getCurrentUser();
    const isAdmin = user && user.is_admin == 1;
    
    if (redirect && redirect !== '/login.html' && redirect !== '/registo.html') {
        window.location.href = redirect;
    } else if (isAdmin) {
        window.location.href = 'pages/admin.html';
    } else {
        window.location.href = 'pages/dashboard.html';
    }
}

// ===== FUNÇÕES DE INTERFACE =====

// Atualizar menu de autenticação
async function updateAuthMenu() {
    const authMenuItems = document.getElementById('authMenuItems');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (!authMenuItems) return;
    
    // Detectar se estamos numa subpasta (pages/) ou na raiz
    const isInSubfolder = window.location.pathname.includes('/pages/');
    const loginPath = isInSubfolder ? '../login.html' : 'login.html';
    
    if (isUserLoggedIn()) {
        const user = await getCurrentUser();
        
        if (user) {
            // Esconder link de login
            authMenuItems.innerHTML = '';
            authMenuItems.style.display = 'none';
            
            // Mostrar botão de perfil
            if (profileDropdown) {
                profileDropdown.style.display = 'flex';
                
                const profileName = document.getElementById('profileName');
                const profileImage = document.getElementById('profileImage');
                
                if (profileName) {
                    profileName.textContent = user.username;
                }
                
                if (profileImage) {
                    if (user.profile_picture) {
                        profileImage.src = user.profile_picture;
                    } else {
                        // Avatar com inicial do nome
                        const initial = user.username.charAt(0).toUpperCase();
                        profileImage.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"%3E%3Ccircle cx="20" cy="20" r="20" fill="%23e6394640"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-size="18" fill="%23e63946" font-weight="bold"%3E' + initial + '%3C/text%3E%3C/svg%3E';
                    }
                }
            }
        } else {
            // Sessão inválida - mostrar login
            authMenuItems.innerHTML = '<a href="' + loginPath + '">Login</a>';
            authMenuItems.style.display = 'list-item';
            
            if (profileDropdown) {
                profileDropdown.style.display = 'none';
            }
        }
    } else {
        // Não está logado - mostrar link de login
        authMenuItems.innerHTML = '<a href="' + loginPath + '">Login</a>';
        authMenuItems.style.display = 'list-item';
        
        // Esconder botão de perfil
        if (profileDropdown) {
            profileDropdown.style.display = 'none';
        }
    }
}

// Toggle do menu de perfil
function toggleProfileMenu() {
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu) {
        profileMenu.classList.toggle('show');
    }
}

// Fechar menu ao clicar fora
document.addEventListener('click', function(event) {
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    if (profileDropdown && profileMenu && !profileDropdown.contains(event.target)) {
        profileMenu.classList.remove('show');
    }
});

// Logout
function logout() {
    if (confirm('Tem certeza que deseja sair?')) {
        logoutUser();
    }
}

// Inicializar menu ao carregar a página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAuth);
} else {
    // DOM já carregado, executar imediatamente
    initializeAuth();
}

function initializeAuth() {
    updateAuthMenu();
    if (isUserLoggedIn()) {
        loadNotifications();
        // Atualizar notificações a cada 30 segundos
        setInterval(loadNotifications, 30000);
    }
}

// ===== SISTEMA DE NOTIFICAÇÕES =====

async function loadNotifications() {
    if (!isUserLoggedIn()) return;
    
    try {
        const response = await fetch(`${API_BASE}/notifications.php?sessionToken=${getSessionToken()}`);
        const result = await response.json();
        
        if (result.success) {
            updateNotificationUI(result.data.notifications, result.data.unread);
        }
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    }
}

function updateNotificationUI(notifications, unreadCount) {
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    
    if (!notificationDropdown) return;
    
    // Mostrar dropdown se estiver logado
    notificationDropdown.style.display = 'flex';
    
    // Atualizar badge
    if (unreadCount > 0) {
        notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        notificationBadge.style.display = 'flex';
    } else {
        notificationBadge.style.display = 'none';
    }
    
    // Atualizar lista
    if (notifications.length === 0) {
        notificationList.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Sem notificações</p>';
        return;
    }
    
    notificationList.innerHTML = notifications.map(notif => {
        const icon = getNotificationIcon(notif.type);
        const isUnread = notif.is_read == 0;
        
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''}" onclick="handleNotificationClick(${notif.id}, '${notif.link || ''}')">
                <div class="notification-icon">${icon}</div>
                <div class="notification-content">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">${formatTimeAgo(notif.created_at)}</div>
                </div>
                ${isUnread ? '<div class="notification-dot"></div>' : ''}
            </div>
        `;
    }).join('');
}

function getNotificationIcon(type) {
    const icons = {
        'friend_request': '',
        'group_invite': '',
        'recipe_share': '',
        'comment': '',
        'like': '',
        'system': ''
    };
    return icons[type] || '';
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Agora mesmo';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m atrás`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h atrás`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d atrás`;
    return date.toLocaleDateString('pt-PT');
}

function toggleNotifications() {
    const notificationMenu = document.getElementById('notificationMenu');
    if (notificationMenu) {
        notificationMenu.classList.toggle('show');
        // Fechar menu de perfil se estiver aberto
        const profileMenu = document.getElementById('profileMenu');
        if (profileMenu) {
            profileMenu.classList.remove('show');
        }
    }
}

async function handleNotificationClick(notificationId, link) {
    // Marcar como lida
    try {
        await fetch(`${API_BASE}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_read',
                sessionToken: getSessionToken(),
                notificationId: notificationId
            })
        });
        
        // Recarregar notificações
        loadNotifications();
        
        // Redirecionar se houver link
        if (link) {
            // Se o link não começar com http ou /, assumir que é relativo à pasta pages
            if (!link.startsWith('http') && !link.startsWith('/')) {
                // Se já estivermos na pasta pages, usar o link direto
                if (window.location.pathname.includes('/pages/')) {
                    window.location.href = link;
                } else {
                    // Caso contrário, adicionar /pages/
                    window.location.href = '/pages/' + link;
                }
            } else {
                window.location.href = link;
            }
        }
    } catch (error) {
        console.error('Erro ao marcar notificação:', error);
    }
}

async function markAllRead() {
    try {
        await fetch(`${API_BASE}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_read',
                sessionToken: getSessionToken()
            })
        });
        
        loadNotifications();
    } catch (error) {
        console.error('Erro ao marcar todas como lidas:', error);
    }
}
