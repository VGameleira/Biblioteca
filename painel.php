<?php
require_once 'config.php';
require_login();

// Buscar estatísticas
try {
    $stats = [];
    
    // Total de livros
    $sql = "SELECT COUNT(*) as total FROM livros";
    $stats['total_livros'] = $pdo->query($sql)->fetch()['total'];
    
    // Livros disponíveis
    $sql = "SELECT COUNT(*) as total FROM livros WHERE disponivel = 1";
    $stats['livros_disponiveis'] = $pdo->query($sql)->fetch()['total'];
    
    // Total de usuários
    $sql = "SELECT COUNT(*) as total FROM usuarios";
    $stats['total_usuarios'] = $pdo->query($sql)->fetch()['total'];
    
    // Aluguéis ativos
    $sql = "SELECT COUNT(*) as total FROM alugueis WHERE devolvido = 0";
    $stats['alugueis_ativos'] = $pdo->query($sql)->fetch()['total'];
    
    // Aluguéis atrasados
    $sql = "SELECT COUNT(*) as total FROM alugueis 
            WHERE devolvido = 0 AND data_devolucao < CURDATE()";
    $stats['alugueis_atrasados'] = $pdo->query($sql)->fetch()['total'];
    
    // Meus aluguéis ativos (para alunos)
    if (!is_admin()) {
        $sql = "SELECT COUNT(*) as total FROM alugueis 
                WHERE id_usuario = :id AND devolvido = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $stats['meus_alugueis'] = $stmt->fetch()['total'];
    }
    
    // Últimos livros cadastrados
    $sql = "SELECT * FROM livros ORDER BY id DESC LIMIT 6";
    $ultimos_livros = $pdo->query($sql)->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Sistema Biblioteca</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(13, 110, 253, 0.3);
        }
        
        .welcome-card h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #0d6efd;
            margin: 10px 0;
        }
        
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .stat-card.danger .number {
            color: #dc3545;
        }
        
        .stat-card.success .number {
            color: #198754;
        }
        
        .section-title {
            font-size: 24px;
            margin: 30px 0 20px;
            color: #222;
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .book-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
        }
        
        .book-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .book-card .no-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        
        .book-card .info {
            padding: 12px;
        }
        
        .book-card .title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .book-card .author {
            font-size: 12px;
            color: #6c757d;
        }
        
        .book-card .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 8px;
        }
        
        .book-card .status.disponivel {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .book-card .status.indisponivel {
            background: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <nav class="menu">
        <?php if (is_admin()): ?>
        <div class="dropdown">
            <button class="dropbtn">👥 Usuários</button>
            <div class="dropdown-content">
                <a href="usuarios/cadastrar.php">Cadastrar Usuário</a>
                <a href="usuarios/listar.php">Listar Usuários</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="dropdown">
            <button class="dropbtn">📚 Livros</button>
            <div class="dropdown-content">
                <?php if (is_admin()): ?>
                <a href="livros/cadastrar.php">Cadastrar Livro</a>
                <?php endif; ?>
                <a href="livros/listar.php">Ver Catálogo</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">📖 Aluguéis</button>
            <div class="dropdown-content">
                <a href="alugueis/cadastrar.php">Alugar Livro</a>
                <a href="alugueis/listar.php">Meus Aluguéis</a>
            </div>
        </div>

        <a href="logout.php" class="logout">Sair</a>
    </nav>

    <div class="dashboard">
        <div class="welcome-card">
            <h1> Olá, <?= sanitize_input($_SESSION['usuario_nome']) ?>!</h1>
            <p>Bem-vindo ao Sistema de Biblioteca</p>
            <small>Tipo de conta: <?= is_admin() ? '⭐ Administrador' : '📖 Aluno' ?></small>
        </div>

        <div class="stats-grid">
            <?php if (is_admin()): ?>
            <div class="stat-card">
                <div class="icon">📚</div>
                <div class="number"><?= $stats['total_livros'] ?? 0 ?></div>
                <div class="label">Total de Livros</div>
            </div>
            
            <div class="stat-card success">
                <div class="icon">✅</div>
                <div class="number"><?= $stats['livros_disponiveis'] ?? 0 ?></div>
                <div class="label">Livros Disponíveis</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">👥</div>
                <div class="number"><?= $stats['total_usuarios'] ?? 0 ?></div>
                <div class="label">Usuários Cadastrados</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📖</div>
                <div class="number"><?= $stats['alugueis_ativos'] ?? 0 ?></div>
                <div class="label">Aluguéis Ativos</div>
            </div>
            
            <?php if ($stats['alugueis_atrasados'] > 0): ?>
            <div class="stat-card danger">
                <div class="icon">⚠️</div>
                <div class="number"><?= $stats['alugueis_atrasados'] ?></div>
                <div class="label">Aluguéis Atrasados</div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            
            <div class="stat-card">
                <div class="icon">📚</div>
                <div class="number"><?= $stats['livros_disponiveis'] ?? 0 ?></div>
                <div class="label">Livros Disponíveis</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📖</div>
                <div class="number"><?= $stats['meus_alugueis'] ?? 0 ?></div>
                <div class="label">Meus Aluguéis Ativos</div>
            </div>
            
            <?php endif; ?>
        </div>

        <h2 class="section-title">📚 Últimos Livros Cadastrados</h2>
        
        <?php if (!empty($ultimos_livros)): ?>
        <div class="books-grid">
            <?php foreach ($ultimos_livros as $livro): ?>
            <div class="book-card">
                <?php if ($livro['imagem']): ?>
                    <img src="<?= sanitize_input($livro['imagem']) ?>" alt="<?= sanitize_input($livro['titulo']) ?>">
                <?php else: ?>
                    <div class="no-image">📚</div>
                <?php endif; ?>
                
                <div class="info">
                    <div class="title"><?= sanitize_input($livro['titulo']) ?></div>
                    <div class="author"><?= sanitize_input($livro['autor']) ?></div>
                    <span class="status <?= $livro['disponivel'] ? 'disponivel' : 'indisponivel' ?>">
                        <?= $livro['disponivel'] ? '✓ Disponível' : '✗ Indisponível' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="info-box" style="text-align: center;">
            <strong>📚 Nenhum livro cadastrado ainda</strong>
            <?php if (is_admin()): ?>
            <p>Comece cadastrando alguns livros!</p>
            <a href="livros/cadastrar.php" class="btn" style="display: inline-block; margin-top: 10px;">Cadastrar Livro</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>