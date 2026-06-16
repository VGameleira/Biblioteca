<?php
require_once '../config.php';
require_login();

$mensagem = "";

// Buscar livros disponíveis
try {
    $sql = "SELECT * FROM livros WHERE disponivel = 1 ORDER BY titulo";
    $livros_disponiveis = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar livros: " . $e->getMessage());
    $livros_disponiveis = [];
}

// Buscar usuários (apenas admin)
$usuarios = [];
if (is_admin()) {
    try {
        $sql = "SELECT id, nome, email FROM usuarios ORDER BY nome";
        $usuarios = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao buscar usuários: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_livro = filter_input(INPUT_POST, 'id_livro', FILTER_VALIDATE_INT);
    $data_devolucao = $_POST['data_devolucao'] ?? '';
    
    // Validações
    if (!$id_livro || empty($data_devolucao)) {
        $mensagem = show_message("Preencha todos os campos.", "erro");
    } else {
        // Determinar usuário
        if (is_admin()) {
            $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
            if (!$id_usuario) {
                $mensagem = show_message("Selecione um usuário.", "erro");
            }
        } else {
            $id_usuario = $_SESSION['usuario_id'];
        }
        
        if (!$mensagem) {
            try {
                $pdo->beginTransaction();
                
                // Verificar se o livro está disponível
                $sql = "SELECT disponivel FROM livros WHERE id = :id FOR UPDATE";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id_livro]);
                $livro = $stmt->fetch();
                
                if (!$livro || !$livro['disponivel']) {
                    throw new Exception("Livro não está mais disponível.");
                }
                
                // Inserir aluguel
                $sql = "INSERT INTO alugueis (id_usuario, id_livro, data_aluguel, data_devolucao, devolvido) 
                        VALUES (:id_usuario, :id_livro, CURDATE(), :data_devolucao, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_usuario' => $id_usuario,
                    ':id_livro' => $id_livro,
                    ':data_devolucao' => $data_devolucao
                ]);
                
                // Marcar livro como indisponível
                $sql = "UPDATE livros SET disponivel = 0 WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id_livro]);
                
                $pdo->commit();
                
                $mensagem = show_message("Livro alugado com sucesso!", "success");
                
                // Atualizar lista de livros disponíveis
                $sql = "SELECT * FROM livros WHERE disponivel = 1 ORDER BY titulo";
                $livros_disponiveis = $pdo->query($sql)->fetchAll();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Erro ao alugar livro: " . $e->getMessage());
                $mensagem = show_message($e->getMessage(), "erro");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alugar Livro - Sistema Biblioteca</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h1>📖 Alugar Livro</h1>
        
        <?= $mensagem ?>
        
        <?php if (empty($livros_disponiveis)): ?>
            <div class="info-box" style="background: #f8d7da; border-color: #dc3545; color: #842029;">
                <strong>⚠️ Nenhum livro disponível</strong>
                Não há livros disponíveis para aluguel no momento.
            </div>
            <a href="../painel.php" class="btn-voltar">← Voltar ao Painel</a>
        <?php else: ?>
            <div class="info-box">
                <strong>ℹ️ Informações</strong>
                Você tem <?= count($livros_disponiveis) ?> livro(s) disponível(is) para aluguel.
            </div>
            
            <form method="POST" id="formAluguel">
                <?php if (is_admin()): ?>
                    <div class="form-group">
                        <label for="id_usuario">Usuário *</label>
                        <select id="id_usuario" name="id_usuario" required>
                            <option value="">Selecione um usuário</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id'] ?>">
                                    <?= sanitize_input($usuario['nome']) ?> 
                                    (<?= sanitize_input($usuario['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="id_livro">Livro *</label>
                    <select id="id_livro" name="id_livro" required>
                        <option value="">Selecione um livro</option>
                        <?php foreach ($livros_disponiveis as $livro): ?>
                            <option value="<?= $livro['id'] ?>" 
                                    data-titulo="<?= sanitize_input($livro['titulo']) ?>"
                                    data-autor="<?= sanitize_input($livro['autor']) ?>"
                                    data-imagem="<?= sanitize_input($livro['imagem']) ?>">
                                <?= sanitize_input($livro['titulo']) ?> - <?= sanitize_input($livro['autor']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="livroPreview" class="livro-preview"></div>
                
                <div class="form-group">
                    <label for="data_devolucao">Data de Devolução *</label>
                    <input type="date" 
                           id="data_devolucao" 
                           name="data_devolucao"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           value="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                           required>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Prazo sugerido: 14 dias
                    </small>
                </div>
                
                <button type="submit" class="btn">✓ Confirmar Aluguel</button>
                <a href="../painel.php" class="btn-voltar">← Voltar ao Painel</a>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        const livroSelect = document.getElementById('id_livro');
        const livroPreview = document.getElementById('livroPreview');
        
        livroSelect?.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            
            if (this.value) {
                const titulo = option.dataset.titulo;
                const autor = option.dataset.autor;
                const imagem = option.dataset.imagem;
                
                let html = '';
                if (imagem) {
                    html += `<img src="../${imagem}" alt="${titulo}">`;
                }
                html += `<div class="livro-preview-info">`;
                html += `<h3>📚 ${titulo}</h3>`;
                html += `<p><strong>Autor:</strong> ${autor}</p>`;
                html += `</div>`;
                html += `<div style="clear: both;"></div>`;
                
                livroPreview.innerHTML = html;
                livroPreview.classList.add('show');
            } else {
                livroPreview.classList.remove('show');
            }
        });
    </script>
</body>
</html>