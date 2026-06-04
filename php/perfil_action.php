<?php
// Inicia a sessão para identificar o usuário logado
session_start();

// Importa a conexão com o banco de dados (PDO PostgreSQL)
require_once 'conexao.php'; 

// Avisa ao navegador que a resposta será no formato JSON
header('Content-Type: application/json');

// Proteção de segurança: se não houver sessão, bloqueia a execução
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

// Pega o ID do usuário logado na sessão
$uid = $_SESSION['usuario_id'];

// Identifica qual ação o JavaScript está pedindo (GET para buscar, POST para atualizar)
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    // =======================================================
    // 1. LER DADOS (Exibir os dados do usuário logado)
    // =======================================================
    if ($acao === 'buscar') {
        // Busca nome, email e telefone no banco de dados
        $sql = "SELECT nome, email, telefone FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid]);
        $dados = $stmt->fetch();

        if ($dados) {
            echo json_encode(['sucesso' => true, 'dados' => $dados]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado no banco.']);
        }
    } 
    // =======================================================
    // 2. ATUALIZAR DADOS (Permitir a edição dos dados pessoais)
    // =======================================================
    elseif ($acao === 'atualizar') {
        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');

        // Validação básica no backend
        if (empty($nome) || empty($telefone)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nome e telefone não podem ficar em branco.']);
            exit;
        }

        // Executa o UPDATE no PostgreSQL
        $sql = "UPDATE usuarios SET nome = ?, telefone = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $telefone, $uid]);

        // Atualiza a sessão para que o nome mude na barra superior imediatamente
        $_SESSION['usuario_nome'] = $nome;

        echo json_encode(['sucesso' => true, 'mensagem' => 'Seus dados foram atualizados com sucesso!']);
    } 
    else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não reconhecida pelo sistema.']);
    }

} catch (PDOException $e) {
    // Captura qualquer erro de banco de dados e envia para o front-end
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>