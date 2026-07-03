<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function normalizar_tipo_usuario($valor) {
    $valor = strtolower(trim((string)$valor));
    return str_replace(
        ['á','à','ã','â','ä','é','ê','ë','í','ï','ó','ô','õ','ö','ú','ü','ç'],
        ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c'],
        $valor
    );
}

$tipo_logado = normalizar_tipo_usuario($_SESSION['tipo'] ?? '');
$eh_admin = isset($_SESSION['usuario']) && in_array($tipo_logado, ['admin', 'administrador', 'ipa'], true);

if (!$eh_admin) {
    header('Location: login.php');
    exit;
}

unset($_SESSION['fluxo_publico'], $_SESSION['playlist_code_atual']);

if (empty($_SESSION['csrf_usuarios'])) {
    $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_usuarios'];

$mensagem = '';
$erro = '';

function coluna_bool($usuario, $coluna) {
    return !empty($usuario[$coluna]) && (int)$usuario[$coluna] === 1;
}

function badge_sim_nao($valor, $texto) {
    if ($valor) {
        return '<span class="perm-badge ok"><i class="bi bi-check2"></i> ' . h($texto) . '</span>';
    }
    return '<span class="perm-badge off"><i class="bi bi-x"></i> ' . h($texto) . '</span>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $token = $_POST['csrf'] ?? '';
    $id_excluir = (int)($_POST['id'] ?? 0);

    if (!hash_equals($csrf, $token)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif ($id_excluir <= 0) {
        $erro = 'Usuário inválido.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id_excluir]);
            $usuario_excluir = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario_excluir) {
                $erro = 'Usuário não encontrado.';
            } elseif (($usuario_excluir['usuario'] ?? '') === ($_SESSION['usuario'] ?? '')) {
                $erro = 'Você não pode excluir o próprio usuário logado.';
            } else {
                $tipo_excluir = normalizar_tipo_usuario($usuario_excluir['tipo'] ?? '');
                if (in_array($tipo_excluir, ['admin', 'administrador', 'ipa'], true)) {
                    $stmt_admins = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE LOWER(tipo) IN ('admin','administrador','ipa')");
                    $total_admins = (int)$stmt_admins->fetchColumn();
                    if ($total_admins <= 1) {
                        $erro = 'Não é possível excluir o último Admin do sistema.';
                    }
                }

                if ($erro === '') {
                    $stmt_del = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
                    $stmt_del->execute(['id' => $id_excluir]);
                    $mensagem = 'Usuário excluído com sucesso.';
                }
            }
        } catch (Exception $e) {
            $erro = 'Não foi possível excluir o usuário. Verifique se a tabela usuarios possui a coluna id.';
        }
    }
}

$usuarios = [];
$totais = ['admin' => 0, 'musico' => 0, 'vocal' => 0, 'outros' => 0];

