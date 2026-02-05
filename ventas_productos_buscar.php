<?php
session_start(); require_once "config/db.php";
header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['usuario_id'])){ http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'No auth']); exit; }

$empresa_id = (int)$_SESSION['empresa_id'];
$q = trim($_GET['q'] ?? '');
$bodega_id = (int)($_GET['bodega_id'] ?? 0);
if($q===''){ echo json_encode(['ok'=>true,'items'=>[]]); exit; }

$st = $pdo->prepare("
  SELECT p.id, p.codigo, p.descripcion, p.precio, p.cabys,
         COALESCE(i.porcentaje, 13.00) AS impuesto_pct,
         COALESCE(ie.existencia, 0) AS existencia
  FROM productos p
  LEFT JOIN impuestos i ON i.id = p.impuesto_id
  LEFT JOIN inventario_existencias ie
    ON ie.empresa_id = p.empresa_id
   AND ie.producto_id = p.id
   AND (?=0 OR ie.bodega_id = ?)
  WHERE p.empresa_id=? AND p.estado=1
    AND (p.codigo LIKE ? OR p.descripcion LIKE ?)
  ORDER BY (p.codigo = ?) DESC, p.descripcion ASC
  LIMIT 50
");
$like = '%'.$q.'%';
$st->execute([$bodega_id,$bodega_id,$empresa_id,$like,$like,$q]);
echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
