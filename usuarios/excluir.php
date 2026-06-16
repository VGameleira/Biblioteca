<?php
require_once '../config.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('listar.php');
}

// Não permitir excluir a si mesmo
if ($id == $_SESSION['usuario_id']) {
    $_SESSION['erro_exclusao'] = "Você não pode excluir sua própria conta.";
    redirect('listar.php');
}

try {
    // Verificar se usuário existe
    $sql = "SELECT nome FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['erro_exclusao'] = "Usuário não encontrado.";
        redirect('listar.php');
    }
    
    // Verificar se há aluguéis ativos
    $sql = "SELECT COUNT(*) as total FROM alugueis WHERE id_usuario = :id AND devolvido = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $alugueis_ativos = $stmt->fetch()['total'];
    
    if ($alugueis_ativos > 0) {
        $_SESSION['erro_exclusao'] = "Não é possível excluir este usuário pois há aluguéis ativos em seu nome.";
        redirect('listar.php');
    }
    
    // Excluir usuário
    $sql = "DELETE FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    $_SESSION['sucesso_exclusao'] = "Usuário '{$usuario['nome']}' excluído com sucesso!";
    redirect('listar.php');
    
} catch (PDOException $e) {
    error_log("Erro ao excluir usuário: " . $e->getMessage());
    $_SESSION['erro_exclusao'] = "Erro ao excluir usuário.";
    redirect('listar.php');
}
?>