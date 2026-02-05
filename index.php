<?php
// index.php
declare(strict_types=1);
session_start();

if (!empty($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}
header("Location: login.php");
exit;
