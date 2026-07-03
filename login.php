<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$erro = '';
$usuario_digitado = '';

function normalizar_tipo_login($valor) {
    $valor = strtolower(trim((string)$valor));
    $valor = str_replace(
        ['á','à','ã','â','ä','é','ê','ë','í','ï','ó','ô','õ','ö','ú','ü','ç'],
        ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c'],
        $valor
    );

    if (in_array($valor, ['admin', 'administrador', 'ipa'], true)) return 'admin';
    if (in_array($valor, ['musico', 'musicos', 'instrumentista', 'cifra', 'cifras'], true)) return 'musico';
    if (in_array($valor, ['vocal', 'voz', 'letra', 'letras'], true)) return 'vocal';
    return $valor !== '' ? $valor : 'vocal';
}

function senha_confere_login($senha_digitada, $senha_salva) {
    $senha_salva = (string)$senha_salva;
    if ($senha_salva === '') return false;

    // Senhas geradas com password_hash.
    if (preg_match('/^\$2y\$|^\$argon2/i', $senha_salva)) {
        return password_verify($senha_digitada, $senha_salva);
    }

    // Compatibilidade com registros antigos em texto puro.
    return hash_equals($senha_salva, $senha_digitada);
}

function permissoes_padrao_login($tipo) {
    $tipo = normalizar_tipo_login($tipo);
    $permissoes = [
        'pode_ver_letras' => 1,
        'pode_ver_comentarios' => 1,
        'pode_ver_cifras' => 0,
        'pode_criar_links' => 0,
        'pode_ver_historico' => 0,
        'pode_cadastrar_musicas' => 0,
        'pode_editar_musicas' => 0,
        'pode_excluir_musicas' => 0,
        'pode_acessar_dashboard' => 0,
        'pode_usar_projecao' => 0,
        'pode_usar_legenda' => 0
    ];

    if (in_array($tipo, ['admin', 'administrador', 'ipa'], true)) {
        foreach ($permissoes as $k => $v) $permissoes[$k] = 1;
    } elseif (in_array($tipo, ['musico', 'musicos', 'instrumentista', 'cifra', 'cifras'], true)) {
        $permissoes['pode_ver_cifras'] = 1;
    }

    return $permissoes;
}

function montar_permissoes_login($tipo, $linha_usuario = null) {
    $permissoes = permissoes_padrao_login($tipo);
    if (is_array($linha_usuario)) {
        foreach ($permissoes as $campo => $valor) {
            if (array_key_exists($campo, $linha_usuario)) {
                $permissoes[$campo] = (int)$linha_usuario[$campo] === 1 ? 1 : 0;
            }
        }
    }
    return $permissoes;
}

function buscar_usuario_banco($pdo, $usuario) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    } catch (Exception $e) {
        // Mantém fallback abaixo.
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    } catch (Exception $e) {
        // Mantém fallback abaixo.
    }

    return null;
}

// Se já estiver logado e acessar o login novamente, manda para o painel interno
if (isset($_SESSION['usuario']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['fluxo_publico'], $_SESSION['playlist_code_atual']);
    header('Location: index.php');
    exit;
}

