<?php
$codigo = isset($_GET['sessao']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['sessao']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legenda ao vivo</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
        }

        .caption-wrapper {
            position: fixed;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            padding: 0 4vw;
            box-sizing: border-box;
            pointer-events: none;
        }

        .caption-wrapper.baixo { bottom: 5vh; }
        .caption-wrapper.meio { top: 50%; transform: translateY(-50%); }
        .caption-wrapper.cima { top: 5vh; }

        .caption-box {
            max-width: 92vw;
            color: #ffffff;
            padding: 22px 34px;
            border-radius: 18px;
            font-size: 42px;
            line-height: 1.25;
            font-weight: 800;
            text-align: center;
            text-shadow: 2px 2px 7px #000;
            box-shadow: 0 12px 40px rgba(0,0,0,0.45);
            transition: all 0.2s ease;
            white-space: normal;
        }

        .caption-box.sem-caixa {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0;
        }

        .status {
            position: fixed;
            top: 12px;
            right: 18px;
            color: rgba(255,255,255,0.65);
            font-size: 14px;
            background: rgba(0,0,0,0.35);
            padding: 6px 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="status" id="status">iniciando</div>

    <div class="caption-wrapper baixo" id="wrapper">
        <div class="caption-box" id="caption">Aguardando legenda...</div>
    </div>

    <script>
        const sessao = <?php echo json_encode($codigo); ?>;
        const caption = document.getElementById('caption');
        const statusEl = document.getElementById('status');
        const wrapper = document.getElementById('wrapper');
        let ultimoTexto = '';

        async function atualizarLegenda() {
            if (!sessao) {
                caption.textContent = 'Sessão não informada.';
                statusEl.textContent = 'erro';
                return;
            }

            try {
                const resposta = await fetch('legenda_api.php?action=get&sessao=' + encodeURIComponent(sessao) + '&t=' + Date.now());
                const dados = await resposta.json();

                if (!dados.ok) {
                    caption.textContent = dados.erro || 'Sessão não encontrada.';
                    statusEl.textContent = 'erro';
                    return;
                }

                const texto = dados.texto || '';
                if (texto !== ultimoTexto) {
                    caption.textContent = texto || '...';
                    ultimoTexto = texto;
                }

                statusEl.textContent = dados.status || 'ok';
                caption.style.color = dados.cor_texto || '#ffffff';
                caption.style.fontSize = (dados.tamanho_fonte || 42) + 'px';
                caption.style.background = dados.mostrar_caixa == 1 ? (dados.cor_fundo || 'rgba(0,0,0,0.72)') : 'transparent';
                caption.classList.toggle('sem-caixa', dados.mostrar_caixa != 1);

                wrapper.className = 'caption-wrapper ' + (dados.posicao || 'baixo');
            } catch (e) {
                statusEl.textContent = 'sem conexão';
            }
        }

        setInterval(atualizarLegenda, 450);
        atualizarLegenda();
    </script>
</body>
</html>