try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY CASE LOWER(tipo) WHEN 'admin' THEN 1 WHEN 'administrador' THEN 1 WHEN 'ipa' THEN 1 WHEN 'musico' THEN 2 WHEN 'musicos' THEN 2 WHEN 'músico' THEN 2 WHEN 'músicos' THEN 2 WHEN 'vocal' THEN 3 WHEN 'vocais' THEN 3 ELSE 4 END, usuario ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $u) {
        $tipo = normalizar_tipo_usuario($u['tipo'] ?? '');
        if (in_array($tipo, ['admin', 'administrador', 'ipa'], true)) {
            $totais['admin']++;
        } elseif (in_array($tipo, ['musico', 'musicos', 'instrumentista'], true)) {
            $totais['musico']++;
        } elseif (in_array($tipo, ['vocal', 'vocais'], true)) {
            $totais['vocal']++;
        } else {
            $totais['outros']++;
        }
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar os usuários. Detalhe: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Usuários - Repertório Aliança</title>

    <link rel="icon" type="image/png" href="Igreja presbiteriana aliança sem fundo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-principal: #f8f9fa;
            --bg-card: #ffffff;
            --texto-principal: #1a1a1a;
            --texto-secundario: #65676b;
            --verde-presbiteriano: #00563B;
            --borda-suave: rgba(0, 0, 0, 0.08);
            --sombras: 0 4px 20px rgba(0, 0, 0, 0.04);
            --bg-nav: #1e2227;
            --borda-nav: rgba(0, 0, 0, 0.15);
            --texto-nav: #ffffff;
            --input-bg: #ffffff;
        }
        [data-bs-theme="dark"] {
            --bg-principal: #0d0f12;
            --bg-card: #16191d;
            --texto-principal: #ffffff;
            --texto-secundario: #989faf;
            --verde-presbiteriano: #00a86b;
            --borda-suave: rgba(255, 255, 255, 0.06);
            --sombras: 0 10px 30px rgba(0, 0, 0, 0.4);
            --bg-nav: #14171a;
            --borda-nav: rgba(255, 255, 255, 0.08);
            --texto-nav: #ffffff;
            --input-bg: #111418;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top center, rgba(0, 168, 107, 0.08), transparent 34rem), var(--bg-principal);
            color: var(--texto-principal);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .nav-corporate-bar {
            background-color: rgba(20, 23, 26, 0.92);
            border-bottom: 1px solid var(--borda-nav);
            box-shadow: var(--sombras);
            padding: 0.55rem 0;
            z-index: 1030;
            backdrop-filter: blur(12px);
        }
        .nav-corporate-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 1rem;
            gap: 1rem;
        }
        .brand-logo-zone {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--texto-nav) !important;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            white-space: nowrap;
        }
        .nav-brand-img { height: 32px; width: auto; object-fit: contain; }
        .nav-links-zone { display: flex; align-items: center; justify-content: flex-end; gap: 0.45rem; flex-wrap: wrap; }
        .btn-nav-pill {
            display: inline-flex; align-items: center; gap: 0.4rem;
            border: 1px solid var(--borda-nav); color: var(--texto-nav) !important;
            background: rgba(255, 255, 255, 0.04); text-decoration: none;
            border-radius: 999px; padding: 0.48rem 0.85rem;
            font-size: 0.82rem; font-weight: 700; transition: all 0.2s ease;
        }
        .btn-nav-pill:hover, .btn-nav-pill.active { border-color: var(--verde-presbiteriano); color: var(--verde-presbiteriano) !important; transform: translateY(-1px); }
        .btn-theme { border: 0; background: transparent; color: var(--texto-nav); width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
        .hero-section { padding: 2.2rem 0 1.2rem; text-align: center; }
        .central-brand-title { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--verde-presbiteriano); text-transform: uppercase; font-size: clamp(2rem, 5vw, 3.4rem); letter-spacing: -0.04em; margin: 0; }
        .hero-subtitle { color: var(--texto-secundario); margin-top: 0.5rem; }
        .linha-ponta-a-ponta { border: 0; border-top: 1px solid var(--borda-suave); width: 100%; margin: 1.2rem 0 2rem; opacity: 0.9; }
        .stat-card, .user-card, .alert-soft { background: var(--bg-card); border: 1px solid var(--borda-suave); border-radius: 18px; box-shadow: var(--sombras); }
        .stat-card { padding: 1rem; height: 100%; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--verde-presbiteriano); line-height: 1; }
        .stat-label { color: var(--texto-secundario); font-size: 0.85rem; font-weight: 700; }
        .user-card { padding: 1rem; transition: all 0.2s ease; }
        .user-card:hover { border-color: var(--verde-presbiteriano); transform: translateY(-2px); }
        .tipo-badge { border-radius: 999px; padding: 0.25rem 0.65rem; font-size: 0.74rem; font-weight: 800; border: 1px solid var(--borda-suave); text-transform: uppercase; }
        .tipo-admin { background: rgba(255, 193, 7, 0.12); color: #ffc107; border-color: rgba(255, 193, 7, 0.24); }
        .tipo-musico { background: rgba(255, 159, 28, 0.12); color: #ff9f1c; border-color: rgba(255, 159, 28, 0.24); }
        .tipo-vocal { background: rgba(0, 168, 107, 0.12); color: var(--verde-presbiteriano); border-color: rgba(0, 168, 107, 0.24); }
        .perm-badge { display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 999px; padding: 0.22rem 0.55rem; font-size: 0.72rem; font-weight: 700; border: 1px solid var(--borda-suave); margin: 0.1rem; }
        .perm-badge.ok { color: var(--verde-presbiteriano); background: rgba(0, 168, 107, 0.08); }
        .perm-badge.off { color: var(--texto-secundario); opacity: 0.65; }
        .btn-action { border-radius: 999px; font-weight: 800; font-size: 0.82rem; padding: 0.48rem 0.85rem; }
        .alert-soft { padding: 0.9rem 1rem; }
        .search-box { background: var(--bg-card); border: 1px solid var(--borda-suave); color: var(--texto-principal); border-radius: 999px; padding: 0.7rem 1rem; outline: none; width: 100%; }
        .search-box:focus { border-color: var(--verde-presbiteriano); box-shadow: 0 0 0 0.25rem rgba(0,168,107,.12); }
        @media (max-width: 991px) { .nav-corporate-container { flex-direction: column; align-items: flex-start; } .nav-links-zone { width: 100%; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 0.2rem; } .btn-nav-pill { white-space: nowrap; } }
    </style>
</head>
<body>
    <nav class="nav-corporate-bar sticky-top">
        <div class="nav-corporate-container">
            <a class="brand-logo-zone" href="index.php">
                <img src="Igreja presbiteriana aliança sem fundo preta.png" alt="Logo Aliança" class="nav-brand-img">
                <span>Repertório Aliança</span>
            </a>
            <div class="nav-links-zone">
                <a href="index.php" class="btn-nav-pill"><i class="bi bi-house-door"></i> Início</a>
                <a href="usuarios.php" class="btn-nav-pill active"><i class="bi bi-people"></i> Usuários</a>
                <a href="criar_usuario.php" class="btn-nav-pill"><i class="bi bi-person-plus"></i> Novo Usuário</a>
                <a href="dashboard.php" class="btn-nav-pill"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <button class="btn-theme" type="button" onclick="alternarTema()" title="Mudar tema"><i class="bi bi-sun-fill" id="iconeTema"></i></button>
            </div>
        </div>
    </nav>

    <main class="container pb-5">
        <section class="hero-section">
            <h1 class="central-brand-title">Usuários</h1>
            <p class="hero-subtitle">Cadastre, edite permissões e exclua usuários do sistema.</p>
            <hr class="linha-ponta-a-ponta">
        </section>

        <?php if ($mensagem): ?>
            <div class="alert-soft mb-3 text-success"><i class="bi bi-check-circle-fill me-1"></i> <?php echo h($mensagem); ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert-soft mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo h($erro); ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value"><?php echo (int)$totais['admin']; ?></div><div class="stat-label">Admins</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value"><?php echo (int)$totais['musico']; ?></div><div class="stat-label">Músicos</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value"><?php echo (int)$totais['vocal']; ?></div><div class="stat-label">Vocais</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-value"><?php echo count($usuarios); ?></div><div class="stat-label">Total</div></div></div>
        </div>

        <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center justify-content-between mb-3">
            <input type="search" class="search-box" id="buscaUsuario" placeholder="Pesquisar usuário...">
            <a href="criar_usuario.php" class="btn btn-success btn-action px-4"><i class="bi bi-person-plus me-1"></i> Criar usuário</a>
        </div>

        <div class="row g-3" id="listaUsuarios">
            <?php foreach ($usuarios as $u): ?>
                <?php
                    $tipo = normalizar_tipo_usuario($u['tipo'] ?? 'vocal');
                    $classe_tipo = 'tipo-vocal';
                    $rotulo_tipo = $u['tipo'] ?? 'vocal';
                    if (in_array($tipo, ['admin', 'administrador', 'ipa'], true)) { $classe_tipo = 'tipo-admin'; $rotulo_tipo = 'admin'; }
                    elseif (in_array($tipo, ['musico', 'musicos', 'instrumentista'], true)) { $classe_tipo = 'tipo-musico'; $rotulo_tipo = 'musico'; }
                ?>
                <div class="col-12 usuario-item" data-search="<?php echo h(strtolower(($u['usuario'] ?? '') . ' ' . ($u['tipo'] ?? ''))); ?>">
                    <div class="user-card">
                        <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle text-success me-1"></i> <?php echo h($u['usuario'] ?? ''); ?></h5>
                                    <span class="tipo-badge <?php echo h($classe_tipo); ?>"><?php echo h($rotulo_tipo); ?></span>
                                </div>
                                <div class="mb-2">
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_ver_letras'), 'Letras'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_ver_comentarios'), 'Comentários ()'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_ver_cifras'), 'Cifras'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_criar_links'), 'Links'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_editar_musicas'), 'Editar músicas'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_excluir_musicas'), 'Excluir músicas'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_acessar_dashboard'), 'Dashboard'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_usar_projecao'), 'Projeção'); ?>
                                    <?php echo badge_sim_nao(coluna_bool($u, 'pode_usar_legenda'), 'Legenda'); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-start justify-content-lg-end">
                                <a href="criar_usuario.php?id=<?php echo (int)($u['id'] ?? 0); ?>" class="btn btn-outline-warning btn-action"><i class="bi bi-pencil-square me-1"></i> Editar</a>
                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-action"><i class="bi bi-trash3 me-1"></i> Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function alternarTema() {
            const html = document.documentElement;
            const novoTema = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', novoTema);
            localStorage.setItem('tema', novoTema);
            atualizarIcone(novoTema);
        }
        function atualizarIcone(tema) {
            const icone = document.getElementById('iconeTema');
            if (icone) icone.className = tema === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
        const temaSalvo = localStorage.getItem('tema') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', temaSalvo);
        atualizarIcone(temaSalvo);

        const busca = document.getElementById('buscaUsuario');
        if (busca) {
            busca.addEventListener('input', () => {
                const termo = busca.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                document.querySelectorAll('.usuario-item').forEach(item => {
                    const texto = (item.dataset.search || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    item.style.display = texto.includes(termo) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
