const formulario = document.getElementById('formulario');
formulario.addEventListener('submit', function (evento) {
    if (!formulario.checkValidity()) {
        evento.preventDefault();
        evento.stopPropagation();
    }
    formulario.classList.add('was-validated');
});
 
// fazer a validação do formulario de cadastro mudando dos alertas amarelos para bootstrap

/*
        document.getElementById('formCadastro').addEventListener('submit', function(e){
            e.preventDefault();

            let nome = document.getElementById('nome').value;
            let email = document.getElementById('email').value;
            let senha = document.getElementById('senha').value;
            let confirmar = document.getElementById('confirmarSenha').value;

            if(nome == "" || email == "" || senha == "" || confirmar == ""){
                alert("Preencha todos os campos");
                return;
            }

            if(senha != confirmar){
                alert("Senhas diferentes");
                return;
            }

            alert("Cadastro ok");

            window.location.href = "index.html";

        });
        */