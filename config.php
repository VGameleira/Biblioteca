<?php

// ─── Carregar variáveis de ambiente ─────────────────────────────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
} else {
    $env = [];
}

// ─── Configurações do Sistema ────────────────────────────────────────────────
define('DB_HOST',    $env['DB_HOST']    ?? 'localhost');
define('DB_NAME',    $env['DB_NAME']    ?? 'biblioteca');
define('DB_USER',    $env['DB_USER']    ?? 'root');
define('DB_PASS',    $env['DB_PASS']    ?? '');
define('DB_CHARSET', $env['DB_CHARSET'] ?? 'utf8mb4');

define('ENVIRONMENT', $env['ENVIRONMENT'] ?? 'production');
define('IS_DEV', ENVIRONMENT === 'development');

// Configurações de Upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// ─── Configurações de Sessão ─────────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');

// ─── Error Reporting ─────────────────────────────────────────────────────────
if (IS_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// ─── Conexão com o Banco de Dados ───────────────────────────────────────────
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
} catch (PDOException $e) {
    error_log("Erro de conexão: " . $e->getMessage());

    if (IS_DEV) {
        die(sprintf(
            '<div style="font-family:Arial;padding:20px;background:#f8d7da;border:2px solid #dc3545;border-radius:10px;margin:20px;">
                <h2 style="color:#842029;"> Erro de Conexão com Banco de Dados</h2>
                <p><strong>Mensagem:</strong> %s</p>
                <hr><h3>Verifique:</h3>
                <ul>
                    <li>O MySQL está rodando?</li>
                    <li>O banco de dados foi criado?</li>
                    <li>Host: <strong>%s</strong></li>
                    <li>Usuário: <strong>%s</strong></li>
                    <li>Senha: <strong>%s</strong></li>
                </ul>
            </div>',
            $e->getMessage(),
            DB_HOST,
            DB_USER,
            DB_PASS ? '***' : '(vazia)'
        ));
    }

    die("Erro ao conectar ao banco de dados. Tente novamente mais tarde.");
}

// Funções Auxiliares
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function is_logged_in() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nome']);
}

function is_admin() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/index.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        redirect('/painel.php');
    }
}

function format_date($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function show_message($message, $type = 'success') {
    $class = $type === 'success' ? 'sucesso' : 'erro';
    return "<p class='$class'>$message</p>";
}

// Função para upload de imagem
function upload_image($file, $old_image = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $old_image;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro no upload do arquivo.");
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception("Arquivo muito grande. Máximo: 5MB");
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        throw new Exception("Tipo de arquivo não permitido.");
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        throw new Exception("Extensão de arquivo não permitida.");
    }
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    $new_filename = uniqid('book_', true) . '.' . $extension;
    $destination = UPLOAD_DIR . $new_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Erro ao salvar arquivo.");
    }
    
    // Deletar imagem antiga se existir
    if ($old_image && file_exists(UPLOAD_DIR . basename($old_image))) {
        unlink(UPLOAD_DIR . basename($old_image));
    }
    
    return 'uploads/' . $new_filename;
}

// Função para deletar imagem
function delete_image($image_path) {
    if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
        unlink(__DIR__ . '/' . $image_path);
    }
}
?>