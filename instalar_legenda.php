<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function h_instalar_legenda($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$tipo = strtolower(trim((string)($_SESSION['tipo'] ?? '')));
$tipo = str_replace(
    ['á','à','ã','â','ä','é','ê','ë','í','ï','ó','ô','õ','ö','ú','ü','ç'],
    ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c'],
    $tipo
);

if (!isset($_SESSION['usuario']) || !in_array($tipo, ['admin', 'administrador', 'ipa'], true)) {
    http_response_code(403);
    echo 'Acesso negado. Entre como Admin primeiro.';
    exit;
}

$mensagens = [];
$erros = [];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS legendas_live (
        id INT NOT NULL AUTO_INCREMENT,
        codigo_sessao VARCHAR(32) NOT NULL,
        token_envio VARCHAR(64) NOT NULL,
        titulo VARCHAR(150) NULL,
        texto LONGTEXT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'aguardando',
        cor_fundo VARCHAR(20) NOT NULL DEFAULT 'rgba(0,0,0,0.72)',
        cor_texto VARCHAR(20) NOT NULL DEFAULT '#ffffff',
        tamanho_fonte INT NOT NULL DEFAULT 42,
        posicao VARCHAR(20) NOT NULL DEFAULT 'baixo',
        mostrar_caixa TINYINT(1) NOT NULL DEFAULT 1,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_por VARCHAR(100) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY codigo_sessao (codigo_sessao),
        KEY idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $mensagens[] = 'Tabela legendas_live criada/verificada.';
} catch (Exception $e) {
    $erros[] = 'Erro ao criar tabela legendas_live: ' . $e->getMessage();
}

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'pode_usar_legenda'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN pode_usar_legenda TINYINT(1) NOT NULL DEFAULT 0 AFTER pode_usar_projecao");
        $mensagens[] = 'Coluna pode_usar_legenda adicionada na tabela usuarios.';
    } else {
        $mensagens[] = 'Coluna pode_usar_legenda já existia.';
    }

    $pdo->exec("UPDATE usuarios SET pode_usar_legenda = 1 WHERE LOWER(tipo) IN ('admin','administrador','ipa')");
    $mensagens[] = 'Permissão de legenda liberada para Admins.';
} catch (Exception $e) {
    $erros[] = 'Erro ao ajustar permissões em usuarios: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Legenda</title>
    <style>
        body { font-family: Arial, sans-serif; background:#0d0f12; color:#fff; padding:24px; }
        .card { max-width:760px; margin:0 auto; background:#16191d; border:1px solid rgba(255,255,255,.08); border-radius:18px; padding:24px; }
        .ok { color:#00d084; }
        .erro { color:#ff6b6b; }
        a { color:#00a86b; font-weight:bold; }
        code { background:#0d0f12; padding:3px 6px; border-radius:6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Instalação da legenda ao vivo</h1>

        <?php foreach ($mensagens as $m): ?>
            <p class="ok">✅ <?php echo h_instalar_legenda($m); ?></p>
        <?php endforeach; ?>

        <?php foreach ($erros as $e): ?>
            <p class="erro">❌ <?php echo h_instalar_legenda($e); ?></p>
        <?php endforeach; ?>

        <?php if (!$erros): ?>
            <h2>Pronto.</h2>
            <p>Agora você já pode acessar <a href="legenda.php">legenda.php</a>.</p>
            <p><strong>Depois de confirmar que funcionou, apague o arquivo <code>instalar_legenda.php</code> do GitHub.</strong></p>
        <?php else: ?>
            <p>Corrija os erros acima e rode este instalador novamente.</p>
        <?php endif; ?>
    </div>
</body>
</html>
