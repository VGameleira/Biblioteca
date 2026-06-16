<?php
require_once '../config.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('listar.php');
}

$mensagem = "";

// Buscar livro
try {
    $sql = "SELECT * FROM livros WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $livro = $stmt->fetch();
    
    if (!$livro) {
        $_SESSION['erro_exclusao'] = "Livro não encontrado.";
        redirect('listar.php');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar livro: " . $e->getMessage());
    redirect('listar.php');
}

// Processar edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;
    
    if (empty($titulo) || empty($autor)) {
        $mensagem = show_message("Preencha todos os campos obrigatórios.", "erro");
    } else {
        try {
            // Processar upload de nova imagem
            $imagem = $livro['imagem'];
            if (isset($_FILES['imagem'])) {
                $nova_imagem = upload_image($_FILES['imagem'], $livro['imagem']);
                if ($nova_imagem) {
                    $imagem = $nova_imagem;
                }
            }
            
            // Atualizar livro
            $sql = "UPDATE livros SET titulo = :titulo, autor = :autor, disponivel = :disponivel, imagem = :imagem WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':autor' => $autor,
                ':disponivel' => $disponivel,
                ':imagem' => $imagem,
                ':id' => $id
            ]);
            
            $mensagem = show_message("Livro atualizado com sucesso!", "success");
            
            // Atualizar dados do livro
            $livro['titulo'] = $titulo;
            $livro['autor'] = $autor;
            $livro['disponivel'] = $disponivel;
            $livro['imagem'] = $imagem;
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar livro: " . $e->getMessage());
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
    <title>Editar Livro - Biblioteca</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .imagem-atual {
            max-width: 200px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .preview-image {
            max-width: 200px;
            margin: 10px 0;
            border-radius: 8px;
            display: none;
        }
    </style>
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

    <div class="card-editar">
        <h1>✏️ Editar Livro</h1>
        
        <?= $mensagem ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="titulo">Título do Livro *</label>
                <input type="text" 
                       id="titulo" 
                       name="titulo" 
                       value="<?= sanitize_input($livro['titulo']) ?>" 
                       required>
            </div>
            
            <div class="input-group">
                <label for="autor">Autor *</label>
                <input type="text" 
                       id="autor" 
                       name="autor" 
                       value="<?= sanitize_input($livro['autor']) ?>" 
                       required>
            </div>
            
            <div class="input-group">
                <label>📷 Imagem da Capa</label>
                <?php if ($livro['imagem']): ?>
                    <div>
                        <strong>Imagem atual:</strong>
                        <img src="../<?= sanitize_input($livro['imagem']) ?>" 
                             alt="Capa atual" 
                             class="imagem-atual">
                    </div>
                <?php endif; ?>
                
                <input type="file" 
                       id="imagem" 
                       name="imagem" 
                       accept="image/*"
                       onchange="previewImage(this)">
                <small>Envie uma nova imagem para substituir a atual (ou deixe em branco para manter)</small>
                <img id="preview" class="preview-image" alt="Preview">
            </div>
            
            <div class="input-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" 
                           name="disponivel" 
                           <?= $livro['disponivel'] ? 'checked' : '' ?>
                           style="width: auto; margin: 0;">
                    <span>✓ Livro disponível para aluguel</span>
                </label>
            </div>
            
            <button type="submit" class="btn">💾 Salvar Alterações</button>
            <a href="listar.php" class="btn-voltar">← Voltar ao Catálogo</a>
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