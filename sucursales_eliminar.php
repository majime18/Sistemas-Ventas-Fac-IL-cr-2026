<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$id=(int)($_GET['id']??0);
$pdo->prepare("UPDATE sucursales SET estado=0 WHERE id=?")->execute([$id]);
header("Location: sucursales.php"); exit;
