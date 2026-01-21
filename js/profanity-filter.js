// ============================================
// ChefGuedes - Filtro de Palavras Inadequadas (Frontend)
// Validação de conteúdo no lado do cliente
// ============================================

/**
 * Lista de palavras proibidas em português e inglês
 * Validação adicional no cliente para feedback imediato
 */
const profanityList = [
    // Palavrões comuns em português (apenas palavras completas e inequívocas)
    'puta', 'caralho', 'foda', 'foder', 'merda', 'cagar', 
    'bosta', 'piça', 'pica', 'pila', 'cacete', 
    'cona', 'buceta', 'xoxota',
    'foda-se', 'fodase', 'foda se',
    
    // Palavrões em inglês
    'fuck', 'fucking', 'fucker', 'motherfucker', 'fucked',
    'shit', 'shitty', 'bullshit', 'shitter',
    'bitch', 'bitches', 'bitching', 'son of a bitch',
    'asshole', 'bastard',
    'pussy', 'cunt', 'dick', 'cock', 'prick', 'twat',
    'whore', 'slut', 'skank',
    
    // Insultos em português (palavras completas)
    'idiota', 'imbecil', 'estupido', 'estúpido',
    'otario', 'otário', 'fdp', 'filho da puta', 'filha da puta',
    'cabrao', 'cabrão', 'sacana', 'desgraçado', 'desgraçada',
    'monte de merda', 'vai te foder', 'vai-te foder', 'vtnc',
    'cretino', 'palhaço',
    
    // Insultos em inglês (apenas termos claramente ofensivos)
    'dumbass', 'jackass',
    'scumbag', 'dickhead', 'asshat', 'douchebag',
    'tosser', 'wanker',
    
    // Termos racistas e discriminatórios graves
    'nigga', 'nigger', 'niga', 'n1gga',
    'paneleiro', 'panasca', 'maricas', 'bicha',
    'retardado', 'mongoloide',
    'fag', 'faggot', 'dyke', 'tranny', 'chink', 'gook',
    'spic', 'wetback', 'beaner', 'kike', 'raghead',
    
    // Variações com caracteres especiais
    'p0rra', 'c@ralho', 'f0der', 'm3rda', 'put@',
    'c4ralho', 'f*ck', 'sh*t', 'b*tch', 'f**k',
    'n1gg@', 'n!gga', 's3x0', 'sex0', 'p!ca',
    'fvck', 'phuck', 'fuk', 'shyt', 'b1tch',
    
    // Abreviações vulgares
    'wtf', 'stfu', 'fds', 'vtnc', 'vsf', 'gtfo',
    'lmfao', 'omfg', 'pos', 'sob', 'mf'
];

/**
 * Remove acentos para normalização
 */
function removeAccents(str) {
    const accents = {
        'á': 'a', 'à': 'a', 'â': 'a', 'ã': 'a', 'ä': 'a',
        'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e',
        'í': 'i', 'ì': 'i', 'î': 'i', 'ï': 'i',
        'ó': 'o', 'ò': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'o',
        'ú': 'u', 'ù': 'u', 'û': 'u', 'ü': 'u',
        'ç': 'c', 'ñ': 'n',
        'Á': 'A', 'À': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A',
        'É': 'E', 'È': 'E', 'Ê': 'E', 'Ë': 'E',
        'Í': 'I', 'Ì': 'I', 'Î': 'I', 'Ï': 'I',
        'Ó': 'O', 'Ò': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'O',
        'Ú': 'U', 'Ù': 'U', 'Û': 'U', 'Ü': 'U',
        'Ç': 'C', 'Ñ': 'N'
    };
    
    return str.split('').map(char => accents[char] || char).join('');
}

/**
 * Verifica se o texto contém palavras inadequadas
 * @param {string} text - Texto a verificar
 * @returns {object} { isClean: boolean, foundWords: array }
 */
function checkProfanity(text) {
    if (!text || text.trim() === '') {
        return { isClean: true, foundWords: [] };
    }
    
    const foundWords = [];
    const normalizedText = removeAccents(text.toLowerCase());
    
    profanityList.forEach(word => {
        // Criar regex para detectar a palavra com limites
        const pattern = new RegExp(
            `(?:^|[\\s.,;!?"'\\(\\)\\[\\]{}])${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:$|[\\s.,;!?"'\\(\\)\\[\\]{}s])`,
            'i'
        );
        
        if (pattern.test(normalizedText)) {
            foundWords.push(word);
        }
    });
    
    return {
        isClean: foundWords.length === 0,
        foundWords: foundWords
    };
}

/**
 * Valida conteúdo de receita completo
 * @param {object} recipeData - Dados da receita
 * @returns {object} { isValid: boolean, errors: array }
 */
function validateRecipeContent(recipeData) {
    const errors = [];
    
    // Verificar título
    if (recipeData.title) {
        const titleCheck = checkProfanity(recipeData.title);
        if (!titleCheck.isClean) {
            errors.push({
                field: 'title',
                message: 'O título contém palavras inadequadas.'
            });
        }
    }
    
    // Verificar descrição
    if (recipeData.description) {
        const descCheck = checkProfanity(recipeData.description);
        if (!descCheck.isClean) {
            errors.push({
                field: 'description',
                message: 'A descrição contém palavras inadequadas.'
            });
        }
    }
    
    // Verificar ingredientes
    if (recipeData.ingredients) {
        const ingredCheck = checkProfanity(recipeData.ingredients);
        if (!ingredCheck.isClean) {
            errors.push({
                field: 'ingredients',
                message: 'Os ingredientes contêm palavras inadequadas.'
            });
        }
    }
    
    // Verificar instruções
    if (recipeData.instructions) {
        const instrCheck = checkProfanity(recipeData.instructions);
        if (!instrCheck.isClean) {
            errors.push({
                field: 'instructions',
                message: 'As instruções contêm palavras inadequadas.'
            });
        }
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Adiciona validação visual aos campos do formulário
 * @param {string} fieldId - ID do campo
 * @param {boolean} isValid - Se o campo é válido
 * @param {string} message - Mensagem de erro
 */
function showFieldValidation(fieldId, isValid, message = '') {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    // Remover classes anteriores
    field.classList.remove('field-valid', 'field-invalid');
    
    // Remover mensagem de erro anterior
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    if (!isValid) {
        field.classList.add('field-invalid');
        
        // Adicionar mensagem de erro
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        field.parentElement.appendChild(errorDiv);
    } else {
        field.classList.add('field-valid');
    }
}

/**
 * Valida campo em tempo real
 * @param {string} fieldId - ID do campo
 * @param {string} fieldName - Nome do campo para mensagem
 */
function validateFieldRealtime(fieldId, fieldName) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.addEventListener('input', function() {
        const check = checkProfanity(this.value);
        showFieldValidation(
            fieldId, 
            check.isClean, 
            check.isClean ? '' : `${fieldName} contém palavras inadequadas.`
        );
    });
}

// CSS para campos válidos/inválidos
if (!document.getElementById('profanity-filter-styles')) {
    const style = document.createElement('style');
    style.id = 'profanity-filter-styles';
    style.textContent = `
        .field-invalid {
            border-color: #e74c3c !important;
            background-color: #fff5f5 !important;
        }
        
        .field-valid {
            border-color: #27ae60 !important;
        }
        
        .field-error {
            animation: shake 0.3s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
}
