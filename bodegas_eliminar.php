<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)$_SESSION['empresa_id'];
$id=(int)($_GET['id']??0);
if(!$id){ header("Location: bodegas.php"); exit; }
$st=$pdo->prepare("UPDATE bodegas SET estado=0 WHERE id=? AND empresa_id=?");
$st->execute([$id,$empresa_id]);
header("Location: bodegas.php"); exit;
