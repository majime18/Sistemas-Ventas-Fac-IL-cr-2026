<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return '₡'.number_format((float)$n, 2, ',', '.'); }

function col_exists(PDO $pdo, string $table, string $col): bool {
  $st = $pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$table,$col]);
  return ((int)$st->fetchColumn())>0;
}

function audit_log(PDO $pdo, int $empresa_id, ?int $usuario_id, string $modulo, string $accion, string $tabla, $registro_id, $antes=null, $despues=null){
  try{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $st = $pdo->prepare("INSERT INTO auditoria (empresa_id,usuario_id,modulo,accion,tabla_nombre,registro_id,antes_json,despues_json,ip,user_agent,created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
    $st->execute([
      $empresa_id,$usuario_id,$modulo,$accion,$tabla,$registro_id,
      $antes===null?null:json_encode($antes, JSON_UNESCAPED_UNICODE),
      $despues===null?null:json_encode($despues, JSON_UNESCAPED_UNICODE),
      $ip,$ua
    ]);
  } catch(Throwable $e){}
}

/**
 * Recalcula saldo/estado de un documento CXC.
 * Estado:
 * - PAGADO si saldo<=0
 * - VENCIDO si saldo>0 y vence < hoy
 * - PENDIENTE en caso contrario
 */
function cxc_recalc(PDO $pdo, int $empresa_id, int $cxc_id, string $vence_col='vence'){
  $st = $pdo->prepare("SELECT total, saldo, $vence_col AS vence FROM cxc_documentos WHERE id=? AND empresa_id=? LIMIT 1");
  $st->execute([$cxc_id,$empresa_id]);
  $d = $st->fetch(PDO::FETCH_ASSOC);
  if(!$d) return;

  $saldo = (float)$d['saldo'];
  $vence = $d['vence'];

  $estado = 'PENDIENTE';
  if($saldo <= 0.00001) $estado = 'PAGADO';
  else if(!empty($vence) && strtotime($vence) < strtotime(date('Y-m-d'))) $estado='VENCIDO';

  $up = $pdo->prepare("UPDATE cxc_documentos SET estado=? WHERE id=? AND empresa_id=?");
  $up->execute([$estado,$cxc_id,$empresa_id]);
}
?>