// ============================================
// ChefGuedes - API de Avaliações e Comentários
// Cliente JavaScript para interagir com o sistema de ratings
// ============================================

class RatingsAPI {
    constructor() {
        this.apiUrl = '../api/ratings.php';
    }

    /**
     * Obter token de sessão
     */
    getSessionToken() {
        return localStorage.getItem('chefguedes-session-token') || sessionStorage.getItem('chefguedes-session-token');
    }

    /**
     * Headers com autenticação (se disponível)
     */
    getHeaders() {
        const token = this.getSessionToken();
        const headers = {
            'Content-Type': 'application/json'
        };
        
        // Adicionar token apenas se existir
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        return headers;
    }

    /**
     * Obter avaliações e comentários de uma receita
     * @param {number} recipeId - ID da receita
     */
    async getRatingsAndComments(recipeId) {
        try {
            const url = `${this.apiUrl}?recipe_id=${recipeId}`;
            console.log('Carregando ratings de:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });

            const data = await response.json();
            console.log('Resposta da API:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }

            return data;
        } catch (error) {
            console.error('Erro ao carregar avaliações:', error);
            throw error;
        }
    }

    /**
     * Adicionar ou atualizar avaliação
     * @param {number} recipeId - ID da receita
     * @param {number} rating - Avaliação (1-5 estrelas)
     */
    async addRating(recipeId, rating) {
        try {
            const token = this.getSessionToken();
            console.log('Token disponível para rating:', !!token);
            console.log('Enviando avaliação:', { recipeId, rating });
            
            if (!token) {
                throw new Error('Faça login para avaliar receitas');
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    action: 'rate',
                    recipe_id: recipeId,
                    rating: rating
                })
            });

            console.log('Resposta status:', response.status);
            const data = await response.json();
            console.log('Resposta data:', data);
            
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            console.error('Erro ao avaliar:', error);
            throw error;
        }
    }

    /**
     * Adicionar comentário
     * @param {number} recipeId - ID da receita
     * @param {string} comment - Texto do comentário
     */
    async addComment(recipeId, comment) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    action: 'comment',
                    recipe_id: recipeId,
                    comment: comment
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            console.error('Erro ao adicionar comentário:', error);
            throw error;
        }
    }

    /**
     * Deletar comentário
     * @param {number} commentId - ID do comentário
     */
    async deleteComment(commentId) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    action: 'delete_comment',
                    comment_id: commentId
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            console.error('Erro ao deletar comentário:', error);
            throw error;
        }
    }

    /**
     * Obter infrações do utilizador
     */
    async getUserInfractions() {
        try {
            const response = await fetch(`${this.apiUrl}?user_infractions=true`, {
                method: 'GET',
                headers: this.getHeaders()
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }

            return data;
        } catch (error) {
            console.error('Erro ao carregar infrações:', error);
            throw error;
        }
    }
}

// ============================================
// UI Components para Ratings e Comentários
// ============================================

class RatingsUI {
    constructor(recipeId, containerId) {
        this.recipeId = recipeId;
        this.container = document.getElementById(containerId);
        this.api = new RatingsAPI();
        this.currentUserRating = null;
        this.userCommentCount = 0;
    }

