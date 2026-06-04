document.addEventListener("DOMContentLoaded", function () {
    console.log("Sistema carregado. Conectando módulos...");

    // ==========================================
    // 1. CADASTRO DE USUÁRIO
    // ==========================================
    const formCadastro = document.getElementById("formCadastro");
    if (formCadastro) {
        formCadastro.addEventListener("submit", function (e) {
            e.preventDefault();
            const nome = document.getElementById("nome").value.trim();
            const email = document.getElementById("email").value.trim();
            const telefone = document.getElementById("telefone").value.trim();
            const cpf = document.getElementById("cpf").value.trim();
            const senha = document.getElementById("senha").value;
            const confirmarSenha = document.getElementById("confirmar_senha").value;

            if (!nome || !email || !telefone || !cpf || !senha || !confirmarSenha) {
                return alert("⚠️ Atenção: Todos os campos são obrigatórios!");
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                return alert("⚠️ Atenção: E-mail inválido.");
            }
            if (senha !== confirmarSenha) {
                return alert("⚠️ Atenção: As senhas não coincidem!");
            }

            fetch("../php/cadastro_action.php", { method: "POST", body: new FormData(formCadastro) })
                .then(res => res.json())
                .then(data => {
                    alert(data.mensagem);
                    if (data.sucesso) window.location.href = "../index.html";
                });
        });
    }

    // ==========================================
    // 2. LOGIN DE USUÁRIO
    // ==========================================
    const formLogin = document.getElementById("formLogin");
    if (formLogin) {
        formLogin.addEventListener("submit", function (e) {
            e.preventDefault();
            fetch("../php/login_action.php", { method: "POST", body: new FormData(formLogin) })
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) window.location.href = "php/painel.php";
                    else alert("⚠️ " + data.mensagem);
                });
        });
    }

// ==========================================
    // 3. PAINEL DO USUÁRIO
    // ==========================================
    // Agora o JS procura o 'display-nome' para saber se está no painel
    if (document.getElementById("display-nome")) {
        console.log("Painel detectado. Inicializando...");
        
        function carregarDadosIniciais() {
            // 1. Busca Perfil
            fetch("../php/perfil_action.php?acao=buscar")
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        // Preenche os textos com os dados do banco
                        document.getElementById("display-nome").innerText = data.dados.nome;
                        document.getElementById("display-email").innerText = data.dados.email;
                        document.getElementById("display-telefone").innerText = data.dados.telefone;
                    } else {
                        console.error("Erro ao puxar dados: ", data.mensagem);
                    }
                });

            // 2. Busca Chamados
            fetch("../php/chamados_action.php?acao=listar")
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById("tabela-chamados-body");
                    if (tbody && data.chamados) {
                        tbody.innerHTML = "";
                        data.chamados.forEach(c => {
                            let corBadge = c.status === 'Resolvido' ? 'bg-success' : 'bg-warning';
                            tbody.innerHTML += `
                                <tr>
                                    <td>#${c.id}</td>
                                    <td>${c.titulo}</td>
                                    <td>${c.departamento}</td>
                                    <td><span class="badge ${corBadge}">${c.status}</span></td>
                                    <td>${c.data_formatada}</td>
                                    <td><button class="btn btn-sm btn-outline-primary btn-editar" data-id="${c.id}" data-bs-toggle="modal" data-bs-target="#modalEditarChamado">Editar</button></td>
                                </tr>
                            `;
                        });
                    }
                });
        }
        
        // Dispara a função assim que o JS perceber que está no painel
        carregarDadosIniciais();

        // Submit de Novo Chamado (Esse formulário ainda existe, então mantemos)
        const formAbrirChamado = document.getElementById("form-abrir-chamado");
        if (formAbrirChamado) {
            formAbrirChamado.addEventListener("submit", function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('acao', 'cadastrar');
                fetch("../php/chamados_action.php", { method: "POST", body: formData })
                    .then(res => res.json())
                    .then(data => { 
                        alert(data.mensagem); 
                        carregarDadosIniciais(); // Atualiza a tabela
                        formAbrirChamado.reset(); // Limpa o formulário
                    });
            });
        }
    }
}); 