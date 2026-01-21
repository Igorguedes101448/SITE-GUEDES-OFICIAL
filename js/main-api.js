/* ============================================
   ChefGuedes - Script Principal (API)
   Gerenciamento de tema e funcionalidades globais com MySQL
   ============================================ */

// ===== TEMA CLARO/ESCURO =====

// Inicializar tema ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    createThemeToggle();
    updateAuthMenu();
    setupUserDropdown();
});

// Função para inicializar o tema
function initializeTheme() {
    const savedTheme = localStorage.getItem('chefguedes-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

// Função para alternar tema
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('chefguedes-theme', newTheme);
    
    // Atualizar ícone do toggle
    updateThemeToggleIcon(newTheme);
}

// Função para criar botão de toggle de tema
function createThemeToggle() {
    const navMenu = document.querySelector('.nav-menu');
    if (!navMenu) return;
    
    const themeToggleLi = document.createElement('li');
    themeToggleLi.innerHTML = `
        <button class="theme-toggle" id="themeToggle" aria-label="Alternar tema">
            <span class="theme-toggle-slider" id="themeToggleIcon"></span>
        </button>
    `;
    
    navMenu.appendChild(themeToggleLi);
    
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', toggleTheme);
    
    // Definir ícone inicial
    const currentTheme = document.documentElement.getAttribute('data-theme');
    updateThemeToggleIcon(currentTheme);
}

// Função para atualizar ícone do toggle
function updateThemeToggleIcon(theme) {
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
        icon.textContent = theme === 'light' ? '☀️' : '🌙';
    }
}

// ===== ATUALIZAR MENU DE AUTENTICAÇÃO =====

async function updateAuthMenu() {
    const authMenuItems = document.getElementById('authMenuItems');
    if (!authMenuItems) return;
    
    // Detectar se estamos numa subpasta (pages/, setup/) ou na raiz
    const currentPath = window.location.pathname;
    const isInPagesFolder = currentPath.includes('/pages/');
    const isInSetupFolder = currentPath.includes('/setup/');
    
    let dashboardPath, perfilPath, guiaPath, loginPath;
    
    if (isInPagesFolder) {
        dashboardPath = 'dashboard.html';
        perfilPath = 'perfil.html';
        guiaPath = '../setup/guia.html';
        loginPath = '../login.html';
    } else if (isInSetupFolder) {
        dashboardPath = '../pages/dashboard.html';
        perfilPath = '../pages/perfil.html';
        guiaPath = 'guia.html';
        loginPath = '../login.html';
    } else {
        dashboardPath = 'pages/dashboard.html';
        perfilPath = 'pages/perfil.html';
        guiaPath = 'setup/guia.html';
        loginPath = 'login.html';
    }
    
    if (isUserLoggedIn()) {
        const currentUser = await getCurrentUser();
        if (currentUser) {
            authMenuItems.innerHTML = `
                <li class="user-menu">
                    <button class="user-menu-toggle" aria-expanded="false" onclick="toggleUserDropdown(event)">
                        ${currentUser.profile_picture ? `<img src="${currentUser.profile_picture}" alt="${currentUser.username}" class="user-avatar-small">` : ''}
                        Olá, ${currentUser.username}
                    </button>
                    <div class="user-dropdown" role="menu" aria-hidden="true">
                        <a href="${guiaPath}">Guia</a>
                        <a href="${dashboardPath}">Dashboard</a>
                        <a href="${perfilPath}">Perfil</a>
                        <a href="#" onclick="logoutUser(); return false;">Sair</a>
                    </div>
                </li>
            `;
        }
    } else {
        authMenuItems.innerHTML = `<li><a href="${loginPath}">Login</a></li>`;
    }
}

// ===== MENU DROPDOWN DO UTILIZADOR =====

