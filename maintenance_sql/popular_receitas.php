<?php
// ============================================
// Popular Base de Dados com Receitas Portuguesas
// ============================================

require_once 'api/db.php';

try {
    $db = getDB();
    
    // Verificar se já existem receitas
    $stmt = $db->query("SELECT COUNT(*) as total FROM recipes");
    $result = $stmt->fetch();
    
    echo "Receitas existentes na base de dados: " . $result['total'] . "\n\n";
    
    // Receitas Portuguesas
    $receitas = [
        [
            'title' => 'Bacalhau à Brás',
            'category' => 'Prato Principal',
            'subcategory' => 'Peixe',
            'description' => 'Uma receita clássica portuguesa que combina bacalhau desfiado, batata palha crocante e ovos mexidos. Perfeita para ocasiões especiais ou um jantar em família.',
            'ingredients' => "400g de bacalhau demolhado e desfiado\n500g de batata cortada em palha fina\n6 ovos\n2 cebolas grandes cortadas em rodelas finas\n4 dentes de alho picados\nAzeite virgem extra q.b.\nAzeitonas pretas para decorar\nSalsa fresca picada\nSal e pimenta preta a gosto",
            'instructions' => "Demolhe o bacalhau durante 24-48 horas, mudando a água várias vezes.\nDesfie o bacalhau em lascas, removendo espinhas e pele.\nFrite a batata palha em óleo bem quente até ficar dourada e crocante. Reserve sobre papel absorvente.\nNum tacho grande, refogue a cebola e o alho no azeite até ficarem translúcidos.\nAdicione o bacalhau desfiado e mexa bem durante 5 minutos.\nJunte a batata palha e misture delicadamente.\nBata os ovos levemente e adicione ao tacho, mexendo rapidamente para criar ovos cremosos.\nTempere com sal (cuidado, o bacalhau já é salgado) e pimenta.\nDecore com azeitonas pretas e salsa fresca picada.\nSirva imediatamente bem quente.",
            'prep_time' => 45,
            'cook_time' => 30,
            'servings' => 4,
            'difficulty' => 'Média'
        ],
        [
            'title' => 'Caldo Verde Tradicional',
            'category' => 'Entrada',
            'subcategory' => 'Sopa',
            'description' => 'A sopa mais tradicional de Portugal, originária do Minho. Nutritiva, reconfortante e perfeita para os dias frios de inverno.',
            'ingredients' => "500g de batatas\n1 couve galega\n200g de chouriço\n1 cebola\n2 dentes de alho\nAzeite virgem extra\nSal q.b.\n1,5 litros de água",
            'instructions' => "Descasque e corte as batatas em pedaços.\nNuma panela grande, ferva a água com sal.\nAdicione as batatas, a cebola picada e o alho.\nCozinhe até as batatas ficarem muito macias (cerca de 20 minutos).\nTriture tudo com a varinha mágica até obter um puré cremoso.\nLave bem a couve e corte em juliana muito fina.\nAdicione a couve ao caldo e deixe ferver por 5 minutos.\nCorte o chouriço em rodelas finas e frite ou asse.\nSirva o caldo bem quente com as rodelas de chouriço por cima.\nRegue com um fio generoso de azeite.",
            'prep_time' => 40,
            'cook_time' => 30,
            'servings' => 6,
            'difficulty' => 'Fácil'
        ],
        [
            'title' => 'Pastéis de Nata',
            'category' => 'Sobremesa',
            'subcategory' => 'Doce',
            'description' => 'Os famosos pastéis de Belém, um ícone da doçaria portuguesa. Massa folhada estaladiça com recheio cremoso de nata.',
            'ingredients' => "1 rolo de massa folhada\n300ml de nata\n200ml de leite\n150g de açúcar\n25g de farinha\n4 gemas\n1 pau de canela\nRaspa de limão\nCanela em pó para polvilhar",
            'instructions' => "Pré-aqueça o forno a 250°C.\nNuma panela, misture o açúcar, farinha, nata e leite.\nAdicione a canela em pau e a raspa de limão.\nLeve ao lume mexendo sempre até engrossar.\nRetire do lume e deixe arrefecer um pouco.\nBata as gemas e adicione ao creme morno, mexendo bem.\nEstenda a massa folhada e corte círculos.\nForre forminhas de pastéis com a massa.\nEncha cada pastel com o creme até 3/4.\nLeve ao forno por 15-20 minutos até a superfície caramelizar.\nPolvilhe com canela antes de servir.",
            'prep_time' => 60,
            'cook_time' => 20,
            'servings' => 12,
            'difficulty' => 'Média'
        ],
        [
            'title' => 'Francesinha',
            'category' => 'Prato Principal',
            'subcategory' => 'Carne',
            'description' => 'Especialidade do Porto, uma sanduíche única coberta com molho especial e queijo derretido. Irresistível!',
            'ingredients' => "4 fatias de pão de forma\n4 bifes finos\n4 salsichas frescas\n200g de fiambre\n200g de queijo flamengo\n4 ovos\nCerveja\nMolho de tomate\nCaldo de carne\nManteiga\nSal e pimenta",
            'instructions' => "Grelhe os bifes e as salsichas temperados com sal e pimenta.\nTorre o pão em manteiga.\nMonte a sanduíche: pão, bife, fiambre, salsicha, pão.\nCubra generosamente com fatias de queijo.\nPara o molho: refogue alho, adicione cerveja, molho tomate e caldo.\nDeixe reduzir até engrossar.\nFrite os ovos.\nColoque a francesinha num prato fundo.\nRegue com o molho quente.\nColoque o ovo estrelado por cima.\nSirva com batatas fritas.",
            'prep_time' => 45,
            'cook_time' => 30,
            'servings' => 4,
            'difficulty' => 'Média'
        ],
        [
            'title' => 'Arroz de Marisco',
            'category' => 'Prato Principal',
            'subcategory' => 'Marisco',
            'description' => 'Um arroz caldoso cheio de sabor do mar, com diversos mariscos frescos. Perfeito para ocasiões especiais.',
            'ingredients' => "400g de arroz\n500g de camarão\n500g de amêijoas\n200g de mexilhão\nLulas\n1 cebola\n4 tomates maduros\nCoentros frescos\nAzeite\nVinho branco\nCaldo de peixe\nAlho\nColorau",
            'instructions' => "Limpe bem todos os mariscos.\nNuma panela, abra as amêijoas e mexilhões com vinho branco.\nReserve o caldo e os mariscos.\nRefogue a cebola e alho picados no azeite.\nAdicione o tomate pelado e picado.\nJunte as lulas cortadas e deixe refogar.\nAdicione o arroz e envolva bem.\nJunte o caldo dos mariscos e caldo de peixe quente.\nCozinhe o arroz durante 15 minutos.\nAdicione os camarões e mariscos.\nFinalize com coentros frescos picados.\nSirva bem quente.",
            'prep_time' => 50,
            'cook_time' => 40,
            'servings' => 6,
            'difficulty' => 'Difícil'
        ],
        [
            'title' => 'Açorda Alentejana',
            'category' => 'Prato Principal',
            'subcategory' => 'Tradicional',
            'description' => 'Uma receita simples mas cheia de sabor, típica do Alentejo. Pão, alho, coentros e ovos escalfados numa combinação perfeita.',
            'ingredients' => "300g de pão alentejano do dia anterior\n6 ovos\n6 dentes de alho\n1 molho de coentros frescos\n1 litro de água a ferver\nAzeite virgem extra\nSal q.b.",
            'instructions' => "Corte o pão em fatias e depois em pedaços pequenos.\nNum pilão, esmague o alho com sal grosso até formar uma pasta.\nPique os coentros finamente.\nNuma tigela grande, coloque o pão, o alho esmagado, os coentros e azeite generoso.\nFerva a água com sal.\nEscalfe os ovos na água fervente (um de cada vez).\nVerta a água fervente sobre o pão e mexa bem até obter uma consistência cremosa.\nColoque os ovos escalfados por cima.\nRegue com um generoso fio de azeite.\nSirva imediatamente bem quente.",
            'prep_time' => 30,
            'cook_time' => 15,
            'servings' => 4,
            'difficulty' => 'Fácil'
        ],
        [
            'title' => 'Polvo à Lagareiro',
            'category' => 'Prato Principal',
            'subcategory' => 'Peixe',
            'description' => 'Uma especialidade portuguesa que combina polvo tenro com batatas assadas e muito azeite. Simplesmente delicioso!',
            'ingredients' => "1 polvo grande (1,5kg)\n800g de batatas\n8 dentes de alho\nAzeite virgem extra (abundante)\nSal grosso\nPimenta preta\nVinagre\nCoentros ou salsa",
            'instructions' => "Limpe bem o polvo e congele-o (se ainda não estiver). Descongele antes de usar.\nNuma panela grande, ferva água com sal e um fio de vinagre.\nMergulhe o polvo 3 vezes na água fervente para encaracolar os tentáculos.\nDeixe cozer por 40-50 minutos até ficar macio.\nEnquanto isso, coza as batatas com pele até ficarem quase macias.\nCorte as batatas ao meio e coloque num tabuleiro.\nEscorra o polvo e corte os tentáculos.\nColoque o polvo sobre as batatas.\nEsmague levemente os dentes de alho e espalhe.\nRegue generosamente com azeite e tempere com sal grosso e pimenta.\nLeve ao forno a 200°C por 15-20 minutos até dourar.\nSirva bem quente com mais azeite por cima.",
            'prep_time' => 60,
            'cook_time' => 50,
            'servings' => 4,
            'difficulty' => 'Média'
        ],
        [
            'title' => 'Cozido à Portuguesa',
            'category' => 'Prato Principal',
            'subcategory' => 'Carne',
            'description' => 'Um dos pratos mais emblemáticos de Portugal. Uma refeição completa e nutritiva que reúne carnes, enchidos e legumes.',
            'ingredients' => "500g de carne de vaca\n1 frango\n200g de toucinho\n200g de chouriço de carne\n200g de morcela\n200g de farinheira\n4 batatas médias\n4 cenouras\n1 nabo\n1/2 couve portuguesa\n200g de grão-de-bico\n200g de arroz\nSal q.b.",
            'instructions' => "Demolhe o grão-de-bico na véspera.\nNuma panela grande, coloque água a ferver com sal.\nAdicione as carnes (vaca e galinha) e deixe cozinhar por 1 hora.\nJunte o grão-de-bico e cozinhe por mais 30 minutos.\nAdicione os enchidos (chouriço, morcela, farinheira) e o toucinho.\nAcrescente as batatas, cenouras e nabo cortados em pedaços grandes.\nPor último, adicione a couve cortada em tiras largas.\nDeixe cozinhar até todos os legumes ficarem macios (cerca de 30 minutos).\nRetire parte do caldo e cozinhe o arroz à parte.\nSirva numa travessa grande com todas as carnes, legumes e grão.\nAcompanhe com o arroz e um fio do caldo do cozido.",
            'prep_time' => 120,
            'cook_time' => 90,
            'servings' => 8,
            'difficulty' => 'Difícil'
        ]
    ];
    
    $inserted = 0;
    
    foreach ($receitas as $receita) {
        // Verificar se receita já existe
        $stmt = $db->prepare("SELECT id FROM recipes WHERE title = ?");
        $stmt->execute([$receita['title']]);
        
        if (!$stmt->fetch()) {
            // Inserir receita
            $stmt = $db->prepare("
                INSERT INTO recipes (
                    title, category, subcategory, description, ingredients, 
                    instructions, prep_time, cook_time, servings, difficulty, 
                    visibility, is_draft, author_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'public', 0, 1)
            ");
            
            $stmt->execute([
                $receita['title'],
                $receita['category'],
                $receita['subcategory'] ?? null,
                $receita['description'],
                $receita['ingredients'],
                $receita['instructions'],
                $receita['prep_time'],
                $receita['cook_time'],
                $receita['servings'],
                $receita['difficulty']
            ]);
            
            echo "✓ Receita inserida: {$receita['title']}\n";
            $inserted++;
        } else {
            echo "→ Receita já existe: {$receita['title']}\n";
        }
    }
    
    echo "\n";
    echo "════════════════════════════════════════\n";
    echo "✓ Processo concluído!\n";
    echo "✓ Receitas inseridas: $inserted\n";
    echo "════════════════════════════════════════\n";
    
    // Contar total final
    $stmt = $db->query("SELECT COUNT(*) as total FROM recipes");
    $result = $stmt->fetch();
    echo "\n✓ Total de receitas na base de dados: " . $result['total'] . "\n";
    
} catch (PDOException $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
