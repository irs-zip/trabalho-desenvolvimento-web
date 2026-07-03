<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json; charset=utf-8');

function responder_json($dados, $codigo_http = 200) {
    http_response_code($codigo_http);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function limpar_texto_legenda($texto) {
    $texto = trim((string)$texto);
    $texto = preg_replace('/\s+/u', ' ', $texto);
    return mb_substr($texto, 0, 700, 'UTF-8');
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    if ($action === 'get') {
        $codigo = trim($_GET['sessao'] ?? '');
        if ($codigo === '') {
            responder_json(['ok' => false, 'erro' => 'Sessão não informada.'], 400);
        }

        $stmt = $pdo->prepare("SELECT codigo_sessao, titulo, texto, status, cor_fundo, cor_texto, tamanho_fonte, posicao, mostrar_caixa, ativo, atualizado_em FROM legendas_live WHERE codigo_sessao = :codigo LIMIT 1");
        $stmt->execute(['codigo' => $codigo]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sessao) {
            responder_json(['ok' => false, 'erro' => 'Sessão não encontrada.'], 404);
        }

        responder_json([
            'ok' => true,
            'sessao' => $sessao['codigo_sessao'],
            'titulo' => $sessao['titulo'],
            'texto' => $sessao['texto'] ?: '',
            'status' => $sessao['status'],
            'cor_fundo' => $sessao['cor_fundo'],
            'cor_texto' => $sessao['cor_texto'],
            'tamanho_fonte' => (int)$sessao['tamanho_fonte'],
            'posicao' => $sessao['posicao'],
            'mostrar_caixa' => (int)$sessao['mostrar_caixa'],
            'ativo' => (int)$sessao['ativo'],
            'atualizado_em' => $sessao['atualizado_em']
        ]);
    }

    if ($action === 'update') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder_json(['ok' => false, 'erro' => 'Use POST para atualizar.'], 405);
        }

        $codigo = trim($_POST['sessao'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $texto = limpar_texto_legenda($_POST['texto'] ?? '');
        $status = trim($_POST['status'] ?? 'ouvindo');

        if ($codigo === '' || $token === '') {
            responder_json(['ok' => false, 'erro' => 'Sessão/token não informados.'], 400);
        }

        $stmt = $pdo->prepare("SELECT id, token_envio FROM legendas_live WHERE codigo_sessao = :codigo AND ativo = 1 LIMIT 1");
        $stmt->execute(['codigo' => $codigo]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sessao || !hash_equals($sessao['token_envio'], $token)) {
            responder_json(['ok' => false, 'erro' => 'Token inválido.'], 403);
        }

        $stmt = $pdo->prepare("UPDATE legendas_live SET texto = :texto, status = :status, atualizado_em = NOW() WHERE id = :id");
        $stmt->execute([
            'texto' => $texto,
            'status' => mb_substr($status, 0, 30, 'UTF-8'),
            'id' => $sessao['id']
        ]);

        responder_json(['ok' => true]);
    }

    if ($action === 'clear') {
        $codigo = trim($_POST['sessao'] ?? '');
        $token = trim($_POST['token'] ?? '');

        if ($codigo === '' || $token === '') {
            responder_json(['ok' => false, 'erro' => 'Sessão/token não informados.'], 400);
        }

        $stmt = $pdo->prepare("SELECT id, token_envio FROM legendas_live WHERE codigo_sessao = :codigo LIMIT 1");
        $stmt->execute(['codigo' => $codigo]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sessao || !hash_equals($sessao['token_envio'], $token)) {
            responder_json(['ok' => false, 'erro' => 'Token inválido.'], 403);
        }

        $stmt = $pdo->prepare("UPDATE legendas_live SET texto = '', status = 'limpo', atualizado_em = NOW() WHERE id = :id");
        $stmt->execute(['id' => $sessao['id']]);

        responder_json(['ok' => true]);
    }

    responder_json(['ok' => false, 'erro' => 'Ação inválida.'], 400);
} catch (Exception $e) {
    responder_json(['ok' => false, 'erro' => 'Erro interno: ' . $e->getMessage()], 500);
}