function setupUserDropdown() {
    // Fechar qualquer dropdown aberto ao clicar fora
    document.addEventListener('click', function(e) {
        const openDropdowns = document.querySelectorAll('.user-dropdown.show');
        openDropdowns.forEach(dd => {
            const menu = dd.closest('.user-menu');
            if (menu && !menu.contains(e.target)) {
                dd.classList.remove('show');
                const toggle = menu.querySelector('.user-menu-toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                dd.setAttribute('aria-hidden', 'true');
            }
        });
    });

    // Fechar com a tecla Esc
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.user-dropdown.show').forEach(dd => {
                dd.classList.remove('show');
                const toggle = dd.closest('.user-menu')?.querySelector('.user-menu-toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                dd.setAttribute('aria-hidden', 'true');
            });
        }
    });
}

function toggleUserDropdown(event) {
    event.stopPropagation();
    const toggleBtn = event.currentTarget || event.target;
    const menu = toggleBtn.closest('.user-menu');
    if (!menu) return;
    const dropdown = menu.querySelector('.user-dropdown');
    if (!dropdown) return;

    // Fechar outros dropdowns abertos
    document.querySelectorAll('.user-dropdown.show').forEach(d => {
        if (d !== dropdown) {
            d.classList.remove('show');
            const otherToggle = d.closest('.user-menu')?.querySelector('.user-menu-toggle');
            if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
            d.setAttribute('aria-hidden', 'true');
        }
    });

    dropdown.classList.toggle('show');
    const isOpen = dropdown.classList.contains('show');
    toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    dropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
}

// ===== GERENCIAMENTO DE RECEITAS =====

// Obter todas as receitas
async function getAllRecipes(category = '', search = '') {
    try {
        let url = `${API_BASE}/recipes.php`;
        const params = new URLSearchParams();
        
        if (category) params.append('category', category);
        if (search) params.append('search', search);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data.recipes) {
            return result.data.recipes;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter receitas:', error);
        return [];
    }
}

