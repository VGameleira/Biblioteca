<?php
require_once '../config.php';
require_admin();

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;
    
    if (empty($titulo) || empty($autor)) {
        $mensagem = show_message("Preencha todos os campos obrigatórios.", "erro");
    } else {
        try {
            // Processar upload de imagem
            $imagem = null;
            if (isset($_FILES['imagem'])) {
                $imagem = upload_image($_FILES['imagem']);
            }
            
            // Inserir livro
            $sql = "INSERT INTO livros (titulo, autor, disponivel, imagem) 
                    VALUES (:titulo, :autor, :disponivel, :imagem)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':autor' => $autor,
                ':disponivel' => $disponivel,
                ':imagem' => $imagem
            ]);
            
            $mensagem = show_message("Livro cadastrado com sucesso!", "success");
            $titulo = $autor = '';
            
        } catch (Exception $e) {
            error_log("Erro ao cadastrar livro: " . $e->getMessage());
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
    <title>Cadastrar Livro - Biblioteca</title>
    <link rel="stylesheet" href="../style.css">
                                                   9
</head>
<body>
    <nav class="menu">
        <div class="dropdown">
            <button class="dropbtn">👥 Usuários</button>
            <div class="dropdown-content">
                <a href="../usuarios/cadastrar.php">Cadastrar Usuário</a>
                <a href="../usuarios/listar.php">Listar Usuários</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="dropbtn">📚 Livros</button>
            <div class="dropdown-content">
                <a href="cadastrar.php">Cadastrar Livro</a>
                <a href="listar.php">Listar Livros</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="dropbtn">📖 Aluguéis</button>
            <div class="dropdown-content">
                <a href="../alugueis/cadastrar.php">Alugar Livro</a>
                <a href="../alugueis/listar.php">Listar Aluguéis</a>
            </div>
        </div>
        <a href="../logout.php" class="logout">Sair</a>
    </nav>

    <div class="container">
        <h1>📚 Cadastrar Novo Livro</h1>
        
        <?= $mensagem ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="titulo">Título do Livro *</label>
                <input type="text" 
                       id="titulo" 
                       name="titulo" 
                       value="<?= isset($titulo) ? sanitize_input($titulo) : '' ?>"
                       placeholder="Digite o título do livro"
                       required>
            </div>
            
            <div class="form-group">
                <label for="autor">Autor *</label>
                <input type="text" 
                       id="autor" 
                       name="autor" 
                       value="<?= isset($autor) ? sanitize_input($autor) : '' ?>"
                       placeholder="Digite o nome do autor"
                       required>
            </div>
            
            <div class="form-group">
                <label for="imagem">📷 Imagem da Capa</label>
                <input type="file" 
                       id="imagem" 
                       name="imagem" 
                       accept="image/*"
                       onchange="previewImage(this)">
                <small style="color: #666; display: block; margin-top: 5px;">
                    Formatos aceitos: JPG, JPEG, PNG, GIF, WEBP (Máximo: 5MB)
                </small>
                <img id="preview" class="preview-image" alt="Preview">
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" 
                           name="disponivel" 
                           checked
                           style="width: auto; margin: 0;">
                    <span>✓ Marcar como disponível para aluguel</span>
                </label>
            </div>
            
            <button type="submit" class="btn">✓ Cadastrar Livro</button>
            <a href="listar.php" class="btn-voltar">← Ver Catálogo</a>
        </form>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>