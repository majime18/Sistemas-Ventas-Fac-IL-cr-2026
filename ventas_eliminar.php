<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$id=(int)($_GET['id']??0);
$pdo->prepare("UPDATE ventas SET estado='ANULADA', anulado=1 WHERE id=? AND empresa_id=?")
    ->execute([$id,$_SESSION['empresa_id']]);
header("Location: ventas.php"); exit;
