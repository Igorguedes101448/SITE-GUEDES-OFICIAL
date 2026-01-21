<?php
// ============================================
// ChefGuedes - Filtro de Palavras Inadequadas
// Sistema de moderação de conteúdo
// ============================================

/**
 * Lista de palavras proibidas em português e inglês
 * Inclui palavrões, insultos e termos inadequados
 */
function getProfanityList() {
    return [
        // Palavrões comuns em português (apenas palavras completas e inequívocas)
        'puta', 'caralho', 'foda', 'foder', 'merda', 'cagar', 
        'bosta', 'piça', 'pica', 'pila', 'cacete', 
        'cona', 'buceta', 'xoxota',
        'foda-se', 'fodase', 'foda se',
        
        // Palavrões em inglês
        'fuck', 'fucking', 'fucker', 'motherfucker', 'fucked',
        'shit', 'shitty', 'bullshit', 'shitter',
        'bitch', 'bitches', 'bitching',
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
        'lmfao', 'omfg', 'pos', 'sob', 'mf',
    ];
}

/**
 * Verifica se o texto contém palavras inadequadas
 * 
 * @param string $text Texto a verificar
 * @return array ['isClean' => bool, 'foundWords' => array]
 */
function checkProfanity($text) {
    if (empty($text)) {
        return ['isClean' => true, 'foundWords' => []];
    }
    
    $profanityList = getProfanityList();
    $foundWords = [];
    
    // Normalizar texto para verificação
    $normalizedText = strtolower($text);
    $normalizedText = removeAccents($normalizedText);
    
    foreach ($profanityList as $word) {
        // Criar padrão regex para detectar a palavra INTEIRA com limites de palavra
        // Usa \b para limites de palavra para evitar falsos positivos
        $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
        
        if (preg_match($pattern, $normalizedText)) {
            $foundWords[] = $word;
        }
    }
    
    return [
        'isClean' => empty($foundWords),
        'foundWords' => $foundWords
    ];
}

/**
 * Remove acentos para normalização
 */
function removeAccents($string) {
    $accents = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N',
    ];
    
    return strtr($string, $accents);
}

/**
 * Valida conteúdo de receita completo
 * 
 * @param array $recipeData Dados da receita
 * @return array ['isValid' => bool, 'errors' => array]
 */
function validateRecipeContent($recipeData) {
    $errors = [];
    
    // Verificar título
    if (isset($recipeData['title'])) {
        $titleCheck = checkProfanity($recipeData['title']);
        if (!$titleCheck['isClean']) {
            $errors[] = [
                'field' => 'title',
                'message' => 'O título contém palavras inadequadas.',
                'words' => $titleCheck['foundWords']
            ];
        }
    }
    
    // Verificar descrição
    if (isset($recipeData['description'])) {
        $descCheck = checkProfanity($recipeData['description']);
        if (!$descCheck['isClean']) {
            $errors[] = [
                'field' => 'description',
                'message' => 'A descrição contém palavras inadequadas.',
                'words' => $descCheck['foundWords']
            ];
        }
    }
    
    // Verificar ingredientes
    if (isset($recipeData['ingredients'])) {
        $ingredCheck = checkProfanity($recipeData['ingredients']);
        if (!$ingredCheck['isClean']) {
            $errors[] = [
                'field' => 'ingredients',
                'message' => 'Os ingredientes contêm palavras inadequadas.',
                'words' => $ingredCheck['foundWords']
            ];
        }
    }
    
    // Verificar instruções
    if (isset($recipeData['instructions'])) {
        $instrCheck = checkProfanity($recipeData['instructions']);
        if (!$instrCheck['isClean']) {
            $errors[] = [
                'field' => 'instructions',
                'message' => 'As instruções contêm palavras inadequadas.',
                'words' => $instrCheck['foundWords']
            ];
        }
    }
    
    return [
        'isValid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Valida nome de grupo
 */
function validateGroupName($name) {
    $check = checkProfanity($name);
    return [
        'isValid' => $check['isClean'],
        'errors' => $check['isClean'] ? [] : ['O nome do grupo contém palavras inadequadas.']
    ];
}

/**
 * Valida comentário
 */
function validateComment($text) {
    $check = checkProfanity($text);
    return [
        'isValid' => $check['isClean'],
        'errors' => $check['isClean'] ? [] : ['O comentário contém palavras inadequadas.']
    ];
}