// Salvar receita
async function saveRecipe(recipe) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const action = recipe.id ? 'update' : 'create';
        
        const response = await fetch(`${API_BASE}/recipes.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                sessionToken: sessionToken,
                recipeId: recipe.id,
                ...recipe
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao salvar receita:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Deletar receita
async function deleteRecipe(recipeId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/recipes.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                sessionToken: sessionToken,
                recipeId: recipeId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao deletar receita:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Obter receita por ID
async function getRecipeById(recipeId) {
    const recipes = await getAllRecipes();
    return recipes.find(r => r.id == recipeId);
}

// Pesquisar receitas
async function searchRecipes(query, category = '') {
    return await getAllRecipes(category, query);
}

// ===== GERENCIAMENTO DE GRUPOS =====

// Obter todos os grupos
async function getAllGroups() {
    try {
        const response = await fetch(`${API_BASE}/groups.php`);
        const result = await response.json();
        
        if (result.success && result.data.groups) {
            return result.data.groups;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter grupos:', error);
        return [];
    }
}

// Obter grupos do utilizador (onde é membro)
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

// Salvar grupo
async function saveGroup(group) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create',
                sessionToken: sessionToken,
                ...group
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao salvar grupo:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Deletar grupo
async function deleteGroup(groupId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                sessionToken: sessionToken,
                groupId: groupId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao deletar grupo:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Obter grupo por ID
async function getGroupById(groupId) {
    const groups = await getUserGroups(); // Mudado de getAllGroups para getUserGroups para obter o role
    return groups.find(g => g.id == groupId);
}

// Obter membros do grupo
async function getGroupMembers(groupId) {
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'get_members',
                groupId: groupId
            })
        });
        
        const result = await response.json();
        if (result.success && result.data.members) {
            return result.data.members;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter membros:', error);
        return [];
    }
}

// Verificar se o utilizador atual é admin do grupo
async function isGroupAdmin(groupId) {
    const currentUser = await getCurrentUser();
    if (!currentUser) return false;
    
    const members = await getGroupMembers(groupId);
    const userMember = members.find(m => m.user_id == currentUser.id);
    
    return userMember && userMember.role === 'admin';
}

// Adicionar membro ao grupo por user_code
async function addGroupMember(groupId, userCode) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'add_member',
                sessionToken: sessionToken,
                groupId: groupId,
                userCode: userCode
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao adicionar membro:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Remover membro do grupo
async function removeGroupMember(groupId, userId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'remove_member',
                sessionToken: sessionToken,
                groupId: groupId,
                userId: userId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao remover membro:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Atualizar grupo
async function updateGroup(groupId, groupData) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update',
                sessionToken: sessionToken,
                groupId: groupId,
                name: groupData.name,
                description: groupData.description
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao atualizar grupo:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Apagar grupo
async function deleteGroup(groupId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                sessionToken: sessionToken,
                groupId: groupId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao apagar grupo:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// ===== FUNÇÕES DE DATA =====

// Formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Formatar data e hora
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ===== FUNÇÕES DE IMAGEM =====

// Converter imagem para Base64
function imageToBase64(file, callback) {
    const reader = new FileReader();
    reader.onload = function(e) {
        callback(e.target.result);
    };
    reader.readAsDataURL(file);
}

// Validar tipo de imagem
function isValidImageType(file) {
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return validTypes.includes(file.type);
}

// Validar tamanho de imagem (max 2MB)
function isValidImageSize(file) {
    const maxSize = 2 * 1024 * 1024; // 2MB
    return file.size <= maxSize;
}

// ===== FUNÇÕES DE NOTIFICAÇÃO =====

// Mostrar notificação de sucesso
function showSuccess(message) {
    showNotification(message, 'success');
}

// Mostrar notificação de erro
function showError(message) {
    showNotification(message, 'error');
}

// Mostrar notificação
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: ${type === 'success' ? 'var(--success-color)' : type === 'error' ? 'var(--danger-color)' : 'var(--info-color)'};
        color: white;
        border-radius: var(--border-radius-sm);
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Adicionar animações de notificação
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .user-avatar-small {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 8px;
        vertical-align: middle;
    }
`;
document.head.appendChild(style);

// ===== ESTATÍSTICAS =====

// Obter estatísticas do usuário
async function getUserStats() {
    const currentUser = await getCurrentUser();
    
    if (!currentUser) {
        return { recipes: 0, groups: 0, favorites: 0 };
    }
    
    const recipes = await getAllRecipes();
    const userGroups = await getUserGroups();
    
    const userRecipes = recipes.filter(r => r.author_id == currentUser.id);
    
    return {
        recipes: userRecipes.length,
        groups: userGroups.length,
        favorites: currentUser.favorites ? currentUser.favorites.length : 0
    };
}

// ===== FUNÇÕES DE MODAL =====

// Abrir modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// Fechar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// Fechar modal ao clicar fora
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// ===== FUNÇÕES DE NOTIFICAÇÃO =====

// Mostrar notificação de sucesso
function showSuccess(message) {
    showNotification(message, 'success');
}

// Mostrar notificação de erro
function showError(message) {
    showNotification(message, 'error');
}

// Mostrar notificação
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Adicionar animações CSS se não existirem
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// ===== FUNÇÕES DE CONVITES DE GRUPOS =====

// Obter convites pendentes
async function getGroupInvites() {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return [];
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'get_invites',
                sessionToken: sessionToken
            })
        });
        
        const result = await response.json();
        if (result.success && result.data.invites) {
            return result.data.invites;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter convites:', error);
        return [];
    }
}