// Só processa e valida se o formulário realmente foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $usuario_digitado = $usuario;

    $login_ok = false;
    $tipo_final = '';
    $nome_final = $usuario;
    $usuario_banco_logado = null;

    // 1) Primeiro tenta validar pela tabela usuarios do banco.
    if ($usuario !== '') {
        $usuario_banco = buscar_usuario_banco($pdo, $usuario);
        if ($usuario_banco) {
            $senha_salva = $usuario_banco['senha_hash'] ?? $usuario_banco['senha'] ?? $usuario_banco['password'] ?? '';
            if (senha_confere_login($senha, $senha_salva)) {
                $login_ok = true;
                $nome_final = $usuario_banco['usuario'] ?? $usuario_banco['nome'] ?? $usuario;
                $tipo_final = normalizar_tipo_login($usuario_banco['tipo'] ?? $usuario_banco['perfil'] ?? 'vocal');
                $usuario_banco_logado = $usuario_banco;
            }
        }
    }

    // 2) Fallback para os usuários antigos do sistema, caso a tabela ainda não tenha sido preenchida.
    if (!$login_ok) {
        $usuarioCorreto = 'IPA';
        $hashSenha = '$2y$10$qhonf9n0YV3Z8NPPUkP7O.HyhyiyRfAehuwZQlcxOgH34RbvqWnKq';

        if ($usuario === $usuarioCorreto && password_verify($senha, $hashSenha)) {
            $login_ok = true;
            $nome_final = 'IPA';
            $tipo_final = 'admin';
        } elseif (in_array($usuario, ['Musicos', 'Musico'], true) && $senha === '123') {
            $login_ok = true;
            $nome_final = $usuario;
            $tipo_final = 'musico';
        } elseif ($usuario === 'Vocal' && $senha === '123') {
            $login_ok = true;
            $nome_final = 'Vocal';
            $tipo_final = 'vocal';
        }
    }

    if ($login_ok) {
        session_regenerate_id(true);
        $_SESSION['logado'] = true;
        $_SESSION['usuario'] = $nome_final;
        $_SESSION['tipo'] = $tipo_final;

        // Permissões individuais do usuário.
        // Se as colunas ainda não existirem no banco, usa o padrão pelo tipo.
        $permissoes_final = montar_permissoes_login($tipo_final, $usuario_banco_logado ?? null);
        foreach ($permissoes_final as $campo_perm => $valor_perm) {
            $_SESSION[$campo_perm] = (int)$valor_perm;
        }

        unset($_SESSION['fluxo_publico'], $_SESSION['playlist_code_atual']);

        header('Location: index.php');
        exit;
    }

    $erro = 'Usuário ou senha inválidos.';
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Letras IPA</title>

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
            --input-placeholder: #7b8190;
            --hero-glow: rgba(0, 86, 59, 0.12);
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
            --input-bg: #111419;
            --input-placeholder: #737b8c;
            --hero-glow: rgba(0, 168, 107, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top center, var(--hero-glow), transparent 34rem),
                var(--bg-principal);
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
            max-width: 1080px;
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

        .nav-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.45rem;
        }

        .btn-nav-pill,
        .btn-nav-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 1px solid var(--borda-nav);
            color: var(--texto-nav) !important;
            background: rgba(255, 255, 255, 0.04);
            text-decoration: none;
            border-radius: 999px;
            min-height: 38px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-nav-pill {
            padding: 0.48rem 0.9rem;
        }

        .btn-nav-icon {
            width: 38px;
            padding: 0;
        }

        .btn-nav-pill:hover,
        .btn-nav-icon:hover {
            border-color: var(--verde-presbiteriano);
            color: var(--verde-presbiteriano) !important;
            transform: translateY(-1px);
        }

        .login-shell {
            min-height: calc(100vh - 62px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }

        .login-layout {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1fr 430px;
            gap: 1.5rem;
            align-items: stretch;
        }

        .login-hero,
        .login-card {
            background-color: var(--bg-card);
            border: 1px solid var(--borda-suave);
            border-radius: 24px;
            box-shadow: var(--sombras);
            overflow: hidden;
        }

        .login-hero {
            padding: 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            min-height: 460px;
        }

        .login-hero::after {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: var(--hero-glow);
            right: -70px;
            bottom: -80px;
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            border: 1px solid rgba(0, 168, 107, 0.25);
            color: var(--verde-presbiteriano);
            background: rgba(0, 168, 107, 0.08);
            border-radius: 999px;
            padding: 0.45rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 1.2rem;
        }

        .hero-title {
            font-family: 'Syne', sans-serif;
            color: var(--verde-presbiteriano);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 0.96;
            font-size: clamp(2.2rem, 5vw, 4.4rem);
            margin: 0 0 1rem;
        }

        .hero-text {
            color: var(--texto-secundario);
            font-size: 1rem;
            line-height: 1.65;
            max-width: 36rem;
            margin: 0;
        }

        .quick-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 2rem;
            position: relative;
            z-index: 1;
        }

        .quick-info-item {
            border: 1px solid var(--borda-suave);
            border-radius: 16px;
            padding: 0.9rem;
            background: rgba(255, 255, 255, 0.025);
        }

        .quick-info-item i {
            color: var(--verde-presbiteriano);
            font-size: 1.2rem;
            margin-bottom: 0.45rem;
        }

        .quick-info-item strong {
            display: block;
            font-size: 0.9rem;
            color: var(--texto-principal);
        }

        .quick-info-item span {
            display: block;
            color: var(--texto-secundario);
            font-size: 0.78rem;
            margin-top: 0.15rem;
        }

        .login-card {
            padding: 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo-wrap {
            width: 78px;
            height: 78px;
            margin: 0 auto 1rem;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--borda-suave);
            box-shadow: var(--sombras);
        }

        .login-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
        }

        .login-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--texto-principal);
            margin: 0;
            font-size: 1.7rem;
        }

        .login-subtitle {
            color: var(--texto-secundario);
            margin-top: 0.4rem;
            font-size: 0.92rem;
        }

        .form-label-mini {
            color: var(--texto-secundario);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.45rem;
        }

        .input-group-custom {
            background-color: var(--input-bg);
            border: 1px solid var(--borda-suave);
            border-radius: 14px;
            min-height: 50px;
            padding: 0.3rem 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }

        .input-group-custom:focus-within {
            border-color: var(--verde-presbiteriano);
            box-shadow: 0 0 0 0.25rem rgba(0, 168, 107, 0.14);
        }

        .input-icon {
            color: var(--texto-secundario);
            font-size: 1.1rem;
            flex: 0 0 auto;
        }

        .form-control-custom {
            background: transparent !important;
            border: none !important;
            color: var(--texto-principal) !important;
            font-weight: 600;
            padding: 0.55rem 0.1rem;
            font-size: 0.96rem;
            width: 100%;
            outline: none !important;
        }

        .form-control-custom::placeholder {
            color: var(--input-placeholder);
            font-weight: 500;
        }

        .form-control-custom:focus {
            box-shadow: none !important;
            outline: none !important;
        }

        .btn-password-toggle {
            border: 0;
            background: transparent;
            color: var(--texto-secundario);
            padding: 0.35rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-password-toggle:hover {
            color: var(--verde-presbiteriano);
            background: rgba(0, 168, 107, 0.08);
        }

        .btn-login {
            background-color: var(--verde-presbiteriano);
            color: #ffffff !important;
            font-weight: 800;
            font-size: 0.98rem;
            letter-spacing: 0.01em;
            padding: 0.82rem 1rem;
            border-radius: 14px;
            border: none;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 8px 18px rgba(0, 168, 107, 0.22);
        }

        .btn-login:hover {
            opacity: 0.92;
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(0, 168, 107, 0.28);
        }

        .btn-login:active { transform: translateY(0); }

        .alert-custom {
            background-color: rgba(220, 53, 69, 0.10);
            border: 1px solid rgba(220, 53, 69, 0.22);
            color: #dc3545;
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 14px;
            padding: 0.8rem 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .public-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            color: var(--texto-secundario);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            margin-top: 1.15rem;
            transition: all 0.2s ease;
        }

        .public-link:hover {
            color: var(--verde-presbiteriano);
        }

        .footer-note {
            text-align: center;
            color: var(--texto-secundario);
            font-size: 0.78rem;
            margin-top: 1.4rem;
        }

        @media (max-width: 900px) {
            .login-layout {
                grid-template-columns: 1fr;
                max-width: 520px;
            }

            .login-hero {
                min-height: auto;
                padding: 1.5rem;
            }

            .quick-info { display: none; }
        }

        @media (max-width: 576px) {
            .nav-corporate-container {
                align-items: center;
            }

            .brand-logo-zone span {
                display: none;
            }

            .btn-nav-pill span {
                display: none;
            }

            .login-shell {
                padding: 1.4rem 0.85rem 2.4rem;
            }

            .login-card,
            .login-hero {
                border-radius: 20px;
                padding: 1.35rem;
            }

            .quick-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <nav class="nav-corporate-bar sticky-top">
        <div class="nav-corporate-container">
            <a class="brand-logo-zone" href="index_publico.php">
                <img src="Igreja presbiteriana aliança sem fundo preta.png" alt="Logo Aliança" class="nav-brand-img">
                <span>Repertório Aliança</span>
            </a>

            <div class="nav-actions">
                <a href="index_publico.php" class="btn-nav-pill" title="Acessar repertório público">
                    <i class="bi bi-globe2"></i> <span>Público</span>
                </a>
                <button type="button" class="btn-nav-icon" onclick="alternarTema()" title="Mudar tema">
                    <i class="bi bi-sun-fill" id="iconeTema"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="login-shell">
        <div class="login-layout">
            <section class="login-hero" aria-label="Apresentação do repertório">
                <div>
                    <span class="hero-badge"><i class="bi bi-music-note-list"></i> Letras IPA</span>
                    <h1 class="hero-title">Repertório Aliança</h1>
                    <p class="hero-text">
                        Acesse o painel interno para organizar louvores, gerenciar listas, histórico e recursos da equipe de música.
                    </p>
                </div>

                <div class="quick-info">
                    <div class="quick-info-item">
                        <i class="bi bi-shield-lock-fill"></i>
                        <strong>Área interna</strong>
                        <span>Login para equipe e administradores.</span>
                    </div>
                    <div class="quick-info-item">
                        <i class="bi bi-stars"></i>
                        <strong>Lista pessoal</strong>
                        <span>Monte repertórios e favoritos.</span>
                    </div>
                    <div class="quick-info-item">
                        <i class="bi bi-clock-history"></i>
                        <strong>Histórico</strong>
                        <span>Acompanhe os louvores acessados.</span>
                    </div>
                    <div class="quick-info-item">
                        <i class="bi bi-phone"></i>
                        <strong>Responsivo</strong>
                        <span>Visual adaptado para celular.</span>
                    </div>
                </div>
            </section>

            <section class="login-card" aria-label="Formulário de login">
                <div class="login-header">
                    <div class="login-logo-wrap">
                        <img src="Igreja presbiteriana aliança sem fundo.png" alt="Logo IPA" class="login-logo">
                    </div>
                    <h2 class="login-title">Entrar no sistema</h2>
                    <div class="login-subtitle">Use seu usuário da equipe para continuar.</div>
                </div>

                <?php if ($erro !== ''): ?>
                    <div class="alert-custom mb-4">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?php echo e($erro); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="on">
                    <div class="mb-3">
                        <label for="usuario" class="form-label-mini">Usuário</label>
                        <div class="input-group-custom">
                            <i class="bi bi-person input-icon"></i>
                            <input
                                type="text"
                                name="usuario"
                                id="usuario"
                                class="form-control-custom"
                                placeholder="Digite seu usuário"
                                value="<?php echo e($usuario_digitado); ?>"
                                required
                                autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="senha" class="form-label-mini">Senha</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock input-icon"></i>
                            <input
                                type="password"
                                name="senha"
                                id="senha"
                                class="form-control-custom"
                                placeholder="Digite sua senha"
                                required
                                autocomplete="current-password">
                            <button type="button" class="btn-password-toggle" onclick="alternarSenha()" title="Mostrar ou ocultar senha">
                                <i class="bi bi-eye" id="iconeSenha"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Entrar <i class="bi bi-box-arrow-in-right ms-1"></i>
                    </button>
                </form>

                <div class="text-center">
                    <a href="index_publico.php" class="public-link">
                        <i class="bi bi-globe2"></i> Acessar repertório público
                    </a>
                </div>

                <div class="footer-note">
                    Igreja Presbiteriana Aliança • Repertório de Louvores
                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const temaSalvo = localStorage.getItem('tema') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', temaSalvo);
            atualizarIcone(temaSalvo);
        });

        function alternarTema() {
            const html = document.documentElement;
            const novoTema = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', novoTema);
            localStorage.setItem('tema', novoTema);
            atualizarIcone(novoTema);
        }

        function atualizarIcone(tema) {
            const iconeTema = document.getElementById('iconeTema');
            if (!iconeTema) return;
            iconeTema.className = tema === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }

        function alternarSenha() {
            const inputSenha = document.getElementById('senha');
            const iconeSenha = document.getElementById('iconeSenha');
            if (!inputSenha || !iconeSenha) return;

            const mostrar = inputSenha.type === 'password';
            inputSenha.type = mostrar ? 'text' : 'password';
            iconeSenha.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    </script>
</body>
</html>
