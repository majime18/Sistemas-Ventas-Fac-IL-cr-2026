<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$id=(int)($_GET['id'] ?? 0);
if($id<=0){ header("Location: empresas.php"); exit; }
$pdo->prepare("DELETE FROM empresas WHERE id=?")->execute([$id]);
header("Location: empresas.php"); exit;
?>