<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 ÁREA LOGADA: quem entra pelo login.php / index.php deve ficar no fluxo interno
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Evita que um acesso anterior pelo index_publico.php prenda o usuário logado no fluxo público
unset($_SESSION['fluxo_publico']);

require_once 'config.php';

// 🔴 🟢 COLOQUE OS LINKS REAIS DAS PLAYLISTS DA IGREJA AQUI:
$link_spotify_geral = "https://open.spotify.com/...";
$link_youtube_geral = "https://www.youtube.com/...";

$usuario_nome = $_SESSION['usuario'] ?? 'Usuário';
$tipo_usuario = strtolower((string)($_SESSION['tipo'] ?? ''));
$is_admin = ($tipo_usuario === 'admin');
$tipo_usuario_sem_acento = str_replace(
    ['á','à','ã','â','ä','é','ê','ë','í','ï','ó','ô','õ','ö','ú','ü','ç'],
    ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c'],
    $tipo_usuario
);
$pode_ver_cifra = in_array($tipo_usuario_sem_acento, ['admin', 'administrador', 'ipa', 'musico', 'musicos', 'instrumentista', 'cifra'], true);
$pode_usar_projecao = $is_admin || !empty($_SESSION['pode_usar_projecao']);
$pode_usar_legenda = $is_admin || !empty($_SESSION['pode_usar_legenda']);

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$musicas = [];
$erro_banco = '';

