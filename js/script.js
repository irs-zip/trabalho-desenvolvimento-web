//função para validar envio de formulario usando as classes do bootstrap
const formulario = document.getElementById('formulario'); // Seleciona o formulário pelo ID
formulario.addEventListener('input', function (evento) { // Adiciona um ouvinte de evento para o evento 'input' no formulário
    if (formulario.senha.value !== formulario.confirmarSenha.value) { 
        // Verifica se os valores dos campos de senha e confirmação de senha são diferentes 
        // (seria melhor usar validação por tamanho da senha ou outros critérios de segurança,
        // mas isso é um exemplo simples)
        evento.preventDefault();
        evento.stopPropagation();
        formulario.confirmarSenha.setCustomValidity('As senhas não coincidem');
        if (formulario.confirmarSenha.value === '') { // Verifica se o campo de confirmação de senha está vazio
            document.getElementById('confirm').innerHTML = 'Por favor, confirme sua senha.';
        } else{ 
            // Se o campo de confirmação de senha não estiver vazio, 
            // mas as senhas não coincidirem, exibe a mensagem de erro
            // (isso não deve ser usado num sistema sério, normalmente a validação de senha deve ser feita no backend, 
            // e não no frontend, para evitar manipulação do código)
            document.getElementById('confirm').innerHTML = 'As senhas não coincidem.';
        }
    } else {
        //reseta o erro de senhaa
        formulario.confirmarSenha.setCustomValidity('');
    }
});
// Adiciona um ouvinte de evento para o evento 'submit' no formulário
// para que ele possa ser validado usando as classes de validação do Bootstrap
// e evitar o envio do formulário se ele não for válido
formulario.addEventListener('submit', function (evento) {
    if (!formulario.checkValidity()) {
        evento.preventDefault();
        evento.stopPropagation();
    }

    formulario.classList.add('was-validated');
});

