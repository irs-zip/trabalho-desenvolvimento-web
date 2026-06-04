<?php
// Proteção: apenas logados
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <span class="navbar-brand">Sistema de Chamados</span>
            <div class="text-white">
                Olá, <span id="nome-usuario-logado"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>!
                <a href="/php/logout.php" class="btn btn-outline-danger btn-sm ms-3">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4">
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Meus Dados</h5>
    </div>
    <div class="card-body">
        <p class="mb-2">
            <strong>Nome:</strong> <br>
            <span id="display-nome" class="text-muted">Carregando...</span>
        </p>
        <p class="mb-2">
            <strong>E-mail:</strong> <br>
            <span id="display-email" class="text-muted">Carregando...</span>
        </p>
        <p class="mb-0">
            <strong>Telefone:</strong> <br>
            <span id="display-telefone" class="text-muted">Carregando...</span>
        </p>
    </div>
</div>

                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">Novo Chamado</div>
                    <div class="card-body">
<form id="form-abrir-chamado">
    <input type="text" name="titulo" id="chamado-titulo" class="form-control mb-2" placeholder="Título" required>
    
    <select name="departamento" id="chamado-departamento" class="form-select mb-2" required>
        <option value="">Departamento...</option>
        <option value="TI">TI</option>
        <option value="RH">RH</option>
        <option value="Financeiro">Financeiro</option>
    </select>
    
    <select name="regiao" id="chamado-regiao" class="form-select mb-2" required>
        <option value="">Região...</option>
        <option value="Sudeste">Sudeste</option>
        <option value="Sul">Sul</option>
        <option value="Norte">Norte</option>
    </select>
    
    <input type="text" name="responsavel" id="chamado-responsavel" class="form-control mb-2" placeholder="Responsável" required>
    
    <textarea name="descricao" id="chamado-descricao" class="form-control mb-2" placeholder="Descrição" required></textarea>
    
    <button type="submit" class="btn btn-primary w-100">Registrar</button>
</form>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Meus Chamados</div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>ID</th><th>Título</th><th>Depto</th><th>Status</th><th>Ação</th></tr>
                            </thead>
                            <tbody id="tabela-chamados-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarChamado" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-body">
            <form id="form-editar-chamado">
                <input type="hidden" id="edit-id-chamado">
                <select id="edit-status" class="form-select mb-2">
                    <option value="Em aberto">Em aberto</option>
                    <option value="Em análise">Em análise</option>
                    <option value="Resolvido">Resolvido</option>
                </select>
                <textarea id="edit-descricao" class="form-control" placeholder="Complemento"></textarea>
                <button type="submit" class="btn btn-primary mt-2">Salvar</button>
            </form>
        </div></div></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/ajax.js"></script>
</body>
</html>