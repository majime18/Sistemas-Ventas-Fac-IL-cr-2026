<?php
session_start(); require_once "config/db.php";
header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['usuario_id'])){ http_response_code(401); echo json_encode(['ok'=>false]); exit; }

$moneda = strtoupper(trim($_GET['moneda'] ?? 'CRC'));
if($moneda==='CRC'){ echo json_encode(['ok'=>true,'venta'=>1.0,'fuente'=>'LOCAL']); exit; }

$hoy = date('Y-m-d');
try{
  $st = $pdo->prepare("SELECT venta FROM tipos_cambio WHERE fecha=? AND moneda=? LIMIT 1");
  $st->execute([$hoy,$moneda]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if($row){ echo json_encode(['ok'=>true,'venta'=>(float)$row['venta'],'fuente'=>'CACHE']); exit; }
}catch(Throwable $e){}

$venta = 1.0; $fuente='MANUAL';
$try = @file_get_contents("https://api.hacienda.go.cr/indicadores/tc");
if($try){
  $d = json_decode($try,true);
  if(is_array($d) && isset($d['venta'])){ $venta=(float)$d['venta']; $fuente='API'; }
}
try{
  $pdo->prepare("INSERT IGNORE INTO tipos_cambio (fecha,moneda,venta,fuente) VALUES (?,?,?,?)")
      ->execute([$hoy,$moneda,$venta,$fuente]);
}catch(Throwable $e){}

echo json_encode(['ok'=>true,'venta'=>$venta,'fuente'=>$fuente]);
