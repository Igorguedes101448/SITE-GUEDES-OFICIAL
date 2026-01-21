/* ============================================
   ChefGuedes - Sistema de Administração
   Gestão de utilizadores e receitas
   ============================================ */

// Estado global
let currentTab = 'users';
let allUsers = [];
let allRecipes = [];
let selectedUserId = null;
let confirmAction = null;

// ============================================
// INICIALIZAÇÃO
// ============================================
document.addEventListener('DOMContentLoaded', async () => {
    // Verificar se é admin
    const isAdmin = await checkAdmin();
    if (!isAdmin) {
        alert('Acesso negado. Apenas administradores podem aceder a esta página.');
        window.location.href = '../login.html';
        return;
    }

    // Carregar dados iniciais
    await loadStats();
    await loadUsers();
    await loadRecipes();

    // Event listeners para pesquisa
    document.getElementById('searchUsers').addEventListener('input', filterUsers);
    document.getElementById('searchRecipes').addEventListener('input', filterRecipes);
});

// ============================================
// VERIFICAR SE É ADMIN
// ============================================
async function checkAdmin() {
    try {
        // Primeiro verificar se está logado via auth-api
        if (typeof getCurrentUser === 'function') {
            const user = await getCurrentUser();
            if (user && user.is_admin == 1) {
                return true;
            }
        }
        
        // Tentar via API PHP (para sessões PHP)
        const response = await fetch('../api/admin.php?action=check_admin');
        const data = await response.json();
        return data.success && data.data.is_admin;
    } catch (error) {
        console.error('Erro ao verificar admin:', error);
        return false;
    }
}

