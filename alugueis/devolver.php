<?php
require_once '../config.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('listar.php');
}

try {
    $pdo->beginTransaction();
    
    // Buscar informações do aluguel
    $sql = "SELECT a.*, l.titulo FROM alugueis a 
            JOIN livros l ON a.id_livro = l.id 
            WHERE a.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $aluguel = $stmt->fetch();
    
    if (!$aluguel) {
        throw new Exception("Aluguel não encontrado.");
    }
    
    // Verificar se o usuário tem permissão (admin ou dono do aluguel)
    if (!is_admin() && $aluguel['id_usuario'] != $_SESSION['usuario_id']) {
        throw new Exception("Você não tem permissão para devolver este livro.");
    }
    
    // Verificar se já foi devolvido
    if ($aluguel['devolvido']) {
        throw new Exception("Este livro já foi devolvido.");
    }
    
    // Marcar aluguel como devolvido
    $sql = "UPDATE alugueis SET devolvido = 1 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    // Marcar livro como disponível
    $sql = "UPDATE livros SET disponivel = 1 WHERE id = :id_livro";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_livro' => $aluguel['id_livro']]);
    
    $pdo->commit();
    
    $_SESSION['sucesso_devolucao'] = "Livro '{$aluguel['titulo']}' devolvido com sucesso!";
    redirect('listar.php');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao devolver livro: " . $e->getMessage());
    $_SESSION['erro_devolucao'] = $e->getMessage();
    redirect('listar.php');
}
?>