// Aceitar convite de grupo
async function acceptGroupInvite(inviteId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'accept_invite',
                sessionToken: sessionToken,
                inviteId: inviteId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao aceitar convite:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Recusar convite de grupo
async function rejectGroupInvite(inviteId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/groups.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'reject_invite',
                sessionToken: sessionToken,
                inviteId: inviteId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao recusar convite:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// ===== FUNÇÕES DE AGENDAMENTO =====

// Obter agendamentos
async function getSchedules(startDate, endDate, groupId = null) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return [];
    }
    
    try {
        let url = `${API_BASE}/schedules.php?sessionToken=${sessionToken}`;
        if (startDate) url += `&start_date=${startDate}`;
        if (endDate) url += `&end_date=${endDate}`;
        if (groupId) url += `&group_id=${groupId}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data.schedules) {
            return result.data.schedules;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter agendamentos:', error);
        return [];
    }
}

// Criar agendamento
async function createSchedule(schedule) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/schedules.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create',
                sessionToken: sessionToken,
                ...schedule
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao criar agendamento:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Atualizar agendamento
async function updateSchedule(schedule) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/schedules.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update',
                sessionToken: sessionToken,
                ...schedule
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao atualizar agendamento:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Eliminar agendamento
async function deleteSchedule(scheduleId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/schedules.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                sessionToken: sessionToken,
                scheduleId: scheduleId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao eliminar agendamento:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// ===== FUNÇÕES DE NOTIFICAÇÕES =====

// Obter notificações do utilizador
async function getNotifications() {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            notifications: [],
            unread: 0
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/notifications.php?sessionToken=${sessionToken}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            return {
                notifications: result.data.notifications || [],
                unread: result.data.unread || 0
            };
        }
        
        return {
            notifications: [],
            unread: 0
        };
    } catch (error) {
        console.error('Erro ao obter notificações:', error);
        return {
            notifications: [],
            unread: 0
        };
    }
}

// Marcar notificação como lida
async function markNotificationRead(notificationId = null) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_read',
                sessionToken: sessionToken,
                notificationId: notificationId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao marcar notificação:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Eliminar notificação
async function deleteNotification(notificationId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                sessionToken: sessionToken,
                notificationId: notificationId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao eliminar notificação:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// ===== FUNÇÕES DE ASSISTENTE IA =====

// Solicitar sugestões de receitas
async function getRecipeSuggestions(context = {}) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/ai.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'suggest_recipes',
                sessionToken: sessionToken,
                context: context
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao obter sugestões:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Solicitar plano semanal
async function getWeeklyPlan(startDate, servings = 2, mealsPerDay = 2) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/ai.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'suggest_weekly_plan',
                sessionToken: sessionToken,
                start_date: startDate,
                servings: servings,
                meals_per_day: mealsPerDay
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao gerar plano semanal:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Solicitar melhorias para receita
async function getRecipeImprovements(recipeId) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return {
            success: false,
            message: 'Utilizador não está logado.'
        };
    }
    
    try {
        const response = await fetch(`${API_BASE}/ai.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'suggest_improvements',
                sessionToken: sessionToken,
                recipeId: recipeId
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro ao obter melhorias:', error);
        return {
            success: false,
            message: 'Erro de conexão com o servidor.'
        };
    }
}

// Obter histórico de sugestões IA
async function getAISuggestionsHistory(type = null) {
    const sessionToken = getSessionToken();
    
    if (!sessionToken) {
        return [];
    }
    
    try {
        let url = `${API_BASE}/ai.php?sessionToken=${sessionToken}`;
        if (type) url += `&type=${type}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data.suggestions) {
            return result.data.suggestions;
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao obter histórico:', error);
        return [];
    }
}

// ===== FUNÇÕES DE UTILIDADE DE DATA =====

// Obter data formatada para input
function getDateForInput(date) {
    return date.toISOString().split('T')[0];
}

// Obter início da semana
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

// Obter fim da semana
function getWeekEnd(date) {
    const start = getWeekStart(date);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    return end;
}

// Obter dias da semana
function getWeekDays(startDate) {
    const days = [];
    const current = new Date(startDate);
    
    for (let i = 0; i < 7; i++) {
        days.push(new Date(current));
        current.setDate(current.getDate() + 1);
    }
    
    return days;
}

// Obter nome do dia da semana
function getDayName(date) {
    const days = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    return days[date.getDay()];
}

// Formatar data para exibição
function formatDate(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}
