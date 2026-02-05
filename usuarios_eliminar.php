<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$mi_id=(int)($_SESSION['usuario_id'] ?? 0);
$id=(int)($_GET['id'] ?? 0);
if($id<=0 || $id===$mi_id){ header("Location: usuarios.php"); exit; }

// Desactivar (no borrar)
$has_empresa_stmt=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='empresa_id'");
$has_empresa_stmt->execute();
$has_empresa=((int)$has_empresa_stmt->fetchColumn())>0;

if($has_empresa){
  $pdo->prepare("UPDATE usuarios SET estado=0 WHERE id=? AND empresa_id=?")->execute([$id,$empresa_id]);
} else {
  $pdo->prepare("UPDATE usuarios SET estado=0 WHERE id=?")->execute([$id]);
}
header("Location: usuarios.php"); exit;
?>