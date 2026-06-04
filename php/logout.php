<?php
// Inicia a sessão para poder destruí-la
session_start();

// Destrói todas as variáveis de sessão (desloga o usuário)
session_unset();
session_destroy();

// Redireciona o usuário de volta para a tela de login (que está uma pasta atrás)
header("Location: ../index.html");
exit;
?>