<?php
session_start(); require_once "config/db.php";
header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['usuario_id'])){ http_response_code(401); echo json_encode(['ok'=>false]); exit; }
$empresa_id=(int)$_SESSION['empresa_id'];
$q=trim($_GET['q'] ?? '');
if($q===''){ echo json_encode(['ok'=>true,'items'=>[]]); exit; }
$like="%$q%";
$st=$pdo->prepare("
  SELECT id,codigo,COALESCE(codigo_barras,'') codigo_barras,descripcion,cabys
  FROM productos
  WHERE empresa_id=? AND estado=1
    AND (codigo LIKE ? OR descripcion LIKE ? OR COALESCE(codigo_barras,'') LIKE ?)
  ORDER BY (codigo=?) DESC, descripcion ASC
  LIMIT 50
");
$st->execute([$empresa_id,$like,$like,$like,$q]);
echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
