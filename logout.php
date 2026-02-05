<?php
// logout.php
declare(strict_types=1);
session_start();

// Intentar auditoría (si existe)
try {
    require_once __DIR__ . "/config/db.php";
    if (!empty($_SESSION['empresa_id'])) {
        $empresa_id = (int)$_SESSION['empresa_id'];
        $usuario_id = !empty($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $st = $pdo->prepare("INSERT INTO auditoria (empresa_id, usuario_id, modulo, accion, descripcion, ip, user_agent)
                             VALUES (?, ?, 'AUTH', 'LOGOUT', 'Cierre de sesión', ?, ?)");
        $st->execute([$empresa_id, $usuario_id, $ip, $ua]);
    }
} catch (Throwable $e) {
    // no romper
}

// Cerrar sesión
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

header("Location: login.php");
exit;
