<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$usuario_id=(int)($_SESSION['usuario_id'] ?? 0);
require_once "contabilidad_helpers.php";

$id=(int)($_GET['id'] ?? 0);
if(!$id){ header("Location: contabilidad.php"); exit; }

$st=$pdo->prepare("SELECT * FROM cont_asientos WHERE id=? AND empresa_id=? LIMIT 1");
$st->execute([$id,$empresa_id]);
$a=$st->fetch(PDO::FETCH_ASSOC);
if(!$a){ header("Location: contabilidad.php"); exit; }

if(!empty($a['periodo_id'])){
  $ps=$pdo->prepare("SELECT estado FROM cont_periodos WHERE id=? AND empresa_id=? LIMIT 1");
  $ps->execute([(int)$a['periodo_id'],$empresa_id]);
  $p=$ps->fetch(PDO::FETCH_ASSOC);
  if($p && $p['estado']==='CERRADO'){
    header("Location: contabilidad_ver.php?id=".$id); exit;
  }
}

if((int)$a['anulado']===0){
  $pdo->prepare("UPDATE cont_asientos SET anulado=1 WHERE id=? AND empresa_id=?")->execute([$id,$empresa_id]);
  audit_log($pdo,$empresa_id,$usuario_id,'Contabilidad','ANULAR','cont_asientos',$id,$a,['anulado'=>1]);
}
header("Location: contabilidad_ver.php?id=".$id); exit;
