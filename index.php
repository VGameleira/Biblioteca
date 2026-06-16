<?php
require_once 'config.php';

// Redirecionar se já estiver logado
if (is_logged_in()) {
    redirect('painel.php');
}

// Verificar se há admin no sistema
$tem_admin = false;
try {
    $sql = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'admin'";
    $resultado = $pdo->query($sql)->fetch();
    $tem_admin = $resultado['total'] > 0;
} catch (PDOException $e) {
    // Se erro, assume que tem admin para segurança
    $tem_admin = true;
}

// Redirecionar para criar admin se não existir nenhum admin
if (!$tem_admin) {
    redirect('criar_admin.php');
}

$mensagem = "";
$tipo_mensagem = "";
$acao_ativa = 'login';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] == 'cadastro') {
            $acao_ativa = 'cadastro';
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $tipo = $_POST['tipo'] ?? 'aluno';
            
            if (empty($nome) || empty($email) || empty($senha)) {
                $mensagem = "Preencha todos os campos!";
                $tipo_mensagem = "erro";
            } elseif (strlen($senha) < 6) {
                $mensagem = "Senha deve ter no mínimo 6 caracteres!";
                $tipo_mensagem = "erro";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mensagem = "Email inválido!";
                $tipo_mensagem = "erro";
            } else {
                try {
                    $sql = "SELECT id FROM usuarios WHERE email = :email";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':email' => $email]);
                    
                    if ($stmt->fetch()) {
                        $mensagem = "Email já cadastrado!";
                        $tipo_mensagem = "erro";
                    } else {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (:nome, :email, :senha, :tipo)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':nome' => $nome,
                            ':email' => $email,
                            ':senha' => $senha_hash,
                            ':tipo' => $tipo
                        ]);
                        
                        $mensagem = "✓ Cadastro realizado com sucesso! Faça login.";
                        $tipo_mensagem = "sucesso";
                        $acao_ativa = 'login';
                    }
                } catch (PDOException $e) {
                    error_log("Erro no cadastro: " . $e->getMessage());
                    $mensagem = "Erro ao registrar usuário!";
                    $tipo_mensagem = "erro";
                }
            }
        } elseif ($_POST['acao'] == 'login') {
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';

            if (empty($email) || empty($senha)) {
                $mensagem = "Preencha email e senha!";
                $tipo_mensagem = "erro";
            } else {
                try {
                    $sql = "SELECT id, nome, tipo, senha FROM usuarios WHERE email = :email";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':email' => $email]);
                    $usuario = $stmt->fetch();

                    if ($usuario) {
                        $senha_hash = $usuario['senha'];
                        $valido = false;

                        // senha já está em hash compatível?
                        if (password_verify($senha, $senha_hash)) {
                            $valido = true;
                        }
                        // caso o campo contenha texto plano (usuário criado manualmente sem hash)
                        elseif ($senha === $senha_hash) {
                            $valido = true;

                            // atualizar registro com hash seguro
                            $nova_hash = password_hash($senha, PASSWORD_DEFAULT);
                            $sql2 = "UPDATE usuarios SET senha = :senha WHERE id = :id";
                            $stmt2 = $pdo->prepare($sql2);
                            $stmt2->execute([
                                ':senha' => $nova_hash,
                                ':id'    => $usuario['id']
                            ]);
                        }

                        if ($valido) {
                            $_SESSION['usuario_id']   = $usuario['id'];
                            $_SESSION['usuario_nome'] = $usuario['nome'];
                            $_SESSION['tipo']         = $usuario['tipo'];
                            redirect('painel.php');
                        } else {
                            $mensagem = "Email ou senha incorretos!";
                            $tipo_mensagem = "erro";
                        }
                    } else {
                        $mensagem = "Email ou senha incorretos!";
                        $tipo_mensagem = "erro";
                    }
                } catch (PDOException $e) {
                    error_log("Erro no login: " . $e->getMessage());
                    $mensagem = "Erro ao fazer login!";
                    $tipo_mensagem = "erro";
                }
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
    <title>Sistema de Biblioteca</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>📚 Sistema de Biblioteca</h1>
            <p style="margin: 10px 0 0; opacity: 0.9;">Bem-vindo! Faça login ou cadastre-se</p>
        </div>
        
        <div class="auth-tabs">
            <button class="tab-btn <?= $acao_ativa == 'login' ? 'active' : '' ?>" onclick="switchTab('login')">
                🔐 Entrar
            </button>
            <button class="tab-btn <?= $acao_ativa == 'cadastro' ? 'active' : '' ?>" onclick="switchTab('cadastro')">
                📝 Cadastrar
            </button>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div style="padding: 20px 30px 0;">
                <div class="<?= $tipo_mensagem ?>"><?= sanitize_input($mensagem) ?></div>
            </div>
        <?php endif; ?>
        
        <!-- TAB LOGIN -->
        <div id="tab-login" class="tab-content <?= $acao_ativa == 'login' ? 'active' : '' ?>">
            <form method="POST">
                <input type="hidden" name="acao" value="login">
                
                <div class="form-group">
                    <label for="email_login">📧 Email</label>
                    <input type="email" id="email_login" name="email" placeholder="seu@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="senha_login">🔒 Senha</label>
                    <input type="password" id="senha_login" name="senha" placeholder="Digite sua senha" required>
                </div>
                
                <button type="submit" class="btn">Entrar</button>
            </form>
        </div>
        
        <!-- TAB CADASTRO -->
        <div id="tab-cadastro" class="tab-content <?= $acao_ativa == 'cadastro' ? 'active' : '' ?>">
            <form method="POST">
                <input type="hidden" name="acao" value="cadastro">
                
                <div class="form-group">
                    <label for="nome">👤 Nome Completo</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu nome" required>
                </div>
                
                <div class="form-group">
                    <label for="email">📧 Email</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">🔒 Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required>
                    <small>A senha deve ter no mínimo 6 caracteres</small>
                </div>
                
                <?php if (!$tem_admin): ?>
                <div class="form-group">
                    <label for="tipo">👑 Tipo de Usuário</label>
                    <select id="tipo" name="tipo">
                        <option value="aluno">👨‍🎓 Aluno</option>
                        <option value="admin">👑 Administrador</option>
                    </select>
                    <small>Como é o primeiro usuário, você pode criar um administrador</small>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn">Cadastrar</button>
            </form>
            
            <!-- Botão para criar administrador -->
            <div style="margin-top: 20px; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                <a href="criar_admin.php" class="btn" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); width: 100%; margin: 0;">
                    👑 Criar Administrador
                </a>
                <small style="display: block; margin-top: 10px; color: #6c757d;">
                    Acesse para criar uma conta de administrador
                </small>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`button[onclick="switchTab('${tab}')"]`).classList.add('active');
            document.getElementById(`tab-${tab}`).classList.add('active');
        }
    </script>
</body>
</html>