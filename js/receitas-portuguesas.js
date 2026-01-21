/* ============================================
   ChefGuedes - Receitas Portuguesas Geradas
   Sistema de receitas tradicionais portuguesas
   ============================================ */

// Base de dados de receitas portuguesas tradicionais
const receitasPortuguesas = [
    {
        id: 'rp001',
        titulo: 'Bacalhau à Brás',
        categoria: 'Prato Principal',
        tempo: '45 min',
        dificuldade: 'Média',
        descricao: 'Uma receita clássica portuguesa que combina bacalhau desfiado, batata palha crocante e ovos mexidos. Perfeita para ocasiões especiais ou um jantar em família.',
        imagem: 'images/receitas/bacalhau-bras.jpg',
        ingredientes: [
            '400g de bacalhau demolhado e desfiado',
            '500g de batata cortada em palha fina',
            '6 ovos',
            '2 cebolas grandes cortadas em rodelas finas',
            '4 dentes de alho picados',
            'Azeite virgem extra q.b.',
            'Azeitonas pretas para decorar',
            'Salsa fresca picada',
            'Sal e pimenta preta a gosto'
        ],
        preparo: [
            'Demolhe o bacalhau durante 24-48 horas, mudando a água várias vezes.',
            'Desfie o bacalhau em lascas, removendo espinhas e pele.',
            'Frite a batata palha em óleo bem quente até ficar dourada e crocante. Reserve sobre papel absorvente.',
            'Num tacho grande, refogue a cebola e o alho no azeite até ficarem translúcidos.',
            'Adicione o bacalhau desfiado e mexa bem durante 5 minutos.',
            'Junte a batata palha e misture delicadamente.',
            'Bata os ovos levemente e adicione ao tacho, mexendo rapidamente para criar ovos cremosos.',
            'Tempere com sal (cuidado, o bacalhau já é salgado) e pimenta.',
            'Decore com azeitonas pretas e salsa fresca picada.',
            'Sirva imediatamente bem quente.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp002',
        titulo: 'Caldo Verde Tradicional',
        categoria: 'Entrada',
        tempo: '40 min',
        dificuldade: 'Fácil',
        descricao: 'A sopa mais tradicional de Portugal, originária do Minho. Nutritiva, reconfortante e perfeita para os dias frios de inverno.',
        imagem: 'images/receitas/caldo-verde.jpg',
        ingredientes: [
            '500g de batatas',
            '300g de couve galega cortada em juliana fina',
            '150g de chouriço português',
            '1 cebola média',
            '2 dentes de alho',
            '1,5 litros de água',
            'Azeite virgem extra',
            'Sal q.b.'
        ],
        preparo: [
            'Descasque as batatas e corte em cubos médios.',
            'Pique a cebola e o alho finamente.',
            'Numa panela grande, refogue a cebola e o alho no azeite.',
            'Adicione as batatas e a água. Deixe cozinhar até as batatas ficarem macias (cerca de 20 minutos).',
            'Triture as batatas com a varinha mágica até obter um creme homogéneo.',
            'Adicione a couve cortada em juliana bem fina e deixe cozinhar por 5 minutos.',
            'Corte o chouriço em rodelas finas e frite numa frigideira à parte.',
            'Tempere o caldo com sal a gosto.',
            'Sirva bem quente com um fio de azeite cru e as rodelas de chouriço por cima.',
            'Acompanhe com broa de milho.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp003',
        titulo: 'Pastéis de Nata Caseiros',
        categoria: 'Sobremesa',
        tempo: '60 min',
        dificuldade: 'Média',
        descricao: 'O doce mais famoso de Portugal! Deliciosos pastéis com massa folhada crocante e creme cremoso. Uma receita que conquista o mundo.',
        imagem: 'images/receitas/pasteis-nata.webp',
        ingredientes: [
            '1 rolo de massa folhada',
            '300ml de leite',
            '200g de açúcar',
            '25g de farinha de trigo',
            '1 pau de canela',
            'Casca de 1 limão',
            '6 gemas de ovo',
            'Canela em pó para polvilhar'
        ],
        preparo: [
            'Pré-aqueça o forno a 250°C.',
            'Numa panela, aqueça o leite com a canela em pau e a casca de limão até ferver. Retire e deixe arrefecer.',
            'Noutra panela, faça uma calda com o açúcar e 100ml de água. Deixe atingir 110°C.',
            'Numa tigela, misture as gemas com a farinha até obter um creme liso.',
            'Retire a canela e o limão do leite. Adicione o leite ao creme de gemas, mexendo sempre.',
            'Junte a calda de açúcar e mexa bem até obter um creme homogéneo.',
            'Enrole a massa folhada e corte em rodelas de 2cm. Forre as forminhas de pastéis.',
            'Preencha 3/4 de cada forminha com o creme.',
            'Leve ao forno bem quente por 12-15 minutos até a superfície caramelizar.',
            'Retire, deixe arrefecer ligeiramente e polvilhe com canela. Sirva morno.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp004',
        titulo: 'Arroz de Marisco',
        categoria: 'Prato Principal',
        tempo: '50 min',
        dificuldade: 'Média',
        descricao: 'Um prato rico e saboroso que celebra os mariscos frescos da costa portuguesa. Perfeito para partilhar em família.',
        imagem: 'images/receitas/arroz-marisco.jpg',
        ingredientes: [
            '400g de arroz carolino',
            '500g de camarão',
            '300g de amêijoas',
            '200g de mexilhão',
            '2 santolas ou caranguejos pequenos',
            '2 tomates maduros',
            '1 cebola grande',
            '4 dentes de alho',
            '1 pimento vermelho',
            'Coentros frescos',
            'Azeite, sal e pimenta',
            '1 copo de vinho branco',
            'Caldo de peixe'
        ],
        preparo: [
            'Limpe bem todos os mariscos. Deixe as amêijoas em água com sal para libertar a areia.',
            'Numa panela grande, refogue a cebola, o alho e o pimento no azeite.',
            'Adicione o tomate picado e deixe refogar bem.',
            'Junte os mariscos e o vinho branco. Tape e deixe abrir.',
            'Retire os mariscos e reserve. Coe o caldo.',
            'No mesmo tacho, adicione o arroz e envolva no refogado.',
            'Adicione o caldo de peixe (cerca de 800ml) e deixe cozinhar.',
            'Quando o arroz estiver quase pronto (cerca de 15 minutos), adicione os mariscos novamente.',
            'Ajuste o tempero com sal e pimenta.',
            'Finalize com coentros frescos picados.',
            'Sirva bem quente com gotas de limão.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp005',
        titulo: 'Francesinha Autêntica',
        categoria: 'Prato Principal',
        tempo: '40 min',
        dificuldade: 'Média',
        descricao: 'O ícone gastronómico do Porto! Uma sandes generosa coberta com queijo derretido e um molho picante irresistível.',
        imagem: 'images/receitas/francesinha.png',
        ingredientes: [
            '8 fatias de pão de forma',
            '4 bifes finos de vaca',
            '4 salsichas frescas',
            '8 fatias de fiambre',
            '4 fatias de queijo',
            '200g de queijo ralado',
            '4 ovos',
            'Molho de francesinha (ver preparo)',
            'Batatas fritas para acompanhar'
        ],
        molho: [
            '500ml de cerveja',
            '200ml de molho de tomate',
            '100ml de whisky ou brandy',
            '2 cubos de caldo de carne',
            'Massa de pimentão',
            'Piri-piri a gosto',
            'Mostarda',
            'Manteiga'
        ],
        preparo: [
            'Prepare o molho: numa panela, misture a cerveja, molho de tomate, whisky, caldo de carne dissolvido, massa de pimentão, piri-piri e mostarda. Cozinhe em lume brando por 20 minutos. Adicione manteiga para dar cremosidade.',
            'Grelhe os bifes e as salsichas. Frite os ovos.',
            'Monte a francesinha: pão, fiambre, bife, salsicha, fiambre, pão.',
            'Coloque numa assadeira, cubra com fatias de queijo e queijo ralado.',
            'Leve ao forno ou gratinador até o queijo derreter completamente.',
            'Retire e coloque o ovo estrelado por cima.',
            'Verta o molho quente generosamente por cima.',
            'Sirva imediatamente com batatas fritas.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp006',
        titulo: 'Cozido à Portuguesa',
        categoria: 'Prato Principal',
        tempo: '120 min',
        dificuldade: 'Média',
        descricao: 'O prato mais tradicional e completo da gastronomia portuguesa. Uma explosão de sabores com carnes, enchidos e legumes.',
        imagem: 'images/receitas/cozido-portuguesa.png',
        ingredientes: [
            '500g de vaca (acém ou pá)',
            '500g de galinha',
            '200g de toucinho',
            '200g de chouriço de carne',
            '200g de morcela',
            '200g de farinheira',
            '4 batatas médias',
            '4 cenouras',
            '1 nabo',
            '1/2 couve portuguesa',
            '200g de grão-de-bico (demolhado)',
            '200g de arroz',
            'Sal q.b.'
        ],
        preparo: [
            'Demolhe o grão-de-bico na véspera.',
            'Numa panela grande, coloque água a ferver com sal.',
            'Adicione as carnes (vaca e galinha) e deixe cozinhar por 1 hora.',
            'Junte o grão-de-bico e cozinhe por mais 30 minutos.',
            'Adicione os enchidos (chouriço, morcela, farinheira) e o toucinho.',
            'Acrescente as batatas, cenouras e nabo cortados em pedaços grandes.',
            'Por último, adicione a couve cortada em tiras largas.',
            'Deixe cozinhar até todos os legumes ficarem macios (cerca de 30 minutos).',
            'Retire parte do caldo e cozinhe o arroz à parte.',
            'Sirva numa travessa grande com todas as carnes, legumes e grão.',
            'Acompanhe com o arroz e um fio do caldo do cozido.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp007',
        titulo: 'Açorda Alentejana',
        categoria: 'Prato Principal',
        tempo: '30 min',
        dificuldade: 'Fácil',
        descricao: 'Uma receita simples mas cheia de sabor, típica do Alentejo. Pão, alho, coentros e ovos escalfados numa combinação perfeita.',
        imagem: 'images/receitas/acorda-alentejana.avif',
        ingredientes: [
            '300g de pão alentejano do dia anterior',
            '6 ovos',
            '6 dentes de alho',
            '1 molho de coentros frescos',
            '1 litro de água a ferver',
            'Azeite virgem extra',
            'Sal q.b.'
        ],
        preparo: [
            'Corte o pão em fatias e depois em pedaços pequenos.',
            'Num pilão, esmague o alho com sal grosso até formar uma pasta.',
            'Pique os coentros finamente.',
            'Numa tigela grande, coloque o pão, o alho esmagado, os coentros e azeite generoso.',
            'Ferva a água com sal.',
            'Escalfe os ovos na água fervente (um de cada vez).',
            'Verta a água fervente sobre o pão e mexa bem até obter uma consistência cremosa.',
            'Coloque os ovos escalfados por cima.',
            'Regue com um generoso fio de azeite.',
            'Sirva imediatamente bem quente.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    },
    {
        id: 'rp008',
        titulo: 'Polvo à Lagareiro',
        categoria: 'Prato Principal',
        tempo: '60 min',
        dificuldade: 'Média',
        descricao: 'Uma especialidade portuguesa que combina polvo tenro com batatas assadas e muito azeite. Simplesmente delicioso!',
        imagem: 'images/receitas/polvo-lagareiro.jpg',
        ingredientes: [
            '1 polvo grande (1,5kg)',
            '800g de batatas',
            '8 dentes de alho',
            'Azeite virgem extra (abundante)',
            'Sal grosso',
            'Pimenta preta',
            'Vinagre',
            'Coentros ou salsa'
        ],
        preparo: [
            'Limpe bem o polvo e congele-o (se ainda não estiver). Descongele antes de usar.',
            'Numa panela grande, ferva água com sal e um fio de vinagre.',
            'Mergulhe o polvo 3 vezes na água fervente para encaracolar os tentáculos.',
            'Deixe cozer por 40-50 minutos até ficar macio.',
            'Enquanto isso, coza as batatas com pele até ficarem quase macias.',
            'Corte as batatas ao meio e coloque num tabuleiro.',
            'Escorra o polvo e corte os tentáculos.',
            'Coloque o polvo sobre as batatas.',
            'Esmague levemente os dentes de alho e espalhe.',
            'Regue generosamente com azeite e tempere com sal grosso e pimenta.',
            'Leve ao forno a 200°C por 15-20 minutos até dourar.',
            'Sirva bem quente com mais azeite por cima.'
        ],
        autor: 'ChefGuedes',
        destaque: true
    }
];

// Função para obter todas as receitas portuguesas
function getReceitasPortuguesas() {
    return receitasPortuguesas;
}

// Função para obter receitas em destaque (aleatórias)
function getReceitasDestaque(quantidade = 3) {
    const receitasEmDestaque = receitasPortuguesas.filter(r => r.destaque);
    // Embaralhar array
    const shuffled = receitasEmDestaque.sort(() => 0.5 - Math.random());
    // Retornar quantidade solicitada
    return shuffled.slice(0, quantidade);
}

// Função para obter receita por ID
function getReceitaById(id) {
    return receitasPortuguesas.find(r => r.id === id);
}

// Função para obter receita aleatória
function getReceitaAleatoria() {
    const randomIndex = Math.floor(Math.random() * receitasPortuguesas.length);
    return receitasPortuguesas[randomIndex];
}
