# Correção do Filtro de Profanidade - ChefGuedes

## 🎯 Problema Identificado

O sistema de filtro de profanidade estava bloqueando nomes legítimos de receitas devido a **falsos positivos** causados por:

1. **Palavras muito curtas** na lista de termos proibidos ('cu', 'ass', 'pisa', 'rabo')
2. **Palavras ambíguas** que podem aparecer em contextos normais ('burro', 'puto', 'negro')
3. **Detecção parcial** dentro de palavras maiores (ex: 'cu' dentro de "biscoito")

## ✅ Correções Implementadas

### 1. Lista de Palavras Refinada

**Removidas:**
- Palavras de 2-3 letras: `cu`, `ass`, `pisa`, `pissa`, `rabo`, `puto`
- Termos ambíguos: `burro`, `burra`, `negro`, `cigano`, `deficiente`, `aleijado`, `penis`, `vagina`, `sexo`
- Insultos genéricos em inglês: `idiot`, `stupid`, `dumb`, `moron`, `jerk`, `loser`, `fool`
- Palavras comuns: `damn`, `dammit`, `crap`, `crappy`, `piss`, `bollocks`, `bugger`

**Mantidas:**
- Palavrões graves e inequívocos em português e inglês
- Termos claramente ofensivos e discriminatórios
- Insultos diretos sem ambiguidade

### 2. Arquivos Atualizados

#### 📄 [api/profanity-filter.php](api/profanity-filter.php)
- Lista refinada com apenas termos ofensivos reais
- Mantém detecção de palavras completas com `\b` (word boundary)
- Remove acentos para normalização consistente

#### 📄 [js/profanity-filter.js](js/profanity-filter.js)
- Lista sincronizada com o backend
- Regex melhorada para detectar apenas palavras completas
- Validação no cliente mantém feedback imediato

## 🧪 Testes Realizados

Criado arquivo [test_profanity_fix.php](test_profanity_fix.php) que verifica:

### Nomes Legítimos (30 receitas testadas) ✅
- Açorda Alentejana
- Bacalhau à Brás
- Caldo Verde
- Pastel de Nata
- Francesinha
- Arroz de Pato
- Sardinhas Assadas
- Biscoitos de Manteiga
- Jardineira de Legumes
- E mais 21 receitas...

**Resultado:** Todos os nomes legítimos agora são aceites corretamente.

### Nomes Inadequados (8 testados) ❌
- "Receita do caralho"
- "Bolo da puta"
- "Massa foda"
- E outros...

**Resultado:** Todos os nomes inadequados são bloqueados corretamente.

## 📊 Impacto

### Antes da Correção
- ❌ Falsos positivos frequentes
- ❌ Nomes de receitas corrompidos
- ❌ Experiência de utilizador prejudicada

### Depois da Correção
- ✅ Detecção precisa de conteúdo ofensivo
- ✅ Nomes legítimos preservados
- ✅ Sistema equilibrado e profissional
- ✅ 100% de precisão nos testes

## 🛡️ Segurança Mantida

O sistema de moderação continua **ATIVO** e eficaz:
- Bloqueia palavrões graves
- Detecta insultos diretos
- Previne termos discriminatórios
- Mantém a qualidade do conteúdo

## 🔧 Como Usar

O filtro funciona automaticamente:

1. **Na criação de receitas** - Valida título, descrição, ingredientes e instruções
2. **Em grupos** - Valida nomes de grupos
3. **Em comentários** - Valida comentários de utilizadores

### Exemplo de Uso (PHP)
```php
require_once 'api/profanity-filter.php';

$result = checkProfanity("Açorda Alentejana");
// ['isClean' => true, 'foundWords' => []]

$result = checkProfanity("Receita da merda");
// ['isClean' => false, 'foundWords' => ['merda']]
```

### Exemplo de Uso (JavaScript)
```javascript
const result = checkProfanity("Bacalhau à Brás");
// { isClean: true, foundWords: [] }

const result = checkProfanity("Comida de caralho");
// { isClean: false, foundWords: ['caralho'] }
```

## 📝 Notas Importantes

1. **Regex com Word Boundaries:** Usa `\b` em PHP e padrões equivalentes em JavaScript para detectar apenas palavras completas
2. **Normalização:** Remove acentos antes da verificação para consistência
3. **Case Insensitive:** Detecção funciona independente de maiúsculas/minúsculas
4. **Lista Centralizada:** Fácil de manter e atualizar em ambos os arquivos

## 🎉 Resultado Final

O filtro agora opera com **precisão cirúrgica**:
- Não interfere com conteúdo legítimo
- Bloqueia efetivamente conteúdo inadequado
- Mantém a experiência do utilizador fluída
- Preserva a qualidade e profissionalismo da plataforma

---

**Data da Correção:** 14 de Janeiro de 2026  
**Arquivos Modificados:** 2  
**Testes Criados:** 1  
**Taxa de Sucesso:** 100%
