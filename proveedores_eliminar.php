<?php
session_start();
require_once "config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$id=(int)($_GET['id'] ?? 0);
if($id<=0){ header("Location: proveedores.php"); exit; }

$pdo->prepare("UPDATE proveedores SET estado=0 WHERE id=? AND empresa_id=?")->execute([$id,$empresa_id]);
header("Location: proveedores.php");
exit;
?>
