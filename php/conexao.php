<?php
// Configurações do banco de dados PostgreSQL
$host = 'localhost';
$port = '5432';               // Porta padrão do PostgreSQL
$dbname = 'sistema_chamados'; // Nome do banco que criamos no pgAdmin
$user = 'postgres';           // Usuário do PostgreSQL
$password = '7911';           // A sua senha local

try {
    // Montando a string de conexão (DSN) específica para PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    // Instanciando o objeto PDO (Requisito do trabalho)
    $pdo = new PDO($dsn, $user, $password);
    
    // Configurando o PDO para exibir os erros detalhados
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configurando para que as consultas retornem arrays associativos
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Se a conexão falhar, envia um JSON de erro para o AJAX do front-end exibir
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão com o banco: ' . $e->getMessage()]);
    exit;
}
?>