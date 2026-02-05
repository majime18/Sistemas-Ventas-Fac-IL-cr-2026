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
function col_type(PDO $pdo,string $t,string $c):?string{
  $st=$pdo->prepare("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$t,$c]);
  $v=$st->fetchColumn();
  return $v?strtolower((string)$v):null;
}
function estado_is_numeric(?string $dt):bool{
  if(!$dt) return true;
  return in_array($dt,['tinyint','smallint','int','bigint','mediumint','decimal','numeric','float','double'],true);
}
function estado_is_activo($v):bool{
  if($v===null) return true;
  if(is_numeric($v)) return ((int)$v)===1;
  $s=strtoupper(trim((string)$v));
  return in_array($s,['ACTIVO','A','SI','S','1','TRUE'],true);
}
function estado_inactivo_value(bool $numeric){
  return $numeric ? 0 : 'INACTIVO';
}
function estado_activo_value(bool $numeric){
  return $numeric ? 1 : 'ACTIVO';
}

if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);

$id=(int)($_GET['id'] ?? 0);
if($id<=0){ header("Location: clientes.php"); exit; }

$has_empresa = col_exists($pdo,'clientes','empresa_id');
$has_estado = col_exists($pdo,'clientes','estado');
if(!$has_estado){ header("Location: clientes.php"); exit; }

$estado_dt = col_type($pdo,'clientes','estado');
$estado_numeric = estado_is_numeric($estado_dt);

$where = $has_empresa ? "id=? AND empresa_id=?" : "id=?";
$params = $has_empresa ? [$id,$empresa_id] : [$id];

$pdo->prepare("UPDATE clientes SET estado=? WHERE $where")->execute([estado_inactivo_value($estado_numeric), ...$params]);
header("Location: clientes.php"); exit;
?>