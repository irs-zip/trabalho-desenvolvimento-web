document.addEventListener("DOMContentLoaded", function () { // 1. Cadastro de Usuario, onde começa a brincadeira.
    const formCadastro = document.getElementById("formCadastro");
    if (formCadastro) {
        formCadastro.addEventListener("submit", function (e) {
            //2. Só vai enviar o formulario, se passar na validação visual do Bootstrap do site.
            if (!formCadastro.checkValidity()) {
                return;
            }
        e.preventDefault(); // Impede o carregamento da página.

        // 3. Capturar os dados dos inputs da pagina HTML.
            const formData = new FormData();
            formData.append("nome", document.getElementById("nome").value);
            // precisamos capturar pelo tipo de garantia, pois no nosso HTML está sem ID para colocar aqui.
            formData.append("email", formCadastro.querySelector('input[type="email"]').value);
            formData.append("telefone", document.getElementById("telefone").value);
            formData.append("cpf", document.getElementById("cpf").value);
            formData.append("senha", document.getElementById("senha").value);
  
            // 4. Envio dos dados para o PHP via AJAX o famoso "Fetch API"
            fetch("php/cadastro_action.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    alert("Cadastro realizado com sucesso!");
                    window.location.href = "login.html"; // 5. Comando que faz o redirecionamento para a tela de login
                } else {
                    alert("Erro no cadastro: " + data.mensagem);
                }
            })
            .catch(error => {
                console.error("Erro na requisição:", error);
                alert("Erro ao conectar com o servidor.");
            });
        });
    }

    // Ajax para o Login (6. Quando a tela de login for integrada claro.)
    const formLogin = document.getElementById("formLogin");
    if (formLogin) {
        formLogin.addEventListener("submit", function (e) {
            if (!formLogin.checkValidity()) {
                return;
            }
            e.preventDefault();

            const formData = new FormData(formLogin);

            fetch("php/login_action.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    window.location.href = "cadastro.html"; // Tela de chamados logada
                } else {
                    alert("Usuário ou senha incorretos: " + data.mensagem);
                }
            })
            .catch(error => {
                console.error("Erro na requisição:", error);
                alert("Erro ao conectar com o servidor.");
            });
        });
    }
});