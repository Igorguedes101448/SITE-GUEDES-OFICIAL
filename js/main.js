    /* ============================================
   ChefGuedes - Script Principal
   Gerenciamento de tema e funcionalidades globais
   ============================================ */

// ===== TEMA CLARO/ESCURO =====

// Inicializar tema ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    createThemeToggle();
    updateAuthMenu();
    setupUserDropdown();
    
    // Inicializar receitas se não existirem
    initializeRecipes();
});

// Inicializar receitas portuguesas
function initializeRecipes() {
    const recipes = getFromStorage('chefguedes-recipes');
    
    // Se não houver receitas, carregar as receitas portuguesas
    if (!recipes || recipes.length === 0) {
        if (typeof receitasPortuguesas !== 'undefined') {
            const formattedRecipes = receitasPortuguesas.map(rp => ({
                id: rp.id,
                title: rp.titulo,
                category: rp.categoria,
                description: rp.descricao,
                ingredients: rp.ingredientes.join('\n'),
                instructions: rp.preparo.join('\n'),
                image: rp.imagem || '',
                prepTime: parseInt(rp.tempo) || 0,
                difficulty: rp.dificuldade,
                author: 'ChefGuedes',
                createdAt: new Date().toISOString(),
                likes: 0
            }));
            
            saveToStorage('chefguedes-recipes', formattedRecipes);
        }
    }
}

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

