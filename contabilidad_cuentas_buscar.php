<?php
session_start(); require_once "config/db.php";
header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['usuario_id'])){ http_response_code(401); echo json_encode(['ok'=>false]); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$q=trim($_GET['q'] ?? '');
if($q===''){ echo json_encode(['ok'=>true,'items'=>[]]); exit; }
$like="%$q%";
$st=$pdo->prepare("
  SELECT id,codigo,nombre,tipo,permite_mov
  FROM cont_cuentas
  WHERE empresa_id=? AND estado=1 AND permite_mov=1
    AND (codigo LIKE ? OR nombre LIKE ?)
  ORDER BY codigo ASC
  LIMIT 50
");
$st->execute([$empresa_id,$like,$like]);
echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
