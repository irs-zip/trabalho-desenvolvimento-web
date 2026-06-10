<?php
// Configurações do banco de dados PostgreSQL
// No Docker, o host é o nome do container "db" (definido no docker-compose.yml)
$host = 'db';
$port = '5432';
$dbname = 'sistema_chamados';
$user =  'postgres';
$password =  '7911';

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