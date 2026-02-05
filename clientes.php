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

$has_empresa = col_exists($pdo,'clientes','empresa_id');
$has_tipo = col_exists($pdo,'clientes','tipo_cliente');
$has_limite = col_exists($pdo,'clientes','limite_credito');
$has_plazo = col_exists($pdo,'clientes','plazo_dias');
$has_estado_credito = col_exists($pdo,'clientes','estado_credito');
$has_estado = col_exists($pdo,'clientes','estado');

$estado_dt = $has_estado ? col_type($pdo,'clientes','estado') : null;
$estado_numeric = $has_estado ? estado_is_numeric($estado_dt) : true;

$q=trim($_GET['q'] ?? '');
$tipo=$_GET['tipo'] ?? 'TODOS';
$estado=$_GET['estado'] ?? 'TODOS';

$where=[]; $params=[];
if($has_empresa){ $where[]="c.empresa_id=?"; $params[]=$empresa_id; }

if($q!==''){
  $where[]="(c.nombre LIKE ? OR c.identificacion LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)";
  $like="%$q%"; $params=array_merge($params,[$like,$like,$like,$like]);
}
if($has_tipo && $tipo!=='TODOS'){ $where[]="c.tipo_cliente=?"; $params[]=$tipo; }

if($has_estado && $estado!=='TODOS'){
  if($estado==='ACTIVO'){
    $where[]="c.estado ".($estado_numeric?"= ?":"= ?"); $params[]=estado_activo_value($estado_numeric);
  } else {
    $where[]="c.estado ".($estado_numeric?"= ?":"= ?"); $params[]=estado_inactivo_value($estado_numeric);
  }
}

$w = count($where)?("WHERE ".implode(" AND ",$where)):"";

$sel="c.id,c.nombre,c.identificacion,c.email,c.telefono";
if(col_exists($pdo,'clientes','direccion')) $sel.=",c.direccion";
if($has_tipo) $sel.=",c.tipo_cliente";
if($has_limite) $sel.=",c.limite_credito";
if($has_plazo) $sel.=",c.plazo_dias";
if($has_estado_credito) $sel.=",c.estado_credito";
if($has_estado) $sel.=",c.estado";

$st=$pdo->prepare("SELECT $sel FROM clientes c $w ORDER BY c.id DESC LIMIT 5000");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);

// KPI básicos
$k_total = count($rows);
$k_credito = 0; $k_contado=0; $k_bloq=0; $k_act=0;
foreach($rows as $r){
  $t = $has_tipo ? ($r['tipo_cliente'] ?? 'CONTADO') : 'CONTADO';
  if($t==='CREDITO') $k_credito++; else $k_contado++;
  if($has_estado_credito && strtoupper((string)($r['estado_credito'] ?? ''))==='BLOQUEADO') $k_bloq++;
  if(!$has_estado || estado_is_activo($r['estado'] ?? null)) $k_act++;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Clientes | FAC-IL-CR</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{margin:0;color:var(--txt);background:radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),linear-gradient(180deg,#020617,var(--fondo));min-height:100vh}
.header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08);background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));position:sticky;top:0;backdrop-filter: blur(12px);z-index:60}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.btn.danger{background:linear-gradient(180deg,#ef4444,#b91c1c);border-color:rgba(239,68,68,.55)}
.wrap{max-width:1600px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px;align-items:end}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr))}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.input,select,textarea{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.input:focus,select:focus,textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:0 0 6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}
@media(max-width:900px){.kpi{grid-template-columns:repeat(2,minmax(0,1fr))}}
.kpi .box{padding:12px;border-radius:18px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06)}
.kpi .val{font-weight:1000;font-size:18px}
</style></head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CLIENTES</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn primary" href="clientes_nuevo.php">➕ Nuevo</a>
  </div>
</div>

