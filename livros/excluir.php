<?php
require_once '../config.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('listar.php');
}

try {
    // Buscar informações do livro
    $sql = "SELECT imagem FROM livros WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $livro = $stmt->fetch();
    
    if (!$livro) {
        redirect('listar.php');
    }
    
    // Verificar se há aluguéis ativos deste livro
    $sql = "SELECT COUNT(*) as total FROM alugueis WHERE id_livro = :id AND devolvido = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $alugueis_ativos = $stmt->fetch()['total'];
    
    if ($alugueis_ativos > 0) {
        $_SESSION['erro_exclusao'] = "Não é possível excluir este livro pois há aluguéis ativos.";
        redirect('listar.php');
    }
    
    // Deletar livro
    $sql = "DELETE FROM livros WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    // Deletar imagem
    delete_image($livro['imagem']);
    
    $_SESSION['sucesso_exclusao'] = "Livro excluído com sucesso!";
    redirect('listar.php');
    
} catch (PDOException $e) {
    error_log("Erro ao excluir livro: " . $e->getMessage());
    $_SESSION['erro_exclusao'] = "Erro ao excluir livro.";
    redirect('listar.php');
}
?>