// ============================================
// CARREGAR ESTATÍSTICAS
// ============================================
async function loadStats() {
    try {
        const response = await fetch('../api/admin.php?action=stats');
        const data = await response.json();

        if (data.success) {
            document.getElementById('totalUsers').textContent = data.data.totalUsers;
            document.getElementById('bannedUsers').textContent = data.data.bannedUsers;
            document.getElementById('totalRecipes').textContent = data.data.totalRecipes;
            document.getElementById('publicRecipes').textContent = data.data.publicRecipes;
            
            // Adicionar contadores adicionais se existirem
            if (data.data.suspendedUsers !== undefined) {
                const suspendedEl = document.getElementById('suspendedUsers');
                if (suspendedEl) suspendedEl.textContent = data.data.suspendedUsers;
            }
            if (data.data.todayActions !== undefined) {
                const actionsEl = document.getElementById('todayActions');
                if (actionsEl) actionsEl.textContent = data.data.todayActions;
            }
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

// ============================================
// CARREGAR UTILIZADORES
// ============================================
async function loadUsers(search = '') {
    try {
        const url = search 
            ? `../api/admin.php?action=users&search=${encodeURIComponent(search)}`
            : '../api/admin.php?action=users';
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            allUsers = data.data;
            displayUsers(allUsers);
        }
    } catch (error) {
        console.error('Erro ao carregar utilizadores:', error);
        document.getElementById('usersTable').innerHTML = `
            <tr><td colspan="6" class="empty-state">Erro ao carregar utilizadores.</td></tr>
        `;
    }
}

// ============================================
// EXIBIR UTILIZADORES
// ============================================
function displayUsers(users) {
    const tbody = document.getElementById('usersTable');
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="7" class="empty-state">Nenhum utilizador encontrado.</td></tr>
        `;
        return;
    }

    tbody.innerHTML = users.map(user => {
        const date = new Date(user.created_at).toLocaleDateString('pt-PT');
        const isBanned = user.banned == 1;
        const isSuspended = user.suspended_until && new Date(user.suspended_until) > new Date();
        const warningCount = user.warning_count || 0;
        
        let statusBadge = '';
        let actions = '';
        
        if (isBanned) {
            statusBadge = '<span class="badge badge-danger">Banido</span>';
            actions = `<button class="btn btn-success" onclick="unbanUser(${user.id})">Desbanir</button>`;
        } else if (isSuspended) {
            const suspendDate = new Date(user.suspended_until).toLocaleDateString('pt-PT');
            statusBadge = `<span class="badge badge-warning">Suspenso até ${suspendDate}</span>`;
            actions = `
                <button class="btn btn-success" onclick="unsuspendUser(${user.id})">Reativar</button>
                <button class="btn btn-danger" onclick="openBanModal(${user.id})">Banir</button>
            `;
        } else {
            statusBadge = '<span class="badge badge-success">Ativo</span>';
            actions = `
                <button class="btn btn-secondary" onclick="openWarnModal(${user.id})">Avisar (${warningCount})</button>
                <button class="btn btn-warning" onclick="openSuspendModal(${user.id})">Suspender</button>
                <button class="btn btn-danger" onclick="openBanModal(${user.id})">Banir</button>
            `;
        }
        
        actions += `<button class="btn btn-danger" onclick="confirmDeleteUser(${user.id})" style="margin-top:5px;">Apagar</button>`;
        
        return `
            <tr>
                <td>${user.id}</td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                        <span>${user.username}</span>
                    </div>
                </td>
                <td>${user.email}</td>
                <td>${date}</td>
                <td>${statusBadge}</td>
                <td>${warningCount}</td>
                <td style="min-width: 200px;">${actions}</td>
            </tr>
        `;
    }).join('');
}

// ============================================
// CARREGAR RECEITAS
// ============================================
async function loadRecipes(search = '') {
    try {
        const url = search 
            ? `../api/admin.php?action=recipes&search=${encodeURIComponent(search)}`
            : '../api/admin.php?action=recipes';
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            allRecipes = data.data;
            displayRecipes(allRecipes);
        }
    } catch (error) {
        console.error('Erro ao carregar receitas:', error);
        document.getElementById('recipesTable').innerHTML = `
            <tr><td colspan="8" class="empty-state">Erro ao carregar receitas.</td></tr>
        `;
    }
}

// ============================================
// EXIBIR RECEITAS
// ============================================
function displayRecipes(recipes) {
    const tbody = document.getElementById('recipesTable');
    
    if (recipes.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="8" class="empty-state">Nenhuma receita encontrada.</td></tr>
        `;
        return;
    }

    tbody.innerHTML = recipes.map(recipe => {
        const date = new Date(recipe.created_at).toLocaleDateString('pt-PT');
        const imageUrl = recipe.image || '../images/receitas/placeholder.jpg';
        
        let visibilityBadge = '';
        if (recipe.visibility === 'public') {
            visibilityBadge = '<span class="badge badge-success">Pública</span>';
        } else if (recipe.visibility === 'private') {
            visibilityBadge = '<span class="badge badge-danger">Privada</span>';
        } else {
            visibilityBadge = '<span class="badge badge-warning">Amigos</span>';
        }
        
        return `
            <tr>
                <td>${recipe.id}</td>
                <td><img src="${imageUrl}" alt="${recipe.title}" class="recipe-image" onerror="this.src='../images/receitas/placeholder.jpg'"></td>
                <td>${recipe.title}</td>
                <td>${recipe.author_username || 'Desconhecido'}</td>
                <td>${recipe.category || 'Sem categoria'}</td>
                <td>${visibilityBadge}</td>
                <td>${date}</td>
                <td>
                    <button class="btn btn-danger" onclick="confirmDeleteRecipeWithReason(${recipe.id})">Apagar</button>
                </td>
            </tr>
        `;
    }).join('');
}

// ============================================
// TROCAR TABS
// ============================================
function switchTab(tab) {
    currentTab = tab;
    
    // Atualizar tabs
    document.querySelectorAll('.admin-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Atualizar seções
    document.querySelectorAll('.admin-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`${tab}-section`).classList.add('active');
}

// ============================================
// FILTRAR UTILIZADORES
// ============================================
function filterUsers() {
    const search = document.getElementById('searchUsers').value.toLowerCase();
    const filtered = allUsers.filter(user => 
        user.username.toLowerCase().includes(search) || 
        user.email.toLowerCase().includes(search)
    );
    displayUsers(filtered);
}

// ============================================
// FILTRAR RECEITAS
// ============================================
function filterRecipes() {
    const search = document.getElementById('searchRecipes').value.toLowerCase();
    const filtered = allRecipes.filter(recipe => 
        recipe.title.toLowerCase().includes(search) || 
        (recipe.category && recipe.category.toLowerCase().includes(search))
    );
    displayRecipes(filtered);
}

// ============================================
// BANIR UTILIZADOR
// ============================================
function openBanModal(userId) {
    selectedUserId = userId;
    document.getElementById('banUserModal').classList.add('active');
    document.getElementById('banReason').value = '';
}

function closeBanModal() {
    document.getElementById('banUserModal').classList.remove('active');
    selectedUserId = null;
}

async function confirmBanUser() {
    const reason = document.getElementById('banReason').value.trim();
    
    if (!reason) {
        alert('Por favor, insira o motivo do banimento.');
        return;
    }

    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'ban_user',
                user_id: selectedUserId,
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Utilizador banido com sucesso!');
            closeBanModal();
            await loadUsers();
            await loadStats();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao banir utilizador:', error);
        alert('Erro ao banir utilizador.');
    }
}

// ============================================
// DESBANIR UTILIZADOR
// ============================================
async function unbanUser(userId) {
    if (!confirm('Tem a certeza que deseja desbanir este utilizador?')) {
        return;
    }

    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'unban_user',
                user_id: userId
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Utilizador desbanido com sucesso!');
            await loadUsers();
            await loadStats();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao desbanir utilizador:', error);
        alert('Erro ao desbanir utilizador.');
    }
}

// ============================================
// APAGAR UTILIZADOR
// ============================================
function confirmDeleteUser(userId) {
    confirmAction = () => deleteUser(userId);
    document.getElementById('confirmTitle').textContent = 'Apagar Utilizador';
    document.getElementById('confirmMessage').textContent = 
        'Tem a certeza que deseja apagar este utilizador? Esta ação não pode ser desfeita e todas as suas receitas serão removidas.';
    document.getElementById('confirmModal').classList.add('active');
}

async function deleteUser(userId) {
    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_user',
                user_id: userId
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Utilizador apagado com sucesso!');
            await loadUsers();
            await loadStats();
            await loadRecipes(); // Atualizar receitas também
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao apagar utilizador:', error);
        alert('Erro ao apagar utilizador.');
    }
}

