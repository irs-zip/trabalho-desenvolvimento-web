document.addEventListener("DOMContentLoaded", function () { // 1. Cadastro de Usuario, onde começa a brincadeira.
    const formCadastro = document.getElementById("formCadastro");
    if (formCadastro) {
        formCadastro.addEventListener("submit", function (e) {
            //2. Só vai enviar o formulario, se passar na validação visual do Bootstrap do site.
            if (!formCadastro.checkValidity()) {
                return;
            }
        e.preventDefault(); // Impede o carregamento da página.

        // Capturar os dados dos inputs da pagina HTML.
            const formData = new FormData();
            formData.append("nome", document.getElementById("nome").value);
            // precisamos capturar pelo tipo de garantia, pois no nosso HTML está sem ID para colocar aqui.
            formData.append("email", formCadastro.querySelector('input[type="email"]').value);
            formData.append("telefone", document.getElementById("telefone").value);
            formData.append("cpf", document.getElementById("cpf").value);
            formData.append("senha", document.getElementById("senha").value);
            )

        }
    }









}