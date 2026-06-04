document.addEventListener("DOMContentLoaded", function () {
    // Máscara de CPF (Adiciona os pontos e traço automaticamente)
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove o que não é número
            if (value.length > 11) value = value.slice(0, 11); // Limita a 11 números
            
            // Aplica a máscara: 000.000.000-00
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            
            e.target.value = value;
        });
    }
});