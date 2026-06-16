<?php
require_once '../config.php';
require_login();

$mensagem = "";

// Processar filtros
$filtro_status = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';

try {
    $sql = "SELECT a.*, l.titulo, l.autor, l.imagem, u.nome as nome_usuario 
            FROM alugueis a 
            JOIN livros l ON a.id_livro = l.id 
            JOIN usuarios u ON a.id_usuario = u.id 
            WHERE 1=1";
    $params = [];
    
    // Filtrar por usuário se não for admin
    if (!is_admin()) {
        $sql .= " AND a.id_usuario = :id_usuario";
        $params[':id_usuario'] = $_SESSION['usuario_id'];
    }
    
    // Filtro de busca
    if (!empty($busca)) {
        $sql .= " AND (l.titulo LIKE :busca OR l.autor LIKE :busca OR u.nome LIKE :busca)";
        $params[':busca'] = "%$busca%";
    }
    
    // Filtro de status
    if ($filtro_status === 'ativo') {
        $sql .= " AND a.devolvido = 0";
    } elseif ($filtro_status === 'devolvido') {
        $sql .= " AND a.devolvido = 1";
    } elseif ($filtro_status === 'atrasado') {
        $sql .= " AND a.devolvido = 0 AND a.data_devolucao < CURDATE()";
    }
    
    $sql .= " ORDER BY a.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alugueis = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao listar aluguéis: " . $e->getMessage());
    $alugueis = [];
    $mensagem = show_message("Erro ao carregar aluguéis.", "erro");
}

// Mensagens de sessão
if (isset($_SESSION['sucesso_devolucao'])) {
    $mensagem = show_message($_SESSION['sucesso_devolucao'], "success");
    unset($_SESSION['sucesso_devolucao']);
}
if (isset($_SESSION['erro_devolucao'])) {
    $mensagem = show_message($_SESSION['erro_devolucao'], "erro");
    unset($_SESSION['erro_devolucao']);
}

