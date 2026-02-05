<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return '₡'.number_format((float)$n, 2, ',', '.'); }

function audit_log(PDO $pdo, int $empresa_id, ?int $usuario_id, string $modulo, string $accion, string $tabla, $registro_id, $antes=null, $despues=null){
  try{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $st = $pdo->prepare("INSERT INTO auditoria (empresa_id,usuario_id,modulo,accion,tabla_nombre,registro_id,antes_json,despues_json,ip,user_agent,created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
    $st->execute([
      $empresa_id,
      $usuario_id,
      $modulo,
      $accion,
      $tabla,
      $registro_id,
      $antes===null?null:json_encode($antes, JSON_UNESCAPED_UNICODE),
      $despues===null?null:json_encode($despues, JSON_UNESCAPED_UNICODE),
      $ip,
      $ua
    ]);
  } catch(Throwable $e){}
}

function periodo_get_or_create(PDO $pdo, int $empresa_id, string $fechaYmd){
  $dt = new DateTime($fechaYmd);
  $anio = (int)$dt->format('Y');
  $mes  = (int)$dt->format('n');
  $st = $pdo->prepare("SELECT id,estado FROM cont_periodos WHERE empresa_id=? AND anio=? AND mes=? LIMIT 1");
  $st->execute([$empresa_id,$anio,$mes]);
  $p = $st->fetch(PDO::FETCH_ASSOC);
  if($p) return $p;
  $ins = $pdo->prepare("INSERT INTO cont_periodos (empresa_id,anio,mes,estado) VALUES (?,?,?,'ABIERTO')");
  $ins->execute([$empresa_id,$anio,$mes]);
  return ['id'=>(int)$pdo->lastInsertId(),'estado'=>'ABIERTO'];
}
?>