<?php
session_start(); require_once "config/db.php"; require_once "cxp_helpers.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$vence_col=cxp_vence_col($pdo);
$num_col=cxp_num_col($pdo);
$has_moneda=col_exists($pdo,'cxp_documentos','moneda');
$has_tc=col_exists($pdo,'cxp_documentos','tipo_cambio');
[$prov_ced,$prov_email,$prov_tel]=prov_cols($pdo);

$err='';
$fecha=$_POST['fecha'] ?? date('Y-m-d');
$vence=$_POST['vence'] ?? date('Y-m-d', strtotime('+30 days'));
$proveedor_id=(int)($_POST['proveedor_id'] ?? 0);
$numero=trim($_POST['numero_documento'] ?? '');
$moneda=$_POST['moneda'] ?? 'CRC';
$tc=(float)($_POST['tipo_cambio'] ?? 1);
$total=(float)($_POST['total'] ?? 0);

$sel_ced = $prov_ced ? ", $prov_ced AS cedula" : ", NULL AS cedula";
$proveedores=$pdo->query("SELECT id,nombre $sel_ced FROM proveedores ORDER BY nombre ASC LIMIT 8000")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  if($proveedor_id<=0) $err="Seleccione un proveedor.";
  if(!$err && $total<=0) $err="Total debe ser mayor a 0.";
  if(!$err && $has_moneda && $moneda!=='CRC' && $has_tc && $tc<=0) $err="Tipo de cambio inválido.";

  if(!$err){
    $cols = ["empresa_id","proveedor_id","fecha",$vence_col,"total","saldo","estado"];
    $vals = [$empresa_id,$proveedor_id,$fecha,$vence,$total,$total,"PENDIENTE"];
    if($num_col && $numero!==''){ $cols[]=$num_col; $vals[]=$numero; }
    if($has_moneda){ $cols[]="moneda"; $vals[]=$moneda; }
    if($has_tc){ $cols[]="tipo_cambio"; $vals[]=$tc; }

    $ph = implode(",", array_fill(0,count($cols),"?" ));
    $sql = "INSERT INTO cxp_documentos (".implode(",",$cols).") VALUES ($ph)";
    $pdo->prepare($sql)->execute($vals);

    header("Location: cxp_detalle.php?id=".$pdo->lastInsertId()); exit;
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuevo CXP</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{margin:0;color:var(--txt);background:radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),linear-gradient(180deg,#020617,var(--fondo));min-height:100vh}
.header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08);background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));position:sticky;top:0;backdrop-filter: blur(12px);z-index:60}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.wrap{max-width:1500px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:rgba(167,176,194,.95)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr))}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.input,select,textarea{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.input:focus,select:focus,textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:rgba(167,176,194,.95);margin:8px 0 6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:rgba(167,176,194,.95);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
</style></head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CXP • Nuevo</span></div>
  <div class="actions"><a class="btn" href="cxp.php">← CXP</a><button class="btn primary" form="f" type="submit">💾 Guardar</button></div>
</div>
<div class="wrap"><div class="card"><div class="hd"><div><div style="font-weight:1000;font-size:18px">Crear documento</div><div class="small">Se ajusta a tu tabla de proveedores</div></div></div>
<div class="bd">
<?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
<form id="f" method="post" class="grid">
  <div style="grid-column:span 6"><div class="label">Proveedor</div>
    <select class="input" name="proveedor_id" required>
      <option value="0">— Seleccione —</option>
      <?php foreach($proveedores as $p): ?>
        <option value="<?=$p['id']?>" <?=$proveedor_id===(int)$p['id']?'selected':''?>><?= h($p['nombre']) ?><?php if(!empty($p['cedula'])): ?> (<?= h($p['cedula']) ?>)<?php endif; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="grid-column:span 2"><div class="label">Fecha</div><input class="input" type="date" name="fecha" value="<?= h($fecha) ?>"></div>
  <div style="grid-column:span 2"><div class="label">Vence</div><input class="input" type="date" name="vence" value="<?= h($vence) ?>"></div>
  <div style="grid-column:span 2"><div class="label">Total</div><input class="input mono" type="number" step="0.01" name="total" value="<?= h($total) ?>"></div>

  <?php if($num_col): ?>
    <div style="grid-column:span 4"><div class="label">Número documento (opcional)</div><input class="input mono" name="numero_documento" value="<?= h($numero) ?>"></div>
  <?php endif; ?>

  <?php if($has_moneda): ?>
    <div style="grid-column:span 2"><div class="label">Moneda</div>
      <select class="input" name="moneda" onchange="toggleTC(this.value)">
        <option value="CRC" <?=$moneda==='CRC'?'selected':''?>>CRC</option>
        <option value="USD" <?=$moneda==='USD'?'selected':''?>>USD</option>
        <option value="EUR" <?=$moneda==='EUR'?'selected':''?>>EUR</option>
      </select>
    </div>
  <?php endif; ?>

  <?php if($has_tc): ?>
    <div style="grid-column:span 2"><div class="label">Tipo cambio</div><input id="tc" class="input mono" type="number" step="0.00001" name="tipo_cambio" value="<?= h($tc) ?>"></div>
  <?php endif; ?>

  <div style="grid-column:span 6"><div class="label">Notas</div><textarea class="input" placeholder="Opcional"></textarea></div>
</form>
</div></div></div>
<script>
function toggleTC(m){ const tc=document.getElementById('tc'); if(!tc) return;
  if(m==='CRC'){ tc.value=1; tc.disabled=true; } else { tc.disabled=false; if(!tc.value||parseFloat(tc.value)<=0) tc.value=1; }
}
toggleTC("<?= h($moneda) ?>");
</script>
</body></html>