// Função para calcular dias restantes
function calcular_dias($data_devolucao) {
    $hoje = new DateTime();
    $devolucao = new DateTime($data_devolucao);
    $diff = $hoje->diff($devolucao);
    
    if ($devolucao < $hoje) {
        return -$diff->days; // Negativo = atrasado
    }
    return $diff->days; // Positivo = dias restantes
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Aluguéis - Biblioteca</title>
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
                <a href="../livros/cadastrar.php">Cadastrar Livro</a>
                <?php endif; ?>
                <a href="../livros/listar.php">Ver Catálogo</a>
            </div>
        </div>
        
        <div class="dropdown">
            <button class="dropbtn">📖 Aluguéis</button>
            <div class="dropdown-content">
                <a href="cadastrar.php">Alugar Livro</a>
                <a href="listar.php">Meus Aluguéis</a>
            </div>
        </div>
        
        <a href="../logout.php" class="logout">Sair</a>
    </nav>

    <div class="alugueis-container">
        <h1>📖 <?= is_admin() ? 'Todos os Aluguéis' : 'Meus Aluguéis' ?></h1>
        
        <?= $mensagem ?>
        
        <!-- Filtros -->
        <form method="GET" style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" 
                   name="busca" 
                   placeholder="🔍 Buscar por livro<?= is_admin() ? ' ou usuário' : '' ?>..." 
                   value="<?= sanitize_input($busca) ?>"
                   style="flex: 1; min-width: 200px;">
            
            <select name="status" style="width: auto;">
                <option value="">Todos os status</option>
                <option value="ativo" <?= $filtro_status == 'ativo' ? 'selected' : '' ?>>📖 Ativos</option>
                <option value="devolvido" <?= $filtro_status == 'devolvido' ? 'selected' : '' ?>>✓ Devolvidos</option>
                <option value="atrasado" <?= $filtro_status == 'atrasado' ? 'selected' : '' ?>>⚠️ Atrasados</option>
            </select>
            
            <button type="submit" class="btn" style="width: auto; padding: 10px 20px;">Filtrar</button>
            <?php if ($busca || $filtro_status): ?>
            <a href="listar.php" class="btn-voltar" style="width: auto; padding: 10px 20px; margin: 0;">Limpar</a>
            <?php endif; ?>
        </form>

        <div style="margin-bottom: 15px; color: #666;">
            <strong>Total:</strong> <?= count($alugueis) ?> aluguel(is) encontrado(s)
        </div>

        <?php if (empty($alugueis)): ?>
            <div class="info-box" style="text-align: center; padding: 40px;">
                <strong style="font-size: 48px; display: block; margin-bottom: 15px;">📖</strong>
                <strong>Nenhum aluguel encontrado</strong>
                <?php if ($busca || $filtro_status): ?>
                <p>Tente ajustar os filtros de busca</p>
                <?php else: ?>
                <p>Você ainda não alugou nenhum livro</p>
                <a href="cadastrar.php" class="btn" style="display: inline-block; margin-top: 15px;">
                    Alugar um Livro
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($alugueis as $aluguel): 
                $dias_restantes = calcular_dias($aluguel['data_devolucao']);
                $atrasado = $dias_restantes < 0;
                $proximo_vencimento = $dias_restantes >= 0 && $dias_restantes <= 3;
            ?>
            <div class="aluguel-card">
                <?php if ($aluguel['imagem']): ?>
                    <img src="../<?= sanitize_input($aluguel['imagem']) ?>" 
                         alt="<?= sanitize_input($aluguel['titulo']) ?>" 
                         class="aluguel-imagem">
                <?php else: ?>
                    <div class="aluguel-no-imagem">📚</div>
                <?php endif; ?>
                
                <div class="aluguel-info">
                    <div class="aluguel-titulo"><?= sanitize_input($aluguel['titulo']) ?></div>
                    <div class="aluguel-autor">por <?= sanitize_input($aluguel['autor']) ?></div>
                    
                    <?php if (is_admin()): ?>
                    <div style="margin-bottom: 10px; color: #6c757d; font-size: 14px;">
                        <strong>👤 Usuário:</strong> <?= sanitize_input($aluguel['nome_usuario']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="aluguel-detalhes">
                        <div>
                            <strong>📅 Alugado em:</strong><br>
                            <?= format_date($aluguel['data_aluguel']) ?>
                        </div>
                        <div>
                            <strong>📆 Devolução:</strong><br>
                            <?= format_date($aluguel['data_devolucao']) ?>
                        </div>
                        <?php if (!$aluguel['devolvido']): ?>
                        <div>
                            <strong>⏱️ Status:</strong><br>
                            <?php if ($atrasado): ?>
                                <span style="color: #dc3545; font-weight: bold;">
                                    <?= abs($dias_restantes) ?> dia(s) de atraso
                                </span>
                            <?php else: ?>
                                <span style="color: <?= $proximo_vencimento ? '#856404' : '#198754' ?>;">
                                    <?= $dias_restantes ?> dia(s) restantes
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <?php if ($aluguel['devolvido']): ?>
                            <span class="aluguel-status status-devolvido">✓ Devolvido</span>
                        <?php elseif ($atrasado): ?>
                            <span class="aluguel-status status-atrasado">⚠️ Atrasado</span>
                        <?php elseif ($proximo_vencimento): ?>
                            <span class="aluguel-status status-proximo">⏰ Vence em breve</span>
                        <?php else: ?>
                            <span class="aluguel-status status-ativo">📖 Ativo</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$aluguel['devolvido']): ?>
                <div class="aluguel-acoes">
                    <a href="devolver.php?id=<?= $aluguel['id'] ?>" 
                       class="btn-devolver"
                       onclick="return confirm('Confirma a devolução do livro \'<?= sanitize_input($aluguel['titulo']) ?>\'?')">
                        ✓ Devolver
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <a href="cadastrar.php" class="btn">+ Alugar Novo Livro</a>
            <a href="../painel.php" class="btn-voltar">← Voltar ao Painel</a>
        </div>
    </div>
</body>
</html>