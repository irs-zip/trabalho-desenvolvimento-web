//função para validar envio de formulario usando as classes do bootstrap
const formulario = document.getElementById('formulario');
formulario.addEventListener('input', function (evento) {
    if (formulario.senha.value !== formulario.confirmarSenha.value) {
        evento.preventDefault();
        evento.stopPropagation();
        formulario.confirmarSenha.setCustomValidity('As senhas não coincidem');
        if (formulario.confirmarSenha.value === '') {
            document.getElementById('confirm').innerHTML = 'Por favor, confirme sua senha.';
        } else{
            document.getElementById('confirm').innerHTML = 'As senhas não coincidem.';
        }
    } else {
        formulario.confirmarSenha.setCustomValidity('');
    }
});
formulario.addEventListener('submit', function (evento) {
    if (!formulario.checkValidity()) {
        evento.preventDefault();
        evento.stopPropagation();
    }

    formulario.classList.add('was-validated');
});

