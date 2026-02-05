<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
function col_exists(PDO $pdo,string $t,string $c):bool{
  $st=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$t,$c]); return ((int)$st->fetchColumn())>0;
}

if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);

$accion=$_POST['accion'] ?? '';
$ids=$_POST['ids'] ?? [];
$back=$_POST['back'] ?? 'comisiones.php?tab=calculo';

if(!is_array($ids) || count($ids)==0){ header("Location: $back&msg=".urlencode("No seleccionaste comisiones.")); exit; }

$ids=array_values(array_filter(array_map('intval',$ids), fn($x)=>$x>0));
if(count($ids)==0){ header("Location: $back&msg=".urlencode("IDs inválidos.")); exit; }

$ph=implode(",", array_fill(0,count($ids),"?"));
$params=array_merge([$empresa_id],$ids);

if($accion==='pagar'){
  $sql="UPDATE comisiones_calculadas SET estado='PAGADA' WHERE empresa_id=? AND estado='PENDIENTE' AND id IN ($ph)";
  $pdo->prepare($sql)->execute($params);
  header("Location: $back&msg=".urlencode("Comisiones pagadas.")); exit;
} elseif($accion==='anular'){
  $sql="UPDATE comisiones_calculadas SET estado='ANULADA' WHERE empresa_id=? AND estado='PENDIENTE' AND id IN ($ph)";
  $pdo->prepare($sql)->execute($params);
  header("Location: $back&msg=".urlencode("Comisiones anuladas.")); exit;
} else {
  header("Location: $back&msg=".urlencode("Acción inválida.")); exit;
}