// ============================================
// APAGAR RECEITA
// ============================================
function confirmDeleteRecipe(recipeId) {
    confirmAction = () => deleteRecipe(recipeId);
    document.getElementById('confirmTitle').textContent = 'Apagar Receita';
    document.getElementById('confirmMessage').textContent = 
        'Tem a certeza que deseja apagar esta receita? Esta ação não pode ser desfeita.';
    document.getElementById('confirmModal').classList.add('active');
}

async function deleteRecipe(recipeId) {
    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_recipe',
                recipe_id: recipeId
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Receita apagada com sucesso!');
            await loadRecipes();
            await loadStats();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao apagar receita:', error);
        alert('Erro ao apagar receita.');
    }
}

// ============================================
// MODAL DE CONFIRMAÇÃO
// ============================================
function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    confirmAction = null;
}

async function executeConfirmAction() {
    if (confirmAction) {
        closeConfirmModal();
        await confirmAction();
    }
}

// ============================================
// LOGOUT
// ============================================
async function logout() {
    if (confirm('Tem a certeza que deseja sair?')) {
        // Usar função do auth-api para limpar tokens e redirecionar para login
        if (typeof logoutUser === 'function') {
            await logoutUser('../login.html');
        } else {
            // Fallback se logoutUser não estiver disponível
            try {
                await fetch('../api/users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            }
            window.location.href = '../login.html';
        }
    }
}

// ============================================
// SUSPENDER UTILIZADOR
// ============================================
function openSuspendModal(userId) {
    const days = prompt('Por quantos dias deseja suspender este utilizador?', '7');
    if (!days || isNaN(days) || days < 1) {
        alert('Número de dias inválido.');
        return;
    }
    
    const reason = prompt('Motivo da suspensão:');
    if (!reason || !reason.trim()) {
        alert('É necessário fornecer um motivo.');
        return;
    }
    
    suspendUser(userId, days, reason.trim());
}

async function suspendUser(userId, days, reason) {
    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'suspend_user',
                user_id: userId,
                days: parseInt(days),
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(`Utilizador suspenso por ${days} dias!`);
            await loadUsers();
            await loadStats();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao suspender utilizador:', error);
        alert('Erro ao suspender utilizador.');
    }
}

// ============================================
// REMOVER SUSPENSÃO
// ============================================
async function unsuspendUser(userId) {
    if (!confirm('Tem a certeza que deseja remover a suspensão deste utilizador?')) {
        return;
    }

    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'unsuspend_user',
                user_id: userId
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Suspensão removida com sucesso!');
            await loadUsers();
            await loadStats();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao remover suspensão:', error);
        alert('Erro ao remover suspensão.');
    }
}

// ============================================
// DAR AVISO AO UTILIZADOR
// ============================================
function openWarnModal(userId) {
    const reason = prompt('Motivo do aviso:');
    if (!reason || !reason.trim()) {
        alert('É necessário fornecer um motivo.');
        return;
    }
    
    warnUser(userId, reason.trim());
}

async function warnUser(userId, reason) {
    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'warn_user',
                user_id: userId,
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(`Aviso registado! Total de avisos: ${data.data.warning_count}`);
            await loadUsers();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao avisar utilizador:', error);
        alert('Erro ao avisar utilizador.');
    }
}

// ============================================
// APAGAR RECEITA COM MOTIVO
// ============================================
function confirmDeleteRecipeWithReason(recipeId) {
    const reason = prompt('Motivo da remoção da receita (opcional):');
    if (reason === null) return; // Cancelou
    
    confirmAction = () => deleteRecipeWithReason(recipeId, reason || 'Sem motivo especificado');
    document.getElementById('confirmTitle').textContent = 'Apagar Receita';
    document.getElementById('confirmMessage').textContent = 
        'Tem a certeza que deseja apagar esta receita? Esta ação não pode ser desfeita.';
    document.getElementById('confirmModal').classList.add('active');
}

async function deleteRecipeWithReason(recipeId, reason) {
    try {
        const response = await fetch('../api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_recipe',
                recipe_id: recipeId,
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Receita apagada com sucesso!');
            await loadRecipes();
            await loadStats();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao apagar receita:', error);
        alert('Erro ao apagar receita.');
    }
}
