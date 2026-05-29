//função para validar envio de formulario usando as classes do bootstrap
const formulario = document.getElementById('formulario');
formulario.addEventListener('submit', function (evento) {
    if (!formulario.checkValidity()) {
        evento.preventDefault();
        evento.stopPropagation();
    }
    if (formulario.senha.value !== formulario.confirmarSenha.value) {
        evento.preventDefault();
        evento.stopPropagation();
        formulario.confirmarSenha.setCustomValidity('As senhas não coincidem');
    } {
        formulario.classList.add('was-validated');
    }
});

