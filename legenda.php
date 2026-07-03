<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function normalizar_tipo_legenda($valor) {
    $valor = strtolower(trim((string)$valor));
    return str_replace(
        ['á','à','ã','â','ä','é','ê','ë','í','ï','ó','ô','õ','ö','ú','ü','ç'],
        ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c'],
        $valor
    );
}

$usuario = $_SESSION['usuario'] ?? null;
$tipo_logado = normalizar_tipo_legenda($_SESSION['tipo'] ?? '');
$eh_admin = $usuario && in_array($tipo_logado, ['admin', 'administrador', 'ipa'], true);
$pode_usar_legenda = $eh_admin || !empty($_SESSION['pode_usar_legenda']);

if (!$usuario || !$pode_usar_legenda) {
    header('Location: login.php');
    exit;
}

unset($_SESSION['fluxo_publico'], $_SESSION['playlist_code_atual']);

if (empty($_SESSION['csrf_legenda'])) {
    $_SESSION['csrf_legenda'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_legenda'];

function gerar_codigo_legenda($tamanho = 12) {
    return bin2hex(random_bytes((int)ceil($tamanho / 2)));
}

function base_url_site_legenda() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . ($dir === '' ? '' : $dir);
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf = $_POST['csrf'] ?? '';
    $acao = $_POST['acao'] ?? '';

    if (!hash_equals($csrf, $token_csrf)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        try {
            if ($acao === 'nova_sessao') {
                $codigo = gerar_codigo_legenda(12);
                $token = gerar_codigo_legenda(32);
                $titulo = trim($_POST['titulo'] ?? 'Legenda do Culto');
                if ($titulo === '') $titulo = 'Legenda do Culto';

                $stmt = $pdo->prepare("INSERT INTO legendas_live (codigo_sessao, token_envio, titulo, texto, status, criado_por, atualizado_em) VALUES (:codigo, :token, :titulo, '', 'aguardando', :usuario, NOW())");
                $stmt->execute([
                    'codigo' => $codigo,
                    'token' => $token,
                    'titulo' => mb_substr($titulo, 0, 150, 'UTF-8'),
                    'usuario' => $usuario
                ]);

                header('Location: legenda.php?sessao=' . urlencode($codigo));
                exit;
            }

            if ($acao === 'configurar') {
                $codigo = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['sessao'] ?? '');
                $cor_fundo = trim($_POST['cor_fundo'] ?? 'rgba(0,0,0,0.72)');
                $cor_texto = trim($_POST['cor_texto'] ?? '#ffffff');
                $tamanho_fonte = max(20, min(100, intval($_POST['tamanho_fonte'] ?? 42)));
                $posicao = in_array($_POST['posicao'] ?? 'baixo', ['baixo', 'meio', 'cima'], true) ? $_POST['posicao'] : 'baixo';
                $mostrar_caixa = isset($_POST['mostrar_caixa']) ? 1 : 0;

                $stmt = $pdo->prepare("UPDATE legendas_live SET cor_fundo = :cor_fundo, cor_texto = :cor_texto, tamanho_fonte = :tamanho_fonte, posicao = :posicao, mostrar_caixa = :mostrar_caixa, atualizado_em = NOW() WHERE codigo_sessao = :codigo");
                $stmt->execute([
                    'cor_fundo' => mb_substr($cor_fundo, 0, 20, 'UTF-8'),
                    'cor_texto' => mb_substr($cor_texto, 0, 20, 'UTF-8'),
                    'tamanho_fonte' => $tamanho_fonte,
                    'posicao' => $posicao,
                    'mostrar_caixa' => $mostrar_caixa,
                    'codigo' => $codigo
                ]);
                $mensagem = 'Configuração salva.';
            }

            if ($acao === 'limpar') {
                $codigo = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['sessao'] ?? '');
                $stmt = $pdo->prepare("UPDATE legendas_live SET texto = '', status = 'limpo', atualizado_em = NOW() WHERE codigo_sessao = :codigo");
                $stmt->execute(['codigo' => $codigo]);
                $mensagem = 'Legenda limpa.';
            }

            if ($acao === 'encerrar') {
                $codigo = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['sessao'] ?? '');
                $stmt = $pdo->prepare("UPDATE legendas_live SET ativo = 0, status = 'encerrado', atualizado_em = NOW() WHERE codigo_sessao = :codigo");
                $stmt->execute(['codigo' => $codigo]);
                $mensagem = 'Sessão encerrada.';
            }
        } catch (Exception $e) {
            $erro = 'Erro: ' . $e->getMessage();
        }
    }
}

