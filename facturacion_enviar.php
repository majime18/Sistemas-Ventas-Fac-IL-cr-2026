<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$empresa=(int)$_SESSION['empresa_id'];
$id=(int)($_GET['id']??0);

// Cargar doc
$st=$pdo->prepare("SELECT * FROM fe_documentos WHERE id=? AND empresa_id=?");
$st->execute([$id,$empresa]); $r=$st->fetch();
if(!$r) die("Documento no encontrado");

// Simulación: si tiene clave, "aceptamos" 85% y "rechazamos" 15%
$roll = random_int(1,100);
$estado = ($roll<=85) ? 'ACEPTADA' : 'RECHAZADA';
$mensaje = ($estado==='ACEPTADA') ? 'Aceptada por Hacienda (simulado).' : 'Rechazada por Hacienda (simulado).';

$respuesta = json_encode([
  "simulado" => true,
  "fecha" => date('c'),
  "estado" => $estado,
  "mensaje" => $mensaje
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

$upd=$pdo->prepare("UPDATE fe_documentos SET estado=?, respuesta_hacienda=?, mensaje_hacienda=? WHERE id=? AND empresa_id=?");
$upd->execute([$estado,$respuesta,$mensaje,$id,$empresa]);

header("Location: facturacion_ver.php?id=".$id);
exit;
