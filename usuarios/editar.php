<?php
require_once '../config.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('listar.php');
}

try {
    $sql = "SELECT id, nome, email, tipo FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        redirect('listar.php');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar usuário: " . $e->getMessage());
    redirect('listar.php');
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'aluno';

    if (empty($nome) || empty($email)) {
        $mensagem = show_message("Preencha os campos obrigatórios.", "erro");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = show_message("Email inválido.", "erro");
    } elseif (!empty($senha) && strlen($senha) < 6) {
        $mensagem = show_message("Senha deve ter no mínimo 6 caracteres.", "erro");
    } else {
        try {
            $sql = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email, ':id' => $id]);

            if ($stmt->fetch()) {
                $mensagem = show_message("Email já cadastrado para outro usuário.", "erro");
            } else {
                if (!empty($senha)) {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, tipo = :tipo WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nome' => $nome,
                        ':email' => $email,
                        ':senha' => $senha_hash,
                        ':tipo' => $tipo,
                        ':id' => $id
                    ]);
                } else {
                    $sql = "UPDATE usuarios SET nome = :nome, email = :email, tipo = :tipo WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nome' => $nome,
                        ':email' => $email,
                        ':tipo' => $tipo,
                        ':id' => $id
                    ]);
                }

                $_SESSION['sucesso_edicao'] = "Usuário atualizado com sucesso!";
                redirect('listar.php');
            }
        } catch (Exception $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            $mensagem = show_message($e->getMessage(), "erro");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Biblioteca</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <nav class="menu">
        <div class="dropdown">
            <button class="dropbtn">👥 Usuários</button>
            <div class="dropdown-content">
                <a href="cadastrar.php">📝 Cadastrar Usuário</a>
                <a href="listar.php">📋 Listar Usuários</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="dropbtn">📚 Livros</button>
            <div class="dropdown-content">
                <a href="../livros/cadastrar.php">📝 Cadastrar Livro</a>
                <a href="../livros/listar.php">📋 Listar Livros</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="dropbtn">📖 Aluguéis</button>
            <div class="dropdown-content">
                <a href="../alugueis/cadastrar.php">📋 Alugar Livro</a>
                <a href="../alugueis/listar.php">📋 Listar Aluguéis</a>
            </div>
        </div>
        <a href="../logout.php" class="logout">🚪 Sair</a>
    </nav>

    <div class="container">
        <h1>✏️ Editar Usuário</h1>

        <?= $mensagem ?>

        <form method="POST">
            <div class="form-group">
                <label for="nome">👤 Nome Completo *</label>
                <input type="text"
                       id="nome"
                       name="nome"
                       value="<?= sanitize_input($_POST['nome'] ?? $usuario['nome']) ?>"
                       placeholder="Digite o nome completo"
                       required>
            </div>

            <div class="form-group">
                <label for="email">📧 Email *</label>
                <input type="email"
                       id="email"
                       name="email"
                       value="<?= sanitize_input($_POST['email'] ?? $usuario['email']) ?>"
                       placeholder="Digite o email"
                       required>
            </div>

            <div class="form-group">
                <label for="senha">🔒 Nova Senha</label>
                <input type="password"
                       id="senha"
                       name="senha"
                       placeholder="Deixe em branco para manter a atual"
                       minlength="6">
                <small>Mínimo de 6 caracteres. Deixe vazio para não alterar.</small>
            </div>

            <div class="form-group">
                <label for="tipo">👑 Tipo de Usuário</label>
                <select id="tipo" name="tipo">
                    <option value="aluno" <?= ($_POST['tipo'] ?? $usuario['tipo']) === 'aluno' ? 'selected' : '' ?>>👨‍🎓 Aluno</option>
                    <option value="admin" <?= ($_POST['tipo'] ?? $usuario['tipo']) === 'admin' ? 'selected' : '' ?>>👑 Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn">💾 Salvar Alterações</button>
            <a href="listar.php" class="btn-voltar">← Voltar</a>
        </form>
    </div>
</body>
</html>
