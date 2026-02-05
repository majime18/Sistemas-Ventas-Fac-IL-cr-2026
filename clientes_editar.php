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

$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id<=0){ header("Location: clientes.php"); exit; }
$where = $has_empresa ? "id=? AND empresa_id=?" : "id=?";
$params = $has_empresa ? [$id,$empresa_id] : [$id];

$st=$pdo->prepare("SELECT * FROM clientes WHERE $where");
$st->execute($params);
$c=$st->fetch(PDO::FETCH_ASSOC);
if(!$c) die("Cliente no encontrado");

$err='';
$nombre=trim($_POST['nombre'] ?? ($c['nombre'] ?? ''));
$ident=trim($_POST['identificacion'] ?? ($c['identificacion'] ?? ''));
$email=trim($_POST['email'] ?? ($c['email'] ?? ''));
$tel=trim($_POST['telefono'] ?? ($c['telefono'] ?? ''));
$dir=trim($_POST['direccion'] ?? ($c['direccion'] ?? ''));
$tipo=$_POST['tipo_cliente'] ?? ($has_tipo?($c['tipo_cliente'] ?? 'CONTADO'):'CONTADO');
$lim=(float)($_POST['limite_credito'] ?? ($has_limite?($c['limite_credito'] ?? 0):0));
$plazo=(int)($_POST['plazo_dias'] ?? ($has_plazo?($c['plazo_dias'] ?? 0):0));
$estado_credito=$_POST['estado_credito'] ?? ($has_estado_credito?($c['estado_credito'] ?? 'ACTIVO'):'ACTIVO');
$estado_in=$_POST['estado'] ?? ($has_estado? (string)($c['estado'] ?? estado_activo_value($estado_numeric)) : (string)estado_activo_value($estado_numeric));

if($_SERVER['REQUEST_METHOD']==='POST'){
  if($nombre==='') $err="El nombre es obligatorio.";
  if(!$err){
    $cols=["nombre"=>$nombre,"identificacion"=>$ident===''?null:$ident,"email"=>$email===''?null:$email,"telefono"=>$tel===''?null:$tel,"direccion"=>$dir===''?null:$dir];
    if($has_tipo) $cols["tipo_cliente"]=$tipo;
    if($has_limite) $cols["limite_credito"]=$lim;
    if($has_plazo) $cols["plazo_dias"]=$plazo;
    if($has_estado_credito) $cols["estado_credito"]=$estado_credito;
    if($has_estado) $cols["estado"]=($estado_numeric?(int)$estado_in:(string)$estado_in);

    $set=[]; $vals=[];
    foreach($cols as $k=>$v){ $set[]="$k=?"; $vals[]=$v; }
    $sql="UPDATE clientes SET ".implode(",",$set)." WHERE $where";
    $vals = array_merge($vals,$params);
    $pdo->prepare($sql)->execute($vals);
    header("Location: clientes.php"); exit;
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Editar Cliente</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CLIENTES • EDITAR</span></div>
  <div class="actions"><a class="btn" href="clientes.php">← Volver</a><button class="btn primary" form="f" type="submit">💾 Guardar</button></div>
</div>

<div class="wrap"><div class="card">
  <div class="hd"><div><div style="font-weight:1000;font-size:18px">Editar cliente</div><div class="small">ID: <span class="mono"><?= (int)$id ?></span></div></div></div>
  <div class="bd">
    <?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
    <form id="f" method="post" class="grid">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <div style="grid-column:span 6"><div class="label">Nombre *</div><input class="input" name="nombre" value="<?=h($nombre)?>" required></div>
      <div style="grid-column:span 3"><div class="label">Identificación</div><input class="input mono" name="identificacion" value="<?=h($ident)?>"></div>
      <div style="grid-column:span 3"><div class="label">Teléfono</div><input class="input mono" name="telefono" value="<?=h($tel)?>"></div>

      <div style="grid-column:span 6"><div class="label">Email</div><input class="input" type="email" name="email" value="<?=h($email)?>"></div>
      <div style="grid-column:span 6"><div class="label">Dirección</div><textarea class="input" name="direccion" rows="3"><?=h($dir)?></textarea></div>

      <?php if($has_tipo): ?>
        <div style="grid-column:span 3"><div class="label">Tipo cliente</div>
          <select class="input" name="tipo_cliente">
            <option value="CONTADO" <?=$tipo==='CONTADO'?'selected':''?>>Contado</option>
            <option value="CREDITO" <?=$tipo==='CREDITO'?'selected':''?>>Crédito</option>
          </select>
        </div>
      <?php endif; ?>

      <?php if($has_limite): ?>
        <div style="grid-column:span 3"><div class="label">Límite crédito</div>
          <input class="input mono" type="number" step="0.01" min="0" name="limite_credito" value="<?=h((string)$lim)?>">
        </div>
      <?php endif; ?>

      <?php if($has_plazo): ?>
        <div style="grid-column:span 3"><div class="label">Plazo (días)</div>
          <input class="input mono" type="number" step="1" min="0" name="plazo_dias" value="<?=h((string)$plazo)?>">
        </div>
      <?php endif; ?>

      <?php if($has_estado_credito): ?>
        <div style="grid-column:span 3"><div class="label">Estado crédito</div>
          <select class="input" name="estado_credito">
            <?php foreach(['ACTIVO','MOROSO','BLOQUEADO'] as $ec): ?>
              <option value="<?=$ec?>" <?=$estado_credito===$ec?'selected':''?>><?=$ec?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <?php if($has_estado): ?>
        <div style="grid-column:span 3"><div class="label">Estado</div>
          <select class="input" name="estado">
            <option value="<?=h((string)estado_activo_value($estado_numeric))?>" <?=estado_is_activo($estado_in)?'selected':''?>>Activo</option>
            <option value="<?=h((string)estado_inactivo_value($estado_numeric))?>" <?=!estado_is_activo($estado_in)?'selected':''?>>Inactivo</option>
          </select>
          <div class="small">Tipo: <span class="mono"><?= h((string)$estado_dt) ?></span></div>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div></div>
</body></html>
