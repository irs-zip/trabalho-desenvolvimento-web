<?php
session_start();
require_once 'conexao.php'; // Conecta ao PostgreSQL via PDO

header('Content-Type: application/json');

// Proteção: só aceita chamados de quem está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login.']);
    exit;
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

try {
    // ==========================================
    // 1. CADASTRAR NOVO CHAMADO
    // ==========================================
    if ($acao === 'cadastrar') {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $departamento = $_POST['departamento'] ?? '';
        $responsavel = $_POST['responsavel'] ?? '';
        $regiao = $_POST['regiao'] ?? '';
        $status = 'Em aberto'; // Exigência do PDF: padrão ao criar
        
        $sql = "INSERT INTO chamados (usuario_id, titulo, descricao, departamento, responsavel, regiao, status, data_hora) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $titulo, $descricao, $departamento, $responsavel, $regiao, $status]);
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado registrado com sucesso!']);
    } 
    // ==========================================
    // 2. LISTAR MEUS CHAMADOS
    // ==========================================
    elseif ($acao === 'listar') {
        // Busca os chamados do banco e formata a data para o padrão Brasileiro
        $sql = "SELECT id, titulo, departamento, status, TO_CHAR(data_hora, 'DD/MM/YYYY HH24:MI') as data_formatada 
                FROM chamados WHERE usuario_id = ? ORDER BY id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        $chamados = $stmt->fetchAll();
        
        echo json_encode(['sucesso' => true, 'chamados' => $chamados]);
    }
} catch (PDOException $e) {
    // Se o banco de dados chiar, o PHP avisa o JavaScript
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro SQL: ' . $e->getMessage()]);
}
?>