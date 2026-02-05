<?php
session_start(); require_once "config/db.php";
header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['usuario_id'])){ http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'No auth']); exit; }

$empresa_id = (int)$_SESSION['empresa_id'];
$cat = trim($_GET['categoria'] ?? '');
$q = trim($_GET['q'] ?? '');
$bodega_id = (int)($_GET['bodega_id'] ?? 0);

$where = "p.empresa_id=? AND p.estado=1";
$params = [$empresa_id];

if($q !== ''){
  $where .= " AND (p.codigo LIKE ? OR p.descripcion LIKE ?)";
  $like = "%$q%";
  $params[] = $like; $params[] = $like;
}
if($cat !== ''){
  // si la columna no existe, el try/catch de la query completa lo manejará
  $where .= " AND p.categoria = ?";
  $params[] = $cat;
}

$sql = "
  SELECT p.id,p.codigo,p.descripcion,p.precio,p.cabys,
         COALESCE(i.porcentaje,13.00) impuesto_pct,
         COALESCE(ie.existencia,0) existencia
  FROM productos p
  LEFT JOIN impuestos i ON i.id = p.impuesto_id
  LEFT JOIN inventario_existencias ie
    ON ie.empresa_id = p.empresa_id
   AND ie.producto_id = p.id
   AND (?=0 OR ie.bodega_id = ?)
  WHERE $where
  ORDER BY p.descripcion ASC
  LIMIT 60
";

try{
  $st = $pdo->prepare($sql);
  $st->execute(array_merge([$bodega_id,$bodega_id], $params));
  echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  // Si no existe columna categoria, reintenta sin filtro
  if(stripos($e->getMessage(),'Unknown column')!==false){
    $sql2 = str_replace(" AND p.categoria = ?","",$sql);
    $params2 = $params;
    if($cat!=='') array_pop($params2);
    $st = $pdo->prepare($sql2);
    $st->execute(array_merge([$bodega_id,$bodega_id], $params2));
    echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
  }else{
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
  }
}
