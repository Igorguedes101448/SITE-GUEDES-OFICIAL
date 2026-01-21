&lt;!DOCTYPE html&gt;
&lt;html lang="pt"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;Debug - Criar Grupo&lt;/title&gt;
    &lt;style&gt;
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            background: #ff6b35;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #e55a2b;
        }
        #debug {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
            white-space: pre-wrap;
        }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;Debug - Criar Grupo&lt;/h1&gt;
    
    &lt;form id="testForm"&gt;
        &lt;div class="form-group"&gt;
            &lt;label for="groupName"&gt;Nome do Grupo:&lt;/label&gt;
            &lt;input type="text" id="groupName" value="familia guedes" required&gt;
        &lt;/div&gt;
        &lt;div class="form-group"&gt;
            &lt;label for="groupDescription"&gt;Descrição:&lt;/label&gt;
            &lt;textarea id="groupDescription" rows="3"&gt;receitas tops&lt;/textarea&gt;
        &lt;/div&gt;
        &lt;button type="submit"&gt;Criar Grupo&lt;/button&gt;
    &lt;/form&gt;
    
    &lt;div id="debug"&gt;Aguardando requisição...&lt;/div&gt;

    &lt;script src="js/auth-api.js"&gt;&lt;/script&gt;
    &lt;script src="js/main-api.js"&gt;&lt;/script&gt;
    &lt;script&gt;
        const debugDiv = document.getElementById('debug');
        
        function logDebug(message) {
            debugDiv.innerHTML += '\n' + message;
            console.log(message);
        }
        
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            debugDiv.innerHTML = 'Iniciando teste...\n';
            
            // Verificar se está logado
            const sessionToken = getSessionToken();
            logDebug('Session Token: ' + (sessionToken ? 'Presente' : 'AUSENTE'));
            
            if (!sessionToken) {
                logDebug('ERRO: Utilizador não está logado!');
                alert('Por favor, faça login primeiro em: ' + window.location.origin + '/siteguedes/login.html');
                return;
            }
            
            const groupData = {
                name: document.getElementById('groupName').value,
                description: document.getElementById('groupDescription').value
            };
            
            logDebug('Dados do grupo: ' + JSON.stringify(groupData, null, 2));
            
            // Testar a API diretamente
            try {
                logDebug('\nEnviando requisição para API...');
                
                const response = await fetch(`${API_BASE}/groups.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create',
                        sessionToken: sessionToken,
                        ...groupData
                    })
                });
                
                logDebug('Status da resposta: ' + response.status);
                logDebug('Status text: ' + response.statusText);
                
                const contentType = response.headers.get('content-type');
                logDebug('Content-Type: ' + contentType);
                
                const text = await response.text();
                logDebug('\nResposta (texto):\n' + text);
                
                try {
                    const result = JSON.parse(text);
                    logDebug('\nResposta (JSON):\n' + JSON.stringify(result, null, 2));
                    
                    if (result.success) {
                        logDebug('\n✅ SUCESSO! Grupo criado com ID: ' + result.data.group.id);
                        alert('Grupo criado com sucesso!');
                    } else {
                        logDebug('\n❌ ERRO: ' + result.message);
                        alert('Erro: ' + result.message);
                    }
                } catch (parseError) {
                    logDebug('\n❌ ERRO ao fazer parse do JSON: ' + parseError.message);
                }
                
            } catch (error) {
                logDebug('\n❌ ERRO na requisição: ' + error.message);
                alert('Erro: ' + error.message);
            }
        });
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;