<div class="wrap">
  <div class="kpi">
    <div class="box"><div class="small">Clientes</div><div class="val"><?= (int)$k_total ?></div></div>
    <div class="box"><div class="small">Activos</div><div class="val"><?= (int)$k_act ?></div></div>
    <div class="box"><div class="small">Contado</div><div class="val"><?= (int)$k_contado ?></div></div>
    <div class="box"><div class="small">Crédito</div><div class="val"><?= (int)$k_credito ?></div></div>
  </div>

  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Clientes</div>
        <div class="small">Incluye control de crédito (si tu tabla tiene esos campos).</div>
      </div>
    </div>
    <div class="bd">
      <form class="grid" method="get">
        <div style="grid-column:span 7">
          <div class="label">Buscar</div>
          <input class="input" name="q" value="<?=h($q)?>" placeholder="Nombre, identificación, email, teléfono...">
        </div>
        <div style="grid-column:span 2">
          <div class="label">Tipo</div>
          <select class="input" name="tipo" <?= $has_tipo ? "" : "disabled" ?>>
            <option value="TODOS" <?=$tipo==='TODOS'?'selected':''?>>Todos</option>
            <option value="CONTADO" <?=$tipo==='CONTADO'?'selected':''?>>Contado</option>
            <option value="CREDITO" <?=$tipo==='CREDITO'?'selected':''?>>Crédito</option>
          </select>
        </div>
        <div style="grid-column:span 2">
          <div class="label">Estado</div>
          <select class="input" name="estado" <?= $has_estado ? "" : "disabled" ?>>
            <option value="TODOS" <?=$estado==='TODOS'?'selected':''?>>Todos</option>
            <option value="ACTIVO" <?=$estado==='ACTIVO'?'selected':''?>>Activos</option>
            <option value="INACTIVO" <?=$estado==='INACTIVO'?'selected':''?>>Inactivos</option>
          </select>
        </div>
        <div style="grid-column:span 1;display:flex;justify-content:flex-end">
          <button class="btn primary" type="submit">🔎</button>
        </div>
      </form>

      <?php if(!$has_tipo): ?><div class="small" style="margin-top:8px">Nota: tu tabla no tiene <span class="mono">tipo_cliente</span> (filtro de tipo deshabilitado).</div><?php endif; ?>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th><th>Cliente</th><th>Contacto</th>
              <?php if($has_tipo): ?><th>Tipo</th><?php endif; ?>
              <?php if($has_limite): ?><th class="right">Límite</th><?php endif; ?>
              <?php if($has_plazo): ?><th class="right">Plazo</th><?php endif; ?>
              <?php if($has_estado_credito): ?><th>Estado crédito</th><?php endif; ?>
              <?php if($has_estado): ?><th>Estado</th><?php endif; ?>
              <th class="right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $c): 
              $activo = (!$has_estado) ? true : estado_is_activo($c['estado'] ?? null);
              $tagE = $activo ? 'ok' : 'bad';
              $tipoC = $has_tipo ? ($c['tipo_cliente'] ?? 'CONTADO') : 'CONTADO';
              $tagT = ($tipoC==='CREDITO') ? 'warn' : 'ok';
            ?>
              <tr>
                <td class="mono"><b><?= (int)$c['id'] ?></b></td>
                <td>
                  <b><?= h($c['nombre']) ?></b>
                  <?php if(!empty($c['identificacion'])): ?><div class="small mono"><?= h($c['identificacion']) ?></div><?php endif; ?>
                </td>
                <td>
                  <?php if(!empty($c['email'])): ?><div><?= h($c['email']) ?></div><?php endif; ?>
                  <?php if(!empty($c['telefono'])): ?><div class="small mono"><?= h($c['telefono']) ?></div><?php endif; ?>
                </td>
                <?php if($has_tipo): ?><td><span class="tag <?=$tagT?>"><?= h($tipoC) ?></span></td><?php endif; ?>
                <?php if($has_limite): ?><td class="right mono">₡<?= nf($c['limite_credito'] ?? 0) ?></td><?php endif; ?>
                <?php if($has_plazo): ?><td class="right mono"><?= (int)($c['plazo_dias'] ?? 0) ?> días</td><?php endif; ?>
                <?php if($has_estado_credito): ?>
                  <?php $ec=strtoupper((string)($c['estado_credito'] ?? 'ACTIVO')); $tagEC=($ec==='BLOQUEADO')?'bad':(($ec==='MOROSO')?'warn':'ok'); ?>
                  <td><span class="tag <?=$tagEC?>"><?= h($ec) ?></span></td>
                <?php endif; ?>
                <?php if($has_estado): ?><td><span class="tag <?=$tagE?>"><?= $activo?'ACTIVO':'INACTIVO' ?></span></td><?php endif; ?>
                <td class="right">
                  <div class="actions">
                    <a class="btn" href="clientes_editar.php?id=<?= (int)$c['id'] ?>">✏️ Editar</a>
                    <a class="btn danger" href="clientes_eliminar.php?id=<?= (int)$c['id'] ?>" onclick="return confirm('¿Marcar como INACTIVO este cliente?');">⛔ Desactivar</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; if(count($rows)===0): ?>
              <tr><td colspan="12" class="small">Sin clientes.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body></html>