try {
    if (!empty($busca)) {
        $stmt = $pdo->prepare("SELECT id, titulo, artista, link_spotify, link_youtube FROM musicas WHERE titulo LIKE :busca OR artista LIKE :busca ORDER BY titulo ASC");
        $stmt->execute(['busca' => "%$busca%"]);
    } else {
        $stmt = $pdo->query("SELECT id, titulo, artista, link_spotify, link_youtube FROM musicas ORDER BY titulo ASC");
    }
    $musicas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        if (!empty($busca)) {
            $stmt = $pdo->prepare("SELECT id, titulo, artista FROM musicas WHERE titulo LIKE :busca OR artista LIKE :busca ORDER BY titulo ASC");
            $stmt->execute(['busca' => "%$busca%"]);
        } else {
            $stmt = $pdo->query("SELECT id, titulo, artista FROM musicas ORDER BY titulo ASC");
        }
        $musicas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($musicas as &$m) {
            $m['link_spotify'] = '';
            $m['link_youtube'] = '';
        }
        unset($m);
    } catch (PDOException $e2) {
        $musicas = [];
        $erro_banco = 'Não foi possível carregar as músicas no momento.';
    }
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Letras IPA - Painel</title>

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
            background:
                radial-gradient(circle at top center, rgba(0, 168, 107, 0.08), transparent 34rem),
                var(--bg-principal);
            color: var(--texto-principal);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
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

        .nav-brand-img {
            height: 32px;
            width: auto;
            object-fit: contain;
        }

        .nav-links-zone {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .btn-nav-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: 1px solid var(--borda-nav);
            color: var(--texto-nav) !important;
            background: rgba(255, 255, 255, 0.04);
            text-decoration: none;
            border-radius: 999px;
            padding: 0.48rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-nav-pill:hover,
        .btn-nav-pill.active {
            border-color: var(--verde-presbiteriano);
            color: var(--verde-presbiteriano) !important;
            transform: translateY(-1px);
        }

        .btn-nav-danger:hover {
            border-color: #dc3545;
            color: #ff6b7a !important;
            background: rgba(220, 53, 69, 0.1);
        }

        .btn-theme {
            border: 0;
            background: transparent;
            color: var(--texto-nav);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-theme:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--verde-presbiteriano);
        }

        .hero-section {
            padding: 2.1rem 0 1.3rem;
            text-align: center;
        }

        .schedule-badge-zone {
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            color: var(--texto-secundario);
            background-color: var(--bg-card);
            padding: 0.48rem 1.1rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            border: 1px solid var(--borda-suave);
            box-shadow: var(--sombras);
            margin-bottom: 1.3rem;
            flex-wrap: wrap;
        }

        .central-brand-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            color: var(--verde-presbiteriano);
            text-transform: uppercase;
            font-size: clamp(2.1rem, 5vw, 3.6rem);
            letter-spacing: -0.04em;
            margin-bottom: 0.8rem;
        }

        .central-verse-text {
            font-style: italic;
            color: var(--texto-secundario);
            max-width: 780px;
            margin: 0 auto 1.4rem;
            line-height: 1.55;
            font-size: 1rem;
        }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--texto-secundario);
            background: var(--bg-card);
            border: 1px solid var(--borda-suave);
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            box-shadow: var(--sombras);
            font-size: 0.86rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .btn-social-global {
            border: 1px solid var(--borda-suave);
            background-color: var(--bg-card);
            font-weight: 800;
            font-size: 0.84rem;
            text-transform: uppercase;
            border-radius: 14px;
            padding: 0.55rem 1.1rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--texto-principal) !important;
            text-decoration: none;
            box-shadow: var(--sombras);
        }

        .btn-social-global img {
            height: 20px;
            width: auto;
            object-fit: contain;
        }

        .btn-social-global:hover {
            transform: translateY(-2px);
            border-color: var(--verde-presbiteriano);
        }

        .linha-ponta-a-ponta {
            border: 0;
            border-top: 1px solid var(--borda-suave);
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .search-panel {
            background-color: var(--bg-card);
            border: 1px solid var(--borda-suave);
            border-radius: 18px;
            box-shadow: var(--sombras);
            padding: 0.45rem;
            max-width: 780px;
            margin: 0 auto 1.3rem;
        }

        .search-panel .form-control {
            background: transparent !important;
            border: 0 !important;
            color: var(--texto-principal) !important;
            box-shadow: none !important;
            min-height: 44px;
        }

        .search-panel .form-control::placeholder { color: var(--texto-secundario); }

        .badge-total-inline {
            color: var(--texto-secundario);
            font-weight: 700;
            font-size: 0.84rem;
            padding: 0 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-left: 1px solid var(--borda-suave);
            white-space: nowrap;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 1.3rem 0 1rem;
        }

        .section-heading h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0;
        }

        .musica-row-card {
            background-color: var(--bg-card);
            border: 1px solid var(--borda-suave);
            border-radius: 16px;
            box-shadow: var(--sombras);
            transition: all 0.2s ease;
            height: 100%;
        }

        .musica-row-card:hover {
            border-color: var(--verde-presbiteriano);
            transform: translateY(-2px);
        }

        .link-letra-zona {
            text-decoration: none;
            color: var(--texto-principal) !important;
            flex-grow: 1;
            min-width: 0;
        }

        .music-title {
            font-weight: 800;
            margin: 0;
            line-height: 1.25;
        }

        .music-artist {
            color: var(--texto-secundario);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.25rem;
        }

        .row-media-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.18);
            border: 1px solid var(--borda-suave);
            transition: all 0.2s;
            text-decoration: none;
            flex-shrink: 0;
        }

        .row-media-btn img {
            height: 17px;
            width: auto;
            object-fit: contain;
        }

        .row-media-btn:hover {
            transform: scale(1.08);
            background: rgba(255,255,255,0.06);
            border-color: var(--verde-presbiteriano);
        }

        .row-cifra-btn {
            color: #ff9f1c !important;
            font-size: 1rem;
        }

        .row-letra-btn {
            width: auto;
            min-width: 76px;
            padding: 7px 12px;
            gap: 0.35rem;
            color: var(--verde-presbiteriano) !important;
            font-weight: 800;
            font-size: 0.78rem;
        }

        .arrow-link {
            color: var(--texto-secundario);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .arrow-link:hover {
            color: var(--verde-presbiteriano);
            background: rgba(0, 168, 107, 0.08);
        }

        .empty-state {
            background: var(--bg-card);
            border: 1px dashed var(--borda-suave);
            border-radius: 18px;
            padding: 2rem;
            color: var(--texto-secundario);
            text-align: center;
        }

        .admin-floating-action {
            position: fixed;
            right: 1.2rem;
            bottom: 1.2rem;
            z-index: 1020;
            border-radius: 999px;
            box-shadow: var(--sombras);
            font-weight: 800;
        }

        @media (max-width: 991px) {
            .nav-corporate-container { align-items: flex-start; flex-direction: column; }
            .nav-links-zone { width: 100%; justify-content: flex-start; overflow-x: auto; padding-bottom: 0.2rem; flex-wrap: nowrap; }
            .btn-nav-pill { white-space: nowrap; }
        }

        @media (max-width: 576px) {
            .hero-section { padding-top: 1.5rem; }
            .schedule-badge-zone { width: 100%; border-radius: 16px; gap: 0.4rem; }
            .search-panel .input-group { flex-wrap: nowrap; }
            .badge-total-inline { display: none; }
            .btn-social-global { width: 100%; justify-content: center; }
            .admin-floating-action { left: 1rem; right: 1rem; justify-content: center; }
        }
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
                <a href="index.php" class="btn-nav-pill active"><i class="bi bi-house-door"></i> Início</a>
                <a href="minha_lista.php" class="btn-nav-pill"><i class="bi bi-star"></i> Minha Lista</a>
                <a href="painel_louvor.php" class="btn-nav-pill"><i class="bi bi-grid-1x2"></i> Painel Louvor</a>
                <?php if ($pode_usar_projecao): ?>
                    <a href="projecao.php" class="btn-nav-pill"><i class="bi bi-easel2"></i> Projeção</a>
                <?php endif; ?>
                <?php if ($pode_usar_legenda): ?>
                    <a href="legenda.php" class="btn-nav-pill"><i class="bi bi-badge-cc"></i> Legenda</a>
                <?php endif; ?>
                <a href="historico.php" class="btn-nav-pill"><i class="bi bi-clock-history"></i> Histórico</a>

                <?php if ($is_admin): ?>
                    <a href="cadastrar.php" class="btn-nav-pill"><i class="bi bi-plus-circle"></i> Cadastrar</a>
                    <a href="link_personalizado.php" class="btn-nav-pill"><i class="bi bi-link-45deg"></i> Link Personalizado</a>
                    <a href="dashboard.php" class="btn-nav-pill"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="usuarios.php" class="btn-nav-pill"><i class="bi bi-people"></i> Usuários</a>
                    <a href="backup.php" class="btn-nav-pill"><i class="bi bi-database-down"></i> Backup</a>
                <?php endif; ?>

                <a href="index_publico.php" class="btn-nav-pill" title="Abrir versão pública"><i class="bi bi-globe2"></i> Público</a>
                <button class="btn-theme" type="button" onclick="alternarTema()" title="Mudar tema"><i class="bi bi-sun-fill" id="iconeTema"></i></button>
                <a href="logout.php" class="btn-nav-pill btn-nav-danger"><i class="bi bi-box-arrow-right"></i> Sair</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="container hero-section">
            <div class="schedule-badge-zone">
                <span><i class="bi bi-calendar3 text-success me-1"></i> <strong>EBD:</strong> Dom às 09:00</span>
                <span class="text-muted d-none d-sm-inline">|</span>
                <span><i class="bi bi-clock text-success me-1"></i> <strong>Culto:</strong> Dom às 19:00</span>
            </div>

            <header class="text-center">
                <div class="user-chip">
                    <i class="bi bi-person-circle text-success"></i>
                    <span>Olá, <?php echo h($usuario_nome); ?></span>
                    <?php if (!empty($tipo_usuario)): ?>
                        <span class="text-success fw-bold">• <?php echo h(ucfirst($tipo_usuario)); ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="central-brand-title">Letras Aliança</h1>
                <p class="central-verse-text mt-2">
                    "Louvai ao Senhor, porque é bom cantar louvores ao nosso Deus; porque isso é agradável, e decoroso o louvor."
                    <span class="d-block text-success fw-bold mt-1" style="font-style: normal; font-size: 0.85rem;">Salmo 147:1</span>
                </p>

                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="<?php echo h($link_spotify_geral); ?>" target="_blank" rel="noopener" class="btn-social-global">
                        <img src="Spotify.png" alt="Spotify"> Playlist Spotify
                    </a>
                    <a href="<?php echo h($link_youtube_geral); ?>" target="_blank" rel="noopener" class="btn-social-global">
                        <img src="Youtube.png" alt="YouTube"> Playlist YouTube
                    </a>
                </div>
            </header>
        </div>

        <hr class="linha-ponta-a-ponta">

        <div class="container mb-5" style="max-width: 1040px;">
            <?php if (!empty($erro_banco)): ?>
                <div class="alert alert-danger border-0 shadow-sm text-center mb-4" style="border-radius: 14px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo h($erro_banco); ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="search-panel">
                <div class="input-group align-items-center">
                    <span class="px-3 text-success"><i class="bi bi-search fs-5"></i></span>
                    <input type="text" name="busca" id="busca" class="form-control px-0" placeholder="Pesquisar música ou artista..." value="<?php echo h($busca); ?>" onkeyup="filtrarTexto()" autocomplete="off">

                    <div class="badge-total-inline">
                        <i class="bi bi-music-note-list text-success"></i>
                        <span>Louvores: <span class="text-success fw-bold" id="contadorVisivel"><?php echo count($musicas); ?></span></span>
                    </div>

                    <button type="submit" class="btn btn-success fw-bold px-4" style="border-radius: 12px !important; min-height: 42px;">
                        Buscar
                    </button>
                </div>
            </form>

            <div class="section-heading">
                <h2><i class="bi bi-music-note-list text-success me-1"></i> Louvores disponíveis</h2>
                <small class="text-muted">Clique em uma música para abrir a letra</small>
            </div>

            <div class="row g-3 text-start" id="listaMusicas">
                <?php if (empty($musicas)): ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-search fs-2 d-block mb-2 text-success"></i>
                            Nenhuma música encontrada.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($musicas as $m): ?>
                        <div class="col-12 col-md-6 item-musica"
                             data-titulo="<?php echo h(function_exists('mb_strtolower') ? mb_strtolower($m['titulo'], 'UTF-8') : strtolower($m['titulo'])); ?>"
                             data-artista="<?php echo h(function_exists('mb_strtolower') ? mb_strtolower($m['artista'], 'UTF-8') : strtolower($m['artista'])); ?>">

                            <div class="card musica-row-card p-3 d-flex flex-row justify-content-between align-items-center gap-3">
                                <a href="musica.php?id=<?php echo (int)$m['id']; ?>" class="link-letra-zona">
                                    <div>
                                        <h6 class="music-title"><?php echo h($m['titulo']); ?></h6>
                                        <small class="music-artist"><i class="bi bi-person-circle text-success"></i> <?php echo h($m['artista']); ?></small>
                                    </div>
                                </a>

                                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                                    <?php if (!empty($m['link_spotify'])): ?>
                                        <a href="<?php echo h($m['link_spotify']); ?>" target="_blank" rel="noopener" class="row-media-btn" title="Ouvir no Spotify">
                                            <img src="Spotify.png" alt="Spotify">
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($m['link_youtube'])): ?>
                                        <a href="<?php echo h($m['link_youtube']); ?>" target="_blank" rel="noopener" class="row-media-btn" title="Assistir no YouTube">
                                            <img src="Youtube.png" alt="YouTube">
                                        </a>
                                    <?php endif; ?>

                                    <a href="musica.php?id=<?php echo (int)$m['id']; ?>" class="row-media-btn row-letra-btn" title="Abrir letra">
                                        <i class="bi bi-file-text"></i> Letra
                                    </a>

                                    <?php if ($pode_ver_cifra): ?>
                                        <a href="cifra.php?id=<?php echo (int)$m['id']; ?>" class="row-media-btn row-cifra-btn" title="Abrir cifra"><i class="bi bi-music-note-beamed"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php if ($is_admin): ?>
        <div class="admin-floating-action d-flex gap-2 flex-wrap justify-content-end">
            <a href="link_personalizado.php" class="btn btn-outline-success px-4 py-2 d-inline-flex align-items-center gap-2 rounded-pill fw-bold shadow-sm">
                <i class="bi bi-link-45deg"></i> Link Personalizado
            </a>
            <a href="cadastrar.php" class="btn btn-success px-4 py-2 d-inline-flex align-items-center gap-2 rounded-pill fw-bold shadow-sm">
                <i class="bi bi-plus-lg"></i> Novo Louvor
            </a>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const temaSalvo = localStorage.getItem("tema") || "dark";
            document.documentElement.setAttribute("data-bs-theme", temaSalvo);
            atualizarIcone(temaSalvo);
            filtrarTexto();
        });

        function alternarTema() {
            const html = document.documentElement;
            const novoTema = html.getAttribute("data-bs-theme") === "light" ? "dark" : "light";
            html.setAttribute("data-bs-theme", novoTema);
            localStorage.setItem("tema", novoTema);
            atualizarIcone(novoTema);
        }

        function atualizarIcone(tema) {
            const icone = document.getElementById("iconeTema");
            if (icone) {
                icone.className = tema === "dark" ? "bi bi-sun-fill" : "bi bi-moon-stars-fill";
            }
        }

        function normalizarTexto(texto) {
            return (texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function filtrarTexto() {
            const campo = document.getElementById("busca");
            const termo = normalizarTexto(campo ? campo.value : '');
            const itens = document.querySelectorAll(".item-musica");
            let visiveis = 0;

            itens.forEach(item => {
                const titulo = normalizarTexto(item.getAttribute("data-titulo"));
                const artista = normalizarTexto(item.getAttribute("data-artista"));
                const mostrar = titulo.includes(termo) || artista.includes(termo);
                item.style.display = mostrar ? "block" : "none";
                if (mostrar) visiveis++;
            });

            const contador = document.getElementById('contadorVisivel');
            if (contador) contador.textContent = visiveis;
        }
    </script>
</body>
</html>
