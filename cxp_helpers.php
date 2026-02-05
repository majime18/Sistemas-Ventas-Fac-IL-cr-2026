<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
function col_exists(PDO $pdo, string $table, string $col): bool {
  $st=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$table,$col]); return ((int)$st->fetchColumn())>0;
}
function cxp_vence_col(PDO $pdo): string {
  if(col_exists($pdo,'cxp_documentos','vencimiento')) return 'vencimiento';
  if(col_exists($pdo,'cxp_documentos','vence')) return 'vence';
  return 'vence';
}
function cxp_num_col(PDO $pdo): ?string {
  if(col_exists($pdo,'cxp_documentos','numero_documento')) return 'numero_documento';
  if(col_exists($pdo,'cxp_documentos','numero')) return 'numero';
  if(col_exists($pdo,'cxp_documentos','documento')) return 'documento';
  return null;
}
function prov_cols(PDO $pdo): array {
  // Returns [cedula_col|null, email_col|null, telefono_col|null]
  $ced=null; $email=null; $tel=null;
  if(col_exists($pdo,'proveedores','cedula')) $ced='cedula';
  elseif(col_exists($pdo,'proveedores','identificacion')) $ced='identificacion';
  elseif(col_exists($pdo,'proveedores','numero_identificacion')) $ced='numero_identificacion';
  if(col_exists($pdo,'proveedores','email')) $email='email';
  if(col_exists($pdo,'proveedores','telefono')) $tel='telefono';
  return [$ced,$email,$tel];
}
function cxp_recalc(PDO $pdo, int $empresa_id, int $cxp_id, string $vence_col){
  $st=$pdo->prepare("SELECT saldo, $vence_col AS vence FROM cxp_documentos WHERE id=? AND empresa_id=?");
  $st->execute([$cxp_id,$empresa_id]); $d=$st->fetch(PDO::FETCH_ASSOC); if(!$d) return;
  $saldo=(float)$d['saldo']; $vence=$d['vence'] ?? null;
  $estado='PENDIENTE';
  if($saldo<=0.00001) $estado='PAGADO';
  else if(!empty($vence) && strtotime($vence) < strtotime(date('Y-m-d'))) $estado='VENCIDO';
  $pdo->prepare("UPDATE cxp_documentos SET estado=? WHERE id=? AND empresa_id=?")->execute([$estado,$cxp_id,$empresa_id]);
}
?>