    /**
     * Inicializar o componente
     */
    async init() {
        if (!this.container) {
            console.error('Container não encontrado');
            return;
        }
        
        console.log('Inicializando RatingsUI para receita:', this.recipeId);
        console.log('Token disponível:', !!this.getSessionToken());
        
        try {
            await this.loadRatingsAndComments();
            this.setupEventListeners();
        } catch (error) {
            console.error('Erro detalhado ao inicializar ratings:', error);
            
            let errorMessage = 'Sistema de avaliações temporariamente indisponível.';
            let detailMessage = 'Por favor, tente novamente mais tarde.';
            
            if (error.message.includes('Receita não encontrada')) {
                errorMessage = '⚠️ Esta receita não está na base de dados.';
                detailMessage = 'Apenas receitas criadas por utilizadores podem ser avaliadas. Receitas de exemplo não podem ser avaliadas.';
            } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                errorMessage = '⚠️ Erro de conexão com o servidor.';
                detailMessage = 'Verifique se o servidor está a funcionar corretamente.';
            } else if (!this.getSessionToken()) {
                detailMessage = 'Faça login para avaliar e comentar.';
            }
            
            this.container.innerHTML = `
                <div class="ratings-section">
                    <div style="text-align: center; padding: 2rem; background: var(--bg-secondary); border-radius: var(--border-radius);">
                        <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 0.5rem;">
                            ${errorMessage}
                        </p>
                        <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.5rem;">
                            ${detailMessage}
                        </p>
                        ${error.message ? `<p style="color: var(--text-light); font-size: 0.8rem; margin-top: 1rem; font-family: monospace;">Erro: ${error.message}</p>` : ''}
                    </div>
                </div>
            `;
        }
    }
    
    /**
     * Obter token de sessão
     */
    getSessionToken() {
        return localStorage.getItem('chefguedes-session-token') || sessionStorage.getItem('chefguedes-session-token');
    }

    /**
     * Carregar avaliações e comentários
     */
    async loadRatingsAndComments(preserveScroll = false) {
        try {
            // Salvar posição do scroll se necessário
            const scrollPosition = preserveScroll ? window.scrollY : null;
            
            const response = await this.api.getRatingsAndComments(this.recipeId);
            console.log('Resposta completa:', response);
            
            // A API retorna: { success: true, message: '...', data: { stats: {...}, comments: [...] } }
            const data = response.data || response;
            
            this.currentUserRating = data.user_rating;
            this.userCommentCount = data.user_comment_count;
            this.recipeAuthorId = data.recipe_author_id;
            this.currentUserId = data.current_user_id;
            this.isLoggedIn = !!this.getSessionToken();
            this.isOwner = this.currentUserId && (this.currentUserId === this.recipeAuthorId);
            
            this.render(data.stats, data.comments);
            
            // Restaurar posição do scroll
            if (preserveScroll && scrollPosition !== null) {
                window.scrollTo(0, scrollPosition);
            }
        } catch (error) {
            throw error;
        }
    }

    /**
     * Renderizar interface
     */
    render(stats, comments) {
        // Verificar se usuário não está logado
        if (!this.isLoggedIn) {
            this.container.innerHTML = `
                <div class="ratings-section">
                    <!-- Estatísticas de Avaliação (apenas visualização) -->
                    <div class="rating-stats">
                        <div class="rating-overview">
                            <div class="average-rating">
                                <span class="rating-number">${stats.average_rating.toFixed(1)}</span>
                                <div class="stars-display">
                                    ${this.renderStars(stats.average_rating, true)}
                                </div>
                                <span class="total-ratings">${stats.total_ratings} ${stats.total_ratings === 1 ? 'avaliação' : 'avaliações'}</span>
                            </div>
                            <div class="rating-breakdown">
                                ${this.renderRatingBar(5, stats.five_stars, stats.total_ratings)}
                                ${this.renderRatingBar(4, stats.four_stars, stats.total_ratings)}
                                ${this.renderRatingBar(3, stats.three_stars, stats.total_ratings)}
                                ${this.renderRatingBar(2, stats.two_stars, stats.total_ratings)}
                                ${this.renderRatingBar(1, stats.one_star, stats.total_ratings)}
                            </div>
                        </div>
                    </div>

                    <!-- Mensagem para fazer login -->
                    <div style="text-align: center; padding: 2rem; background: var(--bg-secondary); border-radius: var(--border-radius); margin-top: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Quer avaliar e comentar esta receita?</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Faça login para partilhar a sua opinião!</p>
                        <a href="../login.html" class="btn" style="display: inline-block; padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; text-decoration: none; border-radius: var(--border-radius);">Fazer Login</a>
                    </div>

                    <!-- Comentários (apenas visualização) -->
                    <div class="comments-section" style="margin-top: 2rem;">
                        <h3>Comentários (${comments.length})</h3>
                        <div class="comments-list" id="comments-list">
                            ${comments.length === 0 ? '<p class="no-comments">Ainda não há comentários. Seja o primeiro a comentar!</p>' : ''}
                            ${comments.map(comment => this.renderComment(comment)).join('')}
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        // Verificar se é o dono da receita
        if (this.isOwner) {
            this.container.innerHTML = `
                <div class="ratings-section">
                    <!-- Estatísticas de Avaliação -->
                    <div class="rating-stats">
                        <div class="rating-overview">
                            <div class="average-rating">
                                <span class="rating-number">${stats.average_rating.toFixed(1)}</span>
                                <div class="stars-display">
                                    ${this.renderStars(stats.average_rating, true)}
                                </div>
                                <span class="total-ratings">${stats.total_ratings} ${stats.total_ratings === 1 ? 'avaliação' : 'avaliações'}</span>
                            </div>
                            <div class="rating-breakdown">
                                ${this.renderRatingBar(5, stats.five_stars, stats.total_ratings)}
                                ${this.renderRatingBar(4, stats.four_stars, stats.total_ratings)}
                                ${this.renderRatingBar(3, stats.three_stars, stats.total_ratings)}
                                ${this.renderRatingBar(2, stats.two_stars, stats.total_ratings)}
                                ${this.renderRatingBar(1, stats.one_star, stats.total_ratings)}
                            </div>
                        </div>
                    </div>

                    <!-- Mensagem para o dono -->
                    <div style="text-align: center; padding: 2rem; background: #e3f2fd; border-radius: var(--border-radius); margin-top: 2rem; border: 2px solid #2196f3;">
                        <p style="color: #1976d2; font-size: 1.1rem;">
                            📝 Esta é a sua receita! Não pode avaliar ou comentar as suas próprias receitas.
                        </p>
                    </div>

                    <!-- Comentários (apenas visualização) -->
                    <div class="comments-section" style="margin-top: 2rem;">
                        <h3>Comentários (${comments.length})</h3>
                        <div class="comments-list" id="comments-list">
                            ${comments.length === 0 ? '<p class="no-comments">Ainda não há comentários.</p>' : ''}
                            ${comments.map(comment => this.renderComment(comment)).join('')}
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        // Usuário logado e não é o dono - mostrar tudo normalmente
        this.container.innerHTML = `
            <div class="ratings-section">
                <!-- Estatísticas de Avaliação -->
                <div class="rating-stats">
                    <div class="rating-overview">
                        <div class="average-rating">
                            <span class="rating-number">${stats.average_rating.toFixed(1)}</span>
                            <div class="stars-display">
                                ${this.renderStars(stats.average_rating, true)}
                            </div>
                            <span class="total-ratings">${stats.total_ratings} ${stats.total_ratings === 1 ? 'avaliação' : 'avaliações'}</span>
                        </div>
                        <div class="rating-breakdown">
                            ${this.renderRatingBar(5, stats.five_stars, stats.total_ratings)}
                            ${this.renderRatingBar(4, stats.four_stars, stats.total_ratings)}
                            ${this.renderRatingBar(3, stats.three_stars, stats.total_ratings)}
                            ${this.renderRatingBar(2, stats.two_stars, stats.total_ratings)}
                            ${this.renderRatingBar(1, stats.one_star, stats.total_ratings)}
                        </div>
                    </div>
                </div>

                <!-- Formulário de Avaliação -->
                <div class="user-rating-section">
                    <h3>Avaliar esta receita</h3>
                    <div class="stars-input" data-recipe-id="${this.recipeId}">
                        ${this.renderInteractiveStars()}
                    </div>
                    ${this.currentUserRating ? `<p class="current-rating-text">A sua avaliação: ${this.currentUserRating} ${this.currentUserRating === 1 ? 'estrela' : 'estrelas'}</p>` : ''}
                </div>

                <!-- Comentários -->
                <div class="comments-section">
                    <h3>Comentários (${comments.length})</h3>
                    
                    <!-- Formulário de Comentário -->
                    <div class="comment-form">
                        <textarea 
                            id="comment-input" 
                            class="comment-textarea" 
                            placeholder="Deixe o seu comentário... (máximo 2 comentários por receita)"
                            maxlength="1000"
                            ${this.userCommentCount >= 2 ? 'disabled' : ''}
                        ></textarea>
                        <div class="comment-form-footer">
                            <span class="char-counter">0/1000</span>
                            <button 
                                id="submit-comment-btn" 
                                class="btn-submit-comment"
                                type="button"
                                ${this.userCommentCount >= 2 ? 'disabled' : ''}
                            >
                                Enviar Comentário
                            </button>
                        </div>
                        ${this.userCommentCount >= 2 ? '<p class="comment-limit-warning">Atingiu o limite de 2 comentários nesta receita.</p>' : ''}
                    </div>

                    <!-- Lista de Comentários -->
                    <div class="comments-list" id="comments-list">
                        ${comments.length === 0 ? '<p class="no-comments">Ainda não há comentários. Seja o primeiro a comentar!</p>' : ''}
                        ${comments.map(comment => this.renderComment(comment)).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Renderizar estrelas (visualização estática)
     */
    renderStars(rating, showHalf = true) {
        let starsHtml = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = showHalf && (rating % 1 >= 0.5);
        
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                starsHtml += '<i class="fas fa-star star-filled"></i>';
            } else if (i === fullStars + 1 && hasHalfStar) {
                starsHtml += '<i class="fas fa-star-half-alt star-half"></i>';
            } else {
                starsHtml += '<i class="far fa-star star-empty"></i>';
            }
        }
        
        return starsHtml;
    }

    /**
     * Renderizar estrelas interativas
     */
    renderInteractiveStars() {
        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            const filled = this.currentUserRating && i <= this.currentUserRating;
            starsHtml += `<i class="far fa-star star-interactive ${filled ? 'star-selected' : ''}" data-rating="${i}"></i>`;
        }
        return starsHtml;
    }

    /**
     * Renderizar barra de rating
     */
    renderRatingBar(stars, count, total) {
        const percentage = total > 0 ? (count / total) * 100 : 0;
        return `
            <div class="rating-bar-row">
                <span class="rating-bar-label">${stars} <i class="fas fa-star"></i></span>
                <div class="rating-bar">
                    <div class="rating-bar-fill" style="width: ${percentage}%"></div>
                </div>
                <span class="rating-bar-count">${count}</span>
            </div>
        `;
    }

    /**
     * Renderizar comentário
     */
    renderComment(comment) {
        const currentUserId = localStorage.getItem('userId');
        const isOwner = currentUserId && parseInt(currentUserId) === comment.user_id;
        const commentDate = new Date(comment.created_at);
        const formattedDate = this.formatDate(commentDate);
        
        return `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-header">
                    <div class="comment-user">
                        <img src="${comment.profile_picture || '/images/default-avatar.png'}" alt="${comment.username}" class="comment-avatar">
                        <div class="comment-user-info">
                            <span class="comment-username">${comment.username}</span>
                            ${comment.rating ? `<div class="comment-user-rating">${this.renderStars(comment.rating, false)}</div>` : ''}
                        </div>
                    </div>
                    <div class="comment-meta">
                        <span class="comment-date">${formattedDate}</span>
                        ${isOwner ? `<button class="btn-delete-comment" data-comment-id="${comment.id}"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </div>
                <div class="comment-body">
                    <p>${this.escapeHtml(comment.comment)}</p>
                </div>
            </div>
        `;
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Avaliação por estrelas
        const stars = this.container.querySelectorAll('.star-interactive');
        stars.forEach(star => {
            star.addEventListener('click', async (e) => {
                const rating = parseInt(e.target.dataset.rating);
                await this.submitRating(rating);
            });
            
            star.addEventListener('mouseenter', (e) => {
                const rating = parseInt(e.target.dataset.rating);
                this.highlightStars(rating);
            });
        });

        const starsInput = this.container.querySelector('.stars-input');
        if (starsInput) {
            starsInput.addEventListener('mouseleave', () => {
                this.highlightStars(this.currentUserRating || 0);
            });
        }

        // Contador de caracteres
        const commentInput = document.getElementById('comment-input');
        const charCounter = this.container.querySelector('.char-counter');
        
        if (commentInput && charCounter) {
            commentInput.addEventListener('input', (e) => {
                const length = e.target.value.length;
                charCounter.textContent = `${length}/1000`;
            });
            
            // Adicionar handler para Enter com Ctrl (opcional)
            commentInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    const submitBtn = document.getElementById('submit-comment-btn');
                    if (submitBtn && !submitBtn.disabled) {
                        this.submitComment();
                    }
                }
            });
        }

        // Enviar comentário
        const submitBtn = document.getElementById('submit-comment-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.submitComment();
            });
        }

        // Deletar comentário
        const deleteButtons = this.container.querySelectorAll('.btn-delete-comment');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const commentId = e.currentTarget.dataset.commentId;
                await this.deleteComment(commentId);
            });
        });
    }

    /**
     * Highlight stars on hover
     */
    highlightStars(rating) {
        const stars = this.container.querySelectorAll('.star-interactive');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('star-hover');
            } else {
                star.classList.remove('star-hover');
            }
        });
    }

    /**
     * Submeter avaliação
     */
    async submitRating(rating) {
        try {
            console.log('=== SUBMETER AVALIAÇÃO ===');
            console.log('Rating:', rating);
            console.log('Token no localStorage:', localStorage.getItem('chefguedes-session-token') || sessionStorage.getItem('chefguedes-session-token'));
            
            if (!this.getSessionToken()) {
                this.showError('Faça login para avaliar receitas');
                return;
            }
            
            await this.api.addRating(this.recipeId, rating);
            this.currentUserRating = rating;
            await this.loadRatingsAndComments(true);
            this.showSuccess('Avaliação registada com sucesso! ⭐');
        } catch (error) {
            console.error('Erro detalhado:', error);
            this.showError(error.message || 'Erro ao registar avaliação');
        }
    }

    /**
     * Submeter comentário
     */
    async submitComment() {
        const commentInput = document.getElementById('comment-input');
        const submitBtn = document.getElementById('submit-comment-btn');
        const comment = commentInput.value.trim();

        if (!comment) {
            this.showError('Por favor, escreva um comentário.');
            return;
        }

        if (comment.length < 3) {
            this.showError('O comentário deve ter pelo menos 3 caracteres.');
            return;
        }

        try {
            // Desabilitar botão e mostrar loading
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando...';
            }
            
            await this.api.addComment(this.recipeId, comment);
            commentInput.value = '';
            
            // Resetar contador de caracteres
            const charCounter = this.container.querySelector('.char-counter');
            if (charCounter) {
                charCounter.textContent = '0/1000';
            }
            
            this.userCommentCount++;
            
            // Recarregar comentários
            await this.loadRatingsAndComments(true);
            
            this.showSuccess('Comentário adicionado com sucesso!');
        } catch (error) {
            this.showError(error.message);
            
            // Re-habilitar botão em caso de erro
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Comentário';
            }
        }
    }

    /**
     * Deletar comentário
     */
    async deleteComment(commentId) {
        if (!confirm('Tem a certeza que deseja remover este comentário?')) {
            return;
        }

        try {
            await this.api.deleteComment(commentId);
            this.userCommentCount--;
            await this.loadRatingsAndComments(true);
            this.showSuccess('Comentário removido com sucesso!');
        } catch (error) {
            this.showError(error.message);
        }
    }

    /**
     * Mostrar mensagem de sucesso
     */
    showSuccess(message) {
        // Implementar toast notification ou alert
        alert(message);
    }

    /**
     * Mostrar mensagem de erro
     */
    showError(message) {
        // Implementar toast notification ou alert
        alert(message);
    }

    /**
     * Formatar data
     */
    formatDate(date) {
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 7) {
            return date.toLocaleDateString('pt-PT');
        } else if (days > 0) {
            return `há ${days} ${days === 1 ? 'dia' : 'dias'}`;
        } else if (hours > 0) {
            return `há ${hours} ${hours === 1 ? 'hora' : 'horas'}`;
        } else if (minutes > 0) {
            return `há ${minutes} ${minutes === 1 ? 'minuto' : 'minutos'}`;
        } else {
            return 'agora mesmo';
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Exportar para uso global
window.RatingsAPI = RatingsAPI;
window.RatingsUI = RatingsUI;
