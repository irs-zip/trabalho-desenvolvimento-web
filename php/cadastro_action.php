<?php
include 'conexao.php';
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = $_POST['senha'];

$sql = "INSERT INTO usuarios (nome, email, senha) VALUES ('$nome', '$email', '$senha')";
if (mysqli_query($conexao, $sql)) {
    echo "Cadastro realizado com sucesso!";
} else {
    echo "Erro: " . $sql . "<br>" . mysqli_error($conexao);
}
mysqli_close($conexao);
?>