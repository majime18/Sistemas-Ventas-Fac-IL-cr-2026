<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$usuario_id=(int)($_SESSION['usuario_id'] ?? 0);
require_once "cxc_helpers.php";

$vence_col = col_exists($pdo,'cxc_documentos','vencimiento') ? 'vencimiento' : (col_exists($pdo,'cxc_documentos','vence') ? 'vence' : 'vence');

$err='';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$vence = $_POST['vence'] ?? date('Y-m-d', strtotime('+30 days'));
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$total = (float)($_POST['total'] ?? 0);
$venta_id = $_POST['venta_id'] ?? '';
$fe_id = $_POST['fe_id'] ?? '';

$clientes = $pdo->query("SELECT id, nombre, identificacion FROM clientes ORDER BY nombre ASC LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  if($cliente_id<=0) $err="Seleccione un cliente.";
  if(!$err && $total<=0) $err="Total debe ser mayor a 0.";
  if(!$err){
    $pdo->beginTransaction();
    try{
      $st=$pdo->prepare("INSERT INTO cxc_documentos (empresa_id,cliente_id,venta_id,fe_id,fecha,$vence_col,total,saldo,estado)
                         VALUES (?,?,?,?,?, ?,?,?, 'PENDIENTE')");
      $venta_id_val = trim($venta_id)===''?null:(int)$venta_id;
      $fe_id_val = trim($fe_id)===''?null:(int)$fe_id;
      $st->execute([$empresa_id,$cliente_id,$venta_id_val,$fe_id_val,$fecha,$vence,$total,$total]);
      $id=(int)$pdo->lastInsertId();
      audit_log($pdo,$empresa_id,$usuario_id,'CXC','CREAR','cxc_documentos',$id,null,compact('cliente_id','fecha','vence','total','venta_id_val','fe_id_val'));
      $pdo->commit();
      header("Location: cxc_detalle.php?id=".$id); exit;
    }catch(Throwable $e){
      $pdo->rollBack();
      $err="Error al guardar: ".$e->getMessage();
    }
  }
}
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuevo CXC | FAC-IL-CR</title>
<style>
:root{
  --azul:#0b5ed7; --azul-metal:#084298;
  --amarillo:#ffc107; --amarillo-metal:#ffca2c;
  --fondo:#071225; --card:rgba(17,24,39,.78);
  --borde:rgba(255,255,255,.12); --txt:#e5e7eb; --muted:#a7b0c2;
  --ok:#22c55e; --bad:#ef4444; --info:#38bdf8;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{
  margin:0;color:var(--txt);
  background:
    radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),
    radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),
    linear-gradient(180deg,#020617,var(--fondo));
  min-height:100vh;
}
.header{
  display:flex;align-items:center;justify-content:space-between;gap:14px;
  padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08);
  background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));
  position:sticky;top:0;backdrop-filter: blur(12px); z-index:60;
}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));
  color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none;
}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.btn.bad{background:linear-gradient(180deg,rgba(239,68,68,.9),rgba(239,68,68,.55));border-color:rgba(239,68,68,.55);color:#450a0a}
.btn.info{background:linear-gradient(180deg,rgba(56,189,248,.9),rgba(56,189,248,.45));border-color:rgba(56,189,248,.55);color:#082f49}
.wrap{max-width:1500px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr));}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
.input, select, textarea{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
textarea{min-height:90px;resize:vertical}
.input:focus, select:focus, textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
@media(max-width:1100px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:640px){.kpis{grid-template-columns:1fr;}}
.kpi{padding:14px;border-radius:18px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.kpi .t{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.10em}
.kpi .n{font-size:24px;font-weight:1000;margin-top:6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.tag.info{border-color:rgba(56,189,248,.55);background:rgba(56,189,248,.12)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
hr{border:none;border-top:1px solid rgba(255,255,255,.08);margin:12px 0}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CXC • Nuevo</span></div>
  <div class="actions">
    <a class="btn" href="cxc.php">← CXC</a>
    <button class="btn primary" form="f" type="submit">💾 Guardar</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Crear documento por cobrar</div>
        <div class="small">Usalo si necesitás cargar saldos manuales (crédito). Si viene de ventas, se crea automáticamente.</div>
      </div>
    </div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
      <form id="f" method="post" class="grid">
        <div style="grid-column:span 6">
          <div class="label">Cliente</div>
          <select class="input" name="cliente_id" required>
            <option value="0">— Seleccione —</option>
            <?php foreach($clientes as $c): ?>
              <option value="<?=$c['id']?>" <?=((int)$cliente_id===(int)$c['id'])?'selected':''?>>
                <?= h($c['nombre']) ?> (<?= h($c['identificacion'] ?? '') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="grid-column:span 2">
          <div class="label">Fecha</div>
          <input class="input" type="date" name="fecha" value="<?= h($fecha) ?>" required>
        </div>
        <div style="grid-column:span 2">
          <div class="label">Vence</div>
          <input class="input" type="date" name="vence" value="<?= h($vence) ?>">
        </div>
        <div style="grid-column:span 2">
          <div class="label">Total</div>
          <input class="input mono" type="number" step="0.01" min="0" name="total" value="<?= h($total) ?>" required>
        </div>

        <div style="grid-column:span 3">
          <div class="label">Venta ID (opcional)</div>
          <input class="input mono" name="venta_id" value="<?= h($venta_id) ?>" placeholder="Ej: 123">
        </div>
        <div style="grid-column:span 3">
          <div class="label">FE ID (opcional)</div>
          <input class="input mono" name="fe_id" value="<?= h($fe_id) ?>" placeholder="fe_documentos.id">
        </div>
        <div style="grid-column:span 6">
          <div class="label">Notas</div>
          <textarea class="input" placeholder="Opcional (solo visual, no se guarda por ahora)"></textarea>
        </div>
      </form>
    </div>
  </div>
</div>
</body></html>
