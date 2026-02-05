<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$usuario_id=(int)($_SESSION['usuario_id'] ?? 0);
require_once "contabilidad_helpers.php";

$id=(int)($_GET['id'] ?? 0);
if(!$id){ header("Location: contabilidad_cuentas.php"); exit; }

$st=$pdo->prepare("SELECT * FROM cont_cuentas WHERE id=? AND empresa_id=? LIMIT 1");
$st->execute([$id,$empresa_id]);
$r=$st->fetch(PDO::FETCH_ASSOC);

if($r){
  $pdo->prepare("UPDATE cont_cuentas SET estado=0 WHERE id=? AND empresa_id=?")->execute([$id,$empresa_id]);
  audit_log($pdo,$empresa_id,$usuario_id,'Contabilidad','INACTIVAR','cont_cuentas',$id,$r,['estado'=>0]);
}
header("Location: contabilidad_cuentas.php"); exit;
