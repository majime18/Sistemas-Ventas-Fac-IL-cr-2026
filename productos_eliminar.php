<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id = (int)$_SESSION['empresa_id'];
$id = (int)($_GET['id'] ?? 0);
if(!$id){ header("Location: productos.php"); exit; }

// En vez de borrar, se desactiva (recomendado para auditoría / integridad).
$st = $pdo->prepare("UPDATE productos SET estado=0 WHERE id=? AND empresa_id=?");
$st->execute([$id,$empresa_id]);

header("Location: productos.php");
exit;