function updateAuthMenu() {
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
        const currentUser = getCurrentUser();
        authMenuItems.innerHTML = `
            <li class="user-menu">
                <button class="user-menu-toggle" aria-expanded="false" onclick="toggleUserDropdown(event)">
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

// ===== FUNÇÕES AUXILIARES DE STORAGE =====

// Salvar dados no localStorage
function saveToStorage(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
        return true;
    } catch (error) {
        console.error('Erro ao salvar no localStorage:', error);
        return false;
    }
}

// Obter dados do localStorage
function getFromStorage(key) {
    try {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (error) {
        console.error('Erro ao ler do localStorage:', error);
        return null;
    }
}

// ===== GERENCIAMENTO DE RECEITAS =====

// Obter todas as receitas
function getAllRecipes() {
    return getFromStorage('chefguedes-recipes') || [];
}

// Salvar receita
function saveRecipe(recipe) {
    const recipes = getAllRecipes();
    const currentUser = getCurrentUser();
    
    if (!currentUser) {
        return { success: false, message: 'Utilizador não autenticado.' };
    }
    
    if (recipe.id) {
        // Atualizar receita existente
        const index = recipes.findIndex(r => r.id === recipe.id);
        if (index !== -1) {
            recipes[index] = { ...recipe, updatedAt: new Date().toISOString() };
        }
    } else {
        // Nova receita
        recipe.id = Date.now().toString();
        recipe.createdAt = new Date().toISOString();
        recipe.author = currentUser.username;
        recipe.likes = 0;
        recipes.push(recipe);
    }
    
    saveToStorage('chefguedes-recipes', recipes);
    return { success: true, recipe };
}

// Deletar receita
function deleteRecipe(recipeId) {
    const recipes = getAllRecipes();
    const currentUser = getCurrentUser();
    const recipe = recipes.find(r => r.id === recipeId);
    
    if (!recipe) {
        return { success: false, message: 'Receita não encontrada.' };
    }
    
    if (!currentUser || recipe.author !== currentUser.username) {
        return { success: false, message: 'Não tem permissão para eliminar esta receita.' };
    }
    
    const filtered = recipes.filter(r => r.id !== recipeId);
    saveToStorage('chefguedes-recipes', filtered);
    return { success: true };
}

// Obter receita por ID
function getRecipeById(recipeId) {
    const recipes = getAllRecipes();
    return recipes.find(r => r.id === recipeId);
}

// Pesquisar receitas
function searchRecipes(query, category = '') {
    const recipes = getAllRecipes();
    return recipes.filter(recipe => {
        const matchQuery = query === '' || 
            recipe.title.toLowerCase().includes(query.toLowerCase()) ||
            recipe.description.toLowerCase().includes(query.toLowerCase());
        
        const matchCategory = category === '' || recipe.category === category;
        
        return matchQuery && matchCategory;
    });
}

// ===== GERENCIAMENTO DE GRUPOS =====

// Obter todos os grupos
function getAllGroups() {
    return getFromStorage('chefguedes-groups') || [];
}

// Salvar grupo
function saveGroup(group) {
    const groups = getAllGroups();
    
    if (group.id) {
        const index = groups.findIndex(g => g.id === group.id);
        if (index !== -1) {
            groups[index] = group;
        }
    } else {
        group.id = Date.now().toString();
        group.createdAt = new Date().toISOString();
        group.createdBy = getCurrentUser()?.username || 'Anônimo';
        group.members = group.members || [];
        groups.push(group);
    }
    
    saveToStorage('chefguedes-groups', groups);
    return group;
}

// Deletar grupo
function deleteGroup(groupId) {
    const groups = getAllGroups();
    const filtered = groups.filter(g => g.id !== groupId);
    saveToStorage('chefguedes-groups', filtered);
}

// Obter grupo por ID
function getGroupById(groupId) {
    const groups = getAllGroups();
    return groups.find(g => g.id === groupId);
}

// ===== GERENCIAMENTO DE AGENDAMENTO =====

// Obter todos os agendamentos
function getAllSchedules() {
    return getFromStorage('chefguedes-schedules') || [];
}

// Salvar agendamento
function saveSchedule(schedule) {
    const schedules = getAllSchedules();
    
    if (schedule.id) {
        const index = schedules.findIndex(s => s.id === schedule.id);
        if (index !== -1) {
            schedules[index] = schedule;
        }
    } else {
        schedule.id = Date.now().toString();
        schedule.createdAt = new Date().toISOString();
        schedules.push(schedule);
    }
    
    saveToStorage('chefguedes-schedules', schedules);
    return schedule;
}

// Deletar agendamento
function deleteSchedule(scheduleId) {
    const schedules = getAllSchedules();
    const filtered = schedules.filter(s => s.id !== scheduleId);
    saveToStorage('chefguedes-schedules', filtered);
}

// Obter agendamentos por data
function getSchedulesByDate(date) {
    const schedules = getAllSchedules();
    return schedules.filter(s => s.date === date);
}

// Obter agendamentos de uma semana
function getSchedulesByWeek(startDate, endDate) {
    const schedules = getAllSchedules();
    return schedules.filter(s => {
        const scheduleDate = new Date(s.date);
        return scheduleDate >= startDate && scheduleDate <= endDate;
    });
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

// ===== GERENCIAMENTO DE ATIVIDADES =====

// Adicionar atividade
function addActivity(type, description) {
    const activities = getFromStorage('chefguedes-activities') || [];
    
    const activity = {
        id: Date.now().toString(),
        type: type,
        description: description,
        user: getCurrentUser()?.username || 'Anônimo',
        timestamp: new Date().toISOString()
    };
    
    activities.unshift(activity);
    
    // Manter apenas as últimas 50 atividades
    if (activities.length > 50) {
        activities.splice(50);
    }
    
    saveToStorage('chefguedes-activities', activities);
}

// Obter atividades recentes
function getRecentActivities(limit = 10) {
    const activities = getFromStorage('chefguedes-activities') || [];
    return activities.slice(0, limit);
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
`;
document.head.appendChild(style);

// ===== ESTATÍSTICAS =====

// Obter estatísticas do usuário
function getUserStats() {
    const groups = getAllGroups();
    const currentUser = getCurrentUser();
    
    if (!currentUser) {
        return { recipes: 0, groups: 0, favorites: 0 };
    }
    
    const userGroups = groups.filter(g => g.createdBy === currentUser.username);
    
    const userRecipes = getAllRecipes().filter(r => r.author === currentUser.username);
    const userFavorites = getFromStorage('chefguedes-favorites') || [];
    const userFavoritesList = userFavorites.filter(f => f.userId === currentUser.username);
    
    return {
        recipes: userRecipes.length,
        groups: userGroups.length,
        favorites: userFavoritesList.length
    };
}