$sessao_codigo = isset($_GET['sessao']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['sessao']) : '';
$aba_atual = in_array($_GET['aba'] ?? 'painel', ['painel', 'navegador', 'python'], true) ? $_GET['aba'] : 'painel';
$sessao = null;
$sessoes = [];

try {
    if ($sessao_codigo) {
        $stmt = $pdo->prepare("SELECT * FROM legendas_live WHERE codigo_sessao = :codigo LIMIT 1");
        $stmt->execute(['codigo' => $sessao_codigo]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $stmt = $pdo->query("SELECT codigo_sessao, titulo, status, ativo, atualizado_em, criado_em FROM legendas_live ORDER BY id DESC LIMIT 10");
    $sessoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $erro = 'Não consegui carregar a tabela de legendas. Rode o instalador/SQL da legenda primeiro.';
}

$base = base_url_site_legenda();
$overlay_url = $sessao ? $base . '/legenda_overlay.php?sessao=' . urlencode($sessao['codigo_sessao']) : '';
$comando_python = $sessao ? 'python legenda_live_site.py --site "' . $base . '" --sessao "' . $sessao['codigo_sessao'] . '" --token "' . $sessao['token_envio'] . '" --model small' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Legenda ao vivo - Repertório Aliança</title>
    <link rel="icon" type="image/png" href="Igreja presbiteriana aliança sem fundo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-principal:#f8f9fa; --bg-card:#ffffff; --texto-principal:#1a1a1a; --texto-secundario:#65676b; --verde-presbiteriano:#00563B; --borda-suave:rgba(0,0,0,.08); --sombras:0 4px 20px rgba(0,0,0,.05); --bg-nav:#1e2227; --input-bg:#ffffff; }
        [data-bs-theme="dark"] { --bg-principal:#0d0f12; --bg-card:#16191d; --texto-principal:#ffffff; --texto-secundario:#989faf; --verde-presbiteriano:#00a86b; --borda-suave:rgba(255,255,255,.08); --sombras:0 10px 30px rgba(0,0,0,.4); --bg-nav:#14171a; --input-bg:#111418; }
        * { box-sizing:border-box; }
        body { font-family:Inter,sans-serif; background:radial-gradient(circle at top center, rgba(0,168,107,.08), transparent 34rem), var(--bg-principal); color:var(--texto-principal); min-height:100vh; }
        .nav-corporate-bar { background:rgba(20,23,26,.94); border-bottom:1px solid var(--borda-suave); box-shadow:var(--sombras); padding:.55rem 0; backdrop-filter:blur(12px); }
        .nav-corporate-container { max-width:1120px; margin:0 auto; padding:0 1rem; display:flex; justify-content:space-between; align-items:center; gap:1rem; }
        .brand-logo-zone { display:inline-flex; align-items:center; gap:.65rem; color:#fff!important; text-decoration:none; font-family:Syne,sans-serif; font-weight:800; }
        .brand-logo-zone img { height:32px; object-fit:contain; }
        .nav-links-zone { display:flex; gap:.45rem; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
        .btn-nav-pill { display:inline-flex; align-items:center; gap:.4rem; border:1px solid rgba(255,255,255,.08); color:#fff!important; background:rgba(255,255,255,.04); text-decoration:none; border-radius:999px; padding:.48rem .85rem; font-size:.82rem; font-weight:800; }
        .btn-nav-pill:hover,.btn-nav-pill.active { border-color:var(--verde-presbiteriano); color:var(--verde-presbiteriano)!important; }
        .btn-theme { border:0; background:transparent; color:#fff; width:38px; height:38px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; }
        .hero-section { padding:2.1rem 0 1.1rem; text-align:center; }
        .title-main { font-family:Syne,sans-serif; color:var(--verde-presbiteriano); font-weight:800; text-transform:uppercase; font-size:clamp(2rem,5vw,3.3rem); letter-spacing:-.04em; }
        .muted { color:var(--texto-secundario); }
        .card-soft,.alert-soft { background:var(--bg-card); border:1px solid var(--borda-suave); border-radius:18px; box-shadow:var(--sombras); }
        .form-control,.form-select { background-color:var(--input-bg)!important; border:1px solid var(--borda-suave)!important; color:var(--texto-principal)!important; border-radius:14px; min-height:46px; }
        .form-control:focus,.form-select:focus { border-color:var(--verde-presbiteriano)!important; box-shadow:0 0 0 .25rem rgba(0,168,107,.12)!important; }
        .code-box { background:rgba(0,0,0,.35); color:#e9ecef; border-radius:14px; padding:1rem; overflow:auto; border:1px solid var(--borda-suave); font-size:.9rem; white-space:pre-wrap; }
        .copy-input { font-family:Consolas,monospace; font-size:.86rem; }
        .nav-pills .nav-link { border-radius:999px; color:var(--texto-secundario); font-weight:800; }
        .nav-pills .nav-link.active { background:var(--verde-presbiteriano); color:#fff; }
        .status-chip { display:inline-flex; align-items:center; gap:.4rem; border:1px solid var(--borda-suave); border-radius:999px; padding:.35rem .7rem; font-size:.8rem; font-weight:800; color:var(--texto-secundario); }
        .meter { height:12px; border-radius:999px; background:rgba(255,255,255,.08); overflow:hidden; border:1px solid var(--borda-suave); }
        .meter > div { height:100%; width:0%; background:var(--verde-presbiteriano); transition:width .08s linear; }
        .preview-caption { min-height:92px; display:flex; align-items:center; justify-content:center; text-align:center; font-size:1.35rem; font-weight:800; background:rgba(0,0,0,.35); border-radius:16px; padding:1rem; color:#fff; }
        .session-list .list-group-item { color:var(--texto-principal); border-color:var(--borda-suave); }
        @media (max-width:991px){ .nav-corporate-container{flex-direction:column;align-items:flex-start}.nav-links-zone{width:100%;overflow-x:auto;flex-wrap:nowrap;padding-bottom:.2rem}.btn-nav-pill{white-space:nowrap} }
    </style>
</head>
<body>
<nav class="nav-corporate-bar sticky-top">
    <div class="nav-corporate-container">
        <a href="index.php" class="brand-logo-zone">
            <img src="Igreja presbiteriana aliança sem fundo preta.png" alt="Logo">
            <span>Repertório Aliança</span>
        </a>
        <div class="nav-links-zone">
            <a href="index.php" class="btn-nav-pill"><i class="bi bi-house-door"></i> Início</a>
            <a href="projecao.php" class="btn-nav-pill"><i class="bi bi-easel2"></i> Projeção</a>
            <a href="legenda.php" class="btn-nav-pill active"><i class="bi bi-badge-cc"></i> Legenda</a>
            <button class="btn-theme" type="button" onclick="alternarTema()" title="Mudar tema"><i class="bi bi-sun-fill" id="iconeTema"></i></button>
        </div>
    </div>
</nav>

<main class="container pb-5" style="max-width:1120px;">
    <section class="hero-section">
        <h1 class="title-main">Legenda ao vivo</h1>
        <p class="muted mb-0">Use no OBS, na projeção ou direto pelo navegador para testar microfone/fone.</p>
    </section>

    <?php if ($mensagem): ?><div class="alert-soft p-3 mb-3 text-success"><i class="bi bi-check-circle-fill me-1"></i> <?php echo h($mensagem); ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert-soft p-3 mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo h($erro); ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card-soft p-4 mb-3">
                <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-success me-1"></i> Criar sessão</h5>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                    <input type="hidden" name="acao" value="nova_sessao">
                    <label class="form-label small muted">Nome da sessão</label>
                    <input class="form-control mb-3" name="titulo" value="Legenda do Culto" maxlength="150">
                    <button class="btn btn-success rounded-pill px-4 w-100 fw-bold" type="submit">Criar sessão</button>
                </form>
            </div>

            <div class="card-soft p-4 session-list">
                <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-success me-1"></i> Sessões recentes</h5>
                <?php if (!$sessoes): ?>
                    <p class="muted mb-0">Nenhuma sessão criada ainda.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($sessoes as $s): ?>
                            <a class="list-group-item list-group-item-action bg-transparent text-decoration-none px-0" href="legenda.php?sessao=<?php echo urlencode($s['codigo_sessao']); ?>">
                                <div class="fw-bold"><?php echo h($s['titulo'] ?: 'Legenda ao vivo'); ?></div>
                                <small class="muted">Status: <?php echo h($s['status']); ?> • <?php echo ((int)($s['ativo'] ?? 1) === 1) ? 'ativa' : 'encerrada'; ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (!$sessao): ?>
                <div class="card-soft p-4">
                    <h4 class="fw-bold mb-2">Escolha ou crie uma sessão</h4>
                    <p class="muted mb-0">A sessão é o “canal” da legenda. O overlay do OBS fica lendo essa sessão, e o Python ou o modo navegador envia o texto para ela.</p>
                </div>
            <?php else: ?>
                <div class="card-soft p-4 mb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                        <div>
                            <div class="status-chip mb-2"><i class="bi bi-broadcast-pin text-success"></i> Sessão: <?php echo h($sessao['codigo_sessao']); ?></div>
                            <h4 class="fw-bold mb-1"><?php echo h($sessao['titulo'] ?: 'Legenda ao vivo'); ?></h4>
                            <p class="muted mb-0">Status atual: <strong><?php echo h($sessao['status'] ?? 'aguardando'); ?></strong></p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <form method="POST" class="m-0">
                                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                <input type="hidden" name="acao" value="limpar">
                                <input type="hidden" name="sessao" value="<?php echo h($sessao['codigo_sessao']); ?>">
                                <button class="btn btn-outline-warning rounded-pill fw-bold" type="submit"><i class="bi bi-eraser"></i> Limpar</button>
                            </form>
                            <form method="POST" class="m-0" onsubmit="return confirm('Encerrar esta sessão de legenda?');">
                                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                <input type="hidden" name="acao" value="encerrar">
                                <input type="hidden" name="sessao" value="<?php echo h($sessao['codigo_sessao']); ?>">
                                <button class="btn btn-outline-danger rounded-pill fw-bold" type="submit"><i class="bi bi-stop-circle"></i> Encerrar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-pills gap-2 mb-3" id="legendaTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link <?php echo $aba_atual === 'painel' ? 'active' : ''; ?>" id="painel-tab" data-bs-toggle="pill" data-bs-target="#painel" type="button" role="tab"><i class="bi bi-sliders"></i> Painel</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link <?php echo $aba_atual === 'navegador' ? 'active' : ''; ?>" id="navegador-tab" data-bs-toggle="pill" data-bs-target="#navegador" type="button" role="tab"><i class="bi bi-mic"></i> Direto no site</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link <?php echo $aba_atual === 'python' ? 'active' : ''; ?>" id="python-tab" data-bs-toggle="pill" data-bs-target="#python" type="button" role="tab"><i class="bi bi-terminal"></i> Python/Yamaha</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade <?php echo $aba_atual === 'painel' ? 'show active' : ''; ?>" id="painel" role="tabpanel">
                        <div class="card-soft p-4 mb-3">
                            <h5 class="fw-bold mb-3"><i class="bi bi-badge-cc text-success me-1"></i> Overlay para OBS/projeção</h5>
                            <label class="form-label small muted">Link do overlay</label>
                            <div class="input-group mb-3">
                                <input id="overlayUrl" class="form-control copy-input" value="<?php echo h($overlay_url); ?>" readonly>
                                <button class="btn btn-success fw-bold" type="button" onclick="copiarCampo('overlayUrl')">Copiar</button>
                            </div>
                            <p class="muted mb-0">No OBS, adicione uma <strong>Fonte Navegador</strong> usando esse link. Ele fica transparente e mostra só a legenda.</p>
                        </div>

                        <div class="card-soft p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-palette text-success me-1"></i> Aparência da legenda</h5>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                <input type="hidden" name="acao" value="configurar">
                                <input type="hidden" name="sessao" value="<?php echo h($sessao['codigo_sessao']); ?>">
                                <div class="col-md-5">
                                    <label class="form-label small muted">Fundo da caixa</label>
                                    <input class="form-control" name="cor_fundo" value="<?php echo h($sessao['cor_fundo'] ?? 'rgba(0,0,0,0.72)'); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small muted">Cor da letra</label>
                                    <input type="color" class="form-control form-control-color w-100" name="cor_texto" value="<?php echo h($sessao['cor_texto'] ?? '#ffffff'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small muted">Fonte</label>
                                    <input type="number" class="form-control" name="tamanho_fonte" min="20" max="100" value="<?php echo (int)($sessao['tamanho_fonte'] ?? 42); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small muted">Posição</label>
                                    <select class="form-select" name="posicao">
                                        <option value="baixo" <?php echo ($sessao['posicao'] ?? '') === 'baixo' ? 'selected' : ''; ?>>Baixo</option>
                                        <option value="meio" <?php echo ($sessao['posicao'] ?? '') === 'meio' ? 'selected' : ''; ?>>Meio</option>
                                        <option value="cima" <?php echo ($sessao['posicao'] ?? '') === 'cima' ? 'selected' : ''; ?>>Cima</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="mostrar_caixa" id="mostrar_caixa" <?php echo ((int)($sessao['mostrar_caixa'] ?? 1) === 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mostrar_caixa">Mostrar caixa escura atrás da legenda</label>
                                    </div>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-success rounded-pill px-4 fw-bold" type="submit">Salvar aparência</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $aba_atual === 'navegador' ? 'show active' : ''; ?>" id="navegador" role="tabpanel">
                        <div class="card-soft p-4 mb-3">
                            <h5 class="fw-bold mb-2"><i class="bi bi-mic-fill text-success me-1"></i> Legendar direto pelo site</h5>
                            <p class="muted">Esse modo é ótimo para testar em casa com microfone/fone. No culto, para a Yamaha USB, o Python ainda é o modo mais forte e estável.</p>

                            <div class="alert alert-warning border-0" style="background:rgba(255,193,7,.12); color:#ffc107; border-radius:14px;">
                                <strong>Importante:</strong> o navegador seleciona <strong>entrada de áudio</strong>, como microfone, headset ou Yamaha USB. Ele não consegue “ouvir” diretamente a saída do fone/caixa de som sem uma entrada virtual ou configuração do Windows.
                            </div>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-7">
                                    <label class="form-label small muted">Entrada de áudio para teste</label>
                                    <select id="audioInputSelect" class="form-select">
                                        <option value="">Clique em “Liberar microfone”</option>
                                    </select>
                                </div>
                                <div class="col-md-5 d-flex gap-2 flex-wrap">
                                    <button class="btn btn-outline-success rounded-pill fw-bold flex-fill" type="button" onclick="carregarEntradas()"><i class="bi bi-unlock"></i> Liberar microfone</button>
                                    <button class="btn btn-success rounded-pill fw-bold flex-fill" type="button" onclick="iniciarReconhecimento()"><i class="bi bi-play-fill"></i> Iniciar</button>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between small muted mb-1"><span>Nível do áudio capturado</span><span id="micStatus">parado</span></div>
                                <div class="meter"><div id="meterBar"></div></div>
                            </div>

                            <div class="preview-caption mt-3" id="previewCaption">A legenda aparecerá aqui...</div>

                            <div class="d-flex gap-2 flex-wrap mt-3">
                                <button class="btn btn-outline-light rounded-pill fw-bold" type="button" onclick="pararReconhecimento()"><i class="bi bi-pause-fill"></i> Parar</button>
                                <button class="btn btn-outline-warning rounded-pill fw-bold" type="button" onclick="limparLegendaWeb()"><i class="bi bi-eraser"></i> Limpar legenda</button>
                            </div>
                        </div>

                        <div class="card-soft p-4">
                            <h6 class="fw-bold"><i class="bi bi-keyboard text-success me-1"></i> Texto manual de emergência</h6>
                            <p class="muted small">Útil se a internet ou o reconhecimento falhar: digite uma frase e envie para o overlay.</p>
                            <textarea id="textoManual" class="form-control mb-2" rows="2" placeholder="Digite a legenda manual aqui..."></textarea>
                            <button class="btn btn-success rounded-pill fw-bold px-4" type="button" onclick="enviarManual()">Enviar manualmente</button>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $aba_atual === 'python' ? 'show active' : ''; ?>" id="python" role="tabpanel">
                        <div class="card-soft p-4 mb-3">
                            <h5 class="fw-bold mb-3"><i class="bi bi-terminal text-success me-1"></i> Modo Python para Yamaha MG12XU</h5>
                            <p class="muted">Use esse modo no PC da igreja, porque é ele que consegue escutar a Yamaha USB com mais controle e usar Whisper local.</p>
                            <label class="form-label small muted">Comando base</label>
                            <div class="code-box" id="comandoPython"><?php echo h($comando_python); ?></div>
                            <button class="btn btn-success rounded-pill fw-bold mt-3" type="button" onclick="copiarTexto(document.getElementById('comandoPython').innerText)"><i class="bi bi-copy"></i> Copiar comando</button>
                        </div>

                        <div class="card-soft p-4">
                            <h6 class="fw-bold">Como escolher a Yamaha no PC da igreja</h6>
                            <ol class="muted mb-0">
                                <li>Coloque no PC os arquivos <strong>legenda_live_site.py</strong>, <strong>instalar_dependencias.bat</strong> e <strong>listar_dispositivos.bat</strong>.</li>
                                <li>Execute <strong>instalar_dependencias.bat</strong> uma vez.</li>
                                <li>Execute <strong>listar_dispositivos.bat</strong> e veja o número da Yamaha USB.</li>
                                <li>No comando base, adicione no final: <strong>--device NÚMERO</strong>.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const temaSalvo = localStorage.getItem('tema') || 'dark';
document.documentElement.setAttribute('data-bs-theme', temaSalvo);
atualizarIcone(temaSalvo);
function alternarTema() {
    const html = document.documentElement;
    const novo = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', novo);
    localStorage.setItem('tema', novo);
    atualizarIcone(novo);
}
function atualizarIcone(tema) {
    const icone = document.getElementById('iconeTema');
    if (icone) icone.className = tema === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
}
function copiarCampo(id) {
    const el = document.getElementById(id);
    if (!el) return;
    navigator.clipboard.writeText(el.value || el.innerText || '').then(() => alert('Copiado!'));
}
function copiarTexto(txt) {
    navigator.clipboard.writeText(txt).then(() => alert('Copiado!'));
}

const sessaoAtual = <?php echo json_encode($sessao['codigo_sessao'] ?? ''); ?>;
const tokenAtual = <?php echo json_encode($sessao['token_envio'] ?? ''); ?>;
let streamAtual = null;
let audioContext = null;
let analyser = null;
let meterLoop = null;
let recognition = null;
let reconhecendo = false;
let ultimoEnvio = '';
let debounceEnvio = null;

async function carregarEntradas() {
    const status = document.getElementById('micStatus');
    const select = document.getElementById('audioInputSelect');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Este navegador não liberou acesso ao microfone. Use Chrome/Edge atualizado em HTTPS.');
        return;
    }
    try {
        status.textContent = 'pedindo permissão...';
        const temp = await navigator.mediaDevices.getUserMedia({ audio: true });
        temp.getTracks().forEach(t => t.stop());

        const devices = await navigator.mediaDevices.enumerateDevices();
        const entradas = devices.filter(d => d.kind === 'audioinput');
        select.innerHTML = '';
        if (!entradas.length) {
            select.innerHTML = '<option value="">Nenhuma entrada encontrada</option>';
            status.textContent = 'sem entrada';
            return;
        }
        entradas.forEach((d, i) => {
            const opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || ('Entrada de áudio ' + (i + 1));
            select.appendChild(opt);
        });
        status.textContent = 'entradas carregadas';
        await prepararEntradaSelecionada();
    } catch (e) {
        status.textContent = 'permissão negada';
        alert('Não consegui acessar o microfone. Confira a permissão do navegador.');
    }
}

async function prepararEntradaSelecionada() {
    const select = document.getElementById('audioInputSelect');
    const status = document.getElementById('micStatus');
    pararStreamAtual();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;

    const deviceId = select && select.value ? select.value : null;
    const audio = deviceId ? { deviceId: { exact: deviceId }, echoCancellation: true, noiseSuppression: true, autoGainControl: true } : true;
    try {
        streamAtual = await navigator.mediaDevices.getUserMedia({ audio });
        status.textContent = 'capturando entrada';
        iniciarMedidor(streamAtual);
    } catch (e) {
        status.textContent = 'erro na entrada';
        alert('Não consegui abrir essa entrada de áudio. Tente outra opção.');
    }
}

function pararStreamAtual() {
    if (meterLoop) cancelAnimationFrame(meterLoop);
    meterLoop = null;
    if (streamAtual) streamAtual.getTracks().forEach(t => t.stop());
    streamAtual = null;
    if (audioContext) audioContext.close().catch(() => {});
    audioContext = null;
    const bar = document.getElementById('meterBar');
    if (bar) bar.style.width = '0%';
}

function iniciarMedidor(stream) {
    const bar = document.getElementById('meterBar');
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    analyser = audioContext.createAnalyser();
    analyser.fftSize = 256;
    const source = audioContext.createMediaStreamSource(stream);
    source.connect(analyser);
    const data = new Uint8Array(analyser.frequencyBinCount);
    const tick = () => {
        analyser.getByteFrequencyData(data);
        let soma = 0;
        for (let i = 0; i < data.length; i++) soma += data[i];
        const media = soma / data.length;
        const porcentagem = Math.min(100, Math.round(media * 1.6));
        if (bar) bar.style.width = porcentagem + '%';
        meterLoop = requestAnimationFrame(tick);
    };
    tick();
}

const selectEntrada = document.getElementById('audioInputSelect');
if (selectEntrada) selectEntrada.addEventListener('change', prepararEntradaSelecionada);

function suporteSpeech() {
    return window.SpeechRecognition || window.webkitSpeechRecognition;
}

async function iniciarReconhecimento() {
    if (!sessaoAtual || !tokenAtual) {
        alert('Crie ou abra uma sessão primeiro.');
        return;
    }
    const SpeechRecognition = suporteSpeech();
    if (!SpeechRecognition) {
        alert('Este navegador não tem reconhecimento de fala nativo. Teste no Chrome ou use o modo Python.');
        return;
    }
    if (!streamAtual) {
        await carregarEntradas();
    }

    pararReconhecimento(false);
    recognition = new SpeechRecognition();
    recognition.lang = 'pt-BR';
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.maxAlternatives = 1;
    reconhecendo = true;
    document.getElementById('micStatus').textContent = 'ouvindo e legendando';

    recognition.onresult = (event) => {
        let textoFinal = '';
        let textoInterino = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const texto = event.results[i][0].transcript.trim();
            if (event.results[i].isFinal) textoFinal += texto + ' ';
            else textoInterino += texto + ' ';
        }
        const texto = (textoFinal || textoInterino).trim();
        if (texto) {
            atualizarPreview(texto);
            agendarEnvio(texto, textoFinal ? 'ouvindo' : 'processando');
        }
    };

    recognition.onerror = (event) => {
        document.getElementById('micStatus').textContent = 'erro: ' + (event.error || 'reconhecimento');
    };

    recognition.onend = () => {
        if (reconhecendo) {
            setTimeout(() => {
                try { recognition.start(); } catch (e) {}
            }, 600);
        } else {
            document.getElementById('micStatus').textContent = 'parado';
        }
    };

    try {
        recognition.start();
    } catch (e) {
        document.getElementById('micStatus').textContent = 'já estava ouvindo';
    }
}

function pararReconhecimento(mostrar = true) {
    reconhecendo = false;
    if (recognition) {
        try { recognition.stop(); } catch (e) {}
        recognition = null;
    }
    if (mostrar && document.getElementById('micStatus')) document.getElementById('micStatus').textContent = 'parado';
}

function atualizarPreview(texto) {
    const preview = document.getElementById('previewCaption');
    if (preview) preview.textContent = texto;
}

function agendarEnvio(texto, status) {
    clearTimeout(debounceEnvio);
    debounceEnvio = setTimeout(() => enviarLegendaWeb(texto, status), 250);
}

async function enviarLegendaWeb(texto, status = 'ouvindo') {
    texto = (texto || '').trim();
    if (texto === ultimoEnvio && texto !== '') return;
    ultimoEnvio = texto;
    try {
        await fetch('legenda_api.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({ sessao: sessaoAtual, token: tokenAtual, texto, status })
        });
    } catch (e) {
        const statusEl = document.getElementById('micStatus');
        if (statusEl) statusEl.textContent = 'erro ao enviar';
    }
}

async function limparLegendaWeb() {
    atualizarPreview('A legenda aparecerá aqui...');
    ultimoEnvio = '';
    await enviarLegendaWeb('', 'limpo');
}

function enviarManual() {
    const el = document.getElementById('textoManual');
    const texto = (el.value || '').trim();
    if (!texto) return;
    atualizarPreview(texto);
    enviarLegendaWeb(texto, 'manual');
}
</script>
</body>
</html>
