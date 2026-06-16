<?php
require_once '../config.php';
require_login();

$mensagem = "";

$busca = $_GET['busca'] ?? '';
$filtro_disponivel = $_GET['disponivel'] ?? '';

try {
    $sql = "SELECT * FROM livros WHERE 1=1";
    $params = [];

    if (!empty($busca)) {
        $sql .= " AND (titulo LIKE :busca OR autor LIKE :busca)";
        $params[':busca'] = "%$busca%";
    }

    if ($filtro_disponivel === 'sim') {
        $sql .= " AND disponivel = 1";
    } elseif ($filtro_disponivel === 'nao') {
        $sql .= " AND disponivel = 0";
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $livros = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao listar livros: " . $e->getMessage());
    $livros = [];
    $mensagem = show_message("Erro ao carregar livros.", "erro");
}

if (isset($_SESSION['sucesso_exclusao'])) {
    $mensagem = show_message($_SESSION['sucesso_exclusao'], "success");
    unset($_SESSION['sucesso_exclusao']);
}
if (isset($_SESSION['erro_exclusao'])) {
    $mensagem = show_message($_SESSION['erro_exclusao'], "erro");
    unset($_SESSION['erro_exclusao']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Livros - Biblioteca</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <nav class="menu">
        <?php if (is_admin()): ?>
        <div class="dropdown">
            <button class="dropbtn">👥 Usuários</button>
            <div class="dropdown-content">
                <a href="../usuarios/cadastrar.php">Cadastrar Usuário</a>
                <a href="../usuarios/listar.php">Listar Usuários</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="dropdown">
            <button class="dropbtn">📚 Livros</button>
            <div class="dropdown-content">
                <?php if (is_admin()): ?>
                <a href="cadastrar.php">Cadastrar Livro</a>
                <?php endif; ?>
                <a href="listar.php">Ver Catálogo</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">📖 Aluguéis</button>
            <div class="dropdown-content">
                <a href="../alugueis/cadastrar.php">Alugar Livro</a>
                <a href="../alugueis/listar.php">Meus Aluguéis</a>
            </div>
        </div>

        <a href="../logout.php" class="logout">Sair</a>
    </nav>

    <div class="container">
        <h1>📚 Catálogo de Livros</h1>

        <?= $mensagem ?>

        <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text"
                   name="busca"
                   placeholder="🔍 Buscar por título ou autor..."
                   value="<?= sanitize_input($busca) ?>"
                   style="flex: 1; min-width: 200px;">

            <select name="disponivel" style="width: auto;">
                <option value="">Todos</option>
                <option value="sim" <?= $filtro_disponivel === 'sim' ? 'selected' : '' ?>>✅ Disponíveis</option>
                <option value="nao" <?= $filtro_disponivel === 'nao' ? 'selected' : '' ?>>❌ Indisponíveis</option>
            </select>

            <button type="submit" class="btn" style="width: auto; padding: 10px 20px;">Filtrar</button>
            <?php if ($busca || $filtro_disponivel): ?>
            <a href="listar.php" class="btn-voltar" style="width: auto; padding: 10px 20px; margin: 0;">Limpar</a>
            <?php endif; ?>
        </form>

        <div style="margin-bottom: 15px; color: #666;">
            <strong>Total:</strong> <?= count($livros) ?> livro(s) encontrado(s)
        </div>

        <?php if (empty($livros)): ?>
            <div class="info-box" style="text-align: center; padding: 40px;">
                <strong style="font-size: 48px; display: block; margin-bottom: 15px;">📚</strong>
                <strong>Nenhum livro encontrado</strong>
                <?php if ($busca || $filtro_disponivel): ?>
                <p>Tente ajustar os filtros de busca</p>
                <?php elseif (is_admin()): ?>
                <p>Comece cadastrando alguns livros!</p>
                <a href="cadastrar.php" class="btn" style="display: inline-block; margin-top: 15px;">Cadastrar Livro</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="books-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
            <?php foreach ($livros as $livro): ?>
            <div class="book-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s;">
                <?php if ($livro['imagem']): ?>
                    <img src="../<?= sanitize_input($livro['imagem']) ?>" alt="<?= sanitize_input($livro['titulo']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #e9ecef, #dee2e6); display: flex; align-items: center; justify-content: center; font-size: 48px;">📚</div>
                <?php endif; ?>

                <div style="padding: 12px;">
                    <div style="font-weight: bold; font-size: 14px; margin-bottom: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= sanitize_input($livro['titulo']) ?></div>
                    <div style="font-size: 12px; color: #6c757d; margin-bottom: 8px;"><?= sanitize_input($livro['autor']) ?></div>
                    <span style="display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; <?= $livro['disponivel'] ? 'background: #d1e7dd; color: #0f5132;' : 'background: #f8d7da; color: #842029;' ?>">
                        <?= $livro['disponivel'] ? '✅ Disponível' : '❌ Indisponível' ?>
                    </span>
                </div>

                <?php if (is_admin()): ?>
                <div style="padding: 8px 12px 12px; display: flex; gap: 8px; border-top: 1px solid #eee;">
                    <a href="editar.php?id=<?= $livro['id'] ?>" style="text-decoration: none; padding: 4px 10px; border-radius: 6px; background: #e7f3ff; color: #0d6efd; font-size: 12px;">✏️ Editar</a>
                    <a href="excluir.php?id=<?= $livro['id'] ?>" style="text-decoration: none; padding: 4px 10px; border-radius: 6px; background: #f8d7da; color: #dc3545; font-size: 12px;" onclick="return confirm('Excluir este livro? Esta ação não pode ser desfeita.')">🗑️ Excluir</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <?php if (is_admin()): ?>
            <a href="cadastrar.php" class="btn">+ Cadastrar Novo Livro</a>
            <?php endif; ?>
            <a href="../painel.php" class="btn-voltar">← Voltar ao Painel</a>
        </div>
    </div>
</body>
</html>
