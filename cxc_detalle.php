<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
require_once "cxc_helpers.php";

$vence_col = col_exists($pdo,'cxc_documentos','vencimiento') ? 'vencimiento' : (col_exists($pdo,'cxc_documentos','vence') ? 'vence' : 'vence');

$id=(int)($_GET['id'] ?? 0);
if(!$id){ header("Location: cxc.php"); exit; }

$st=$pdo->prepare("
  SELECT d.*, c.nombre cliente, c.identificacion, c.telefono, c.email
  FROM cxc_documentos d
  JOIN clientes c ON c.id=d.cliente_id
  WHERE d.id=? AND d.empresa_id=?
  LIMIT 1
");
$st->execute([$id,$empresa_id]);
$doc=$st->fetch(PDO::FETCH_ASSOC);
if(!$doc){ header("Location: cxc.php"); exit; }

// Detalle de venta (si existe)
$items=[];
if(!empty($doc['venta_id'])){
  $vd_has_empresa = col_exists($pdo,'ventas_detalle','empresa_id');
  $sqlItems = "
    SELECT vd.producto_id,
           p.codigo,
           vd.descripcion,
           vd.cantidad,
           vd.precio_unitario,
           (vd.cantidad*vd.precio_unitario - vd.descuento) AS subtotal,
           vd.impuesto_monto,
           vd.total_linea
    FROM ventas_detalle vd
    JOIN productos p ON p.id=vd.producto_id
    WHERE vd.venta_id=?
    ".($vd_has_empresa ? " AND vd.empresa_id=? " : "")."
    ORDER BY vd.id ASC
  ";
  $it=$pdo->prepare($sqlItems);
  $params=[(int)$doc['venta_id']];
  if($vd_has_empresa) $params[] = $empresa_id;
  $it->execute($params);
  $items=$it->fetchAll(PDO::FETCH_ASSOC);
}

// Abonos (cxc_abonos)
$ab=$pdo->prepare("
  SELECT a.id, a.fecha_abono, a.monto_abono, a.metodo_pago, a.referencia_pago, a.usuario_id, a.observaciones, a.anulado
  FROM cxc_abonos a
  WHERE a.cxc_documento_id=? AND a.empresa_id=?
  ORDER BY a.id DESC
");
$ab->execute([$id,$empresa_id]);
$abonos=$ab->fetchAll(PDO::FETCH_ASSOC);

$sum_ab=0;
foreach($abonos as $a){
  if((int)$a['anulado']===0) $sum_ab += (float)$a['monto_abono'];
}

$vence = $doc[$vence_col] ?? $doc['vence'] ?? null;
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CXC #<?= (int)$id ?> | FAC-IL-CR</title>
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CXC • Documento #<?= (int)$id ?></span></div>
  <div class="actions">
    <a class="btn" href="cxc.php">← CXC</a>
    <?php if((float)$doc['saldo']>0.00001): ?>
      <a class="btn warn" href="cxc_pago.php?cxc_id=<?=$id?>">💳 Registrar abono</a>
    <?php endif; ?>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Documento CXC</div>
        <div class="small">Cliente + vencimiento + movimientos de abono.</div>
      </div>
      <div class="actions">
        <?php
          $estado = $doc['estado'] ?? 'PENDIENTE';
          $tag = $estado==='PAGADO'?'ok':($estado==='VENCIDO'?'bad':'warn');
        ?>
        <span class="tag <?= $tag ?>"><?= h($estado) ?></span>
        <?php if(!empty($vence) && $estado!=='PAGADO' && strtotime($vence) < strtotime(date('Y-m-d'))): ?>
          <span class="tag bad">Vencido</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="bd">
      <div class="grid">
        <div style="grid-column:span 5">
          <div class="label">Cliente</div>
          <div><b><?= h($doc['cliente']) ?></b></div>
          <div class="small mono"><?= h($doc['identificacion'] ?? '') ?></div>
        </div>
        <div style="grid-column:span 3">
          <div class="label">Fecha</div>
          <div class="mono"><b><?= h(substr((string)$doc['fecha'],0,10)) ?></b></div>
        </div>
        <div style="grid-column:span 2">
          <div class="label">Vence</div>
          <div class="mono"><b><?= $vence ? h($vence) : '—' ?></b></div>
        </div>
        <div style="grid-column:span 2">
          <div class="label">Venta</div>
          <div class="mono"><?= !empty($doc['venta_id']) ? '#'.h($doc['venta_id']) : '—' ?></div>
        </div>

        <div style="grid-column:span 4">
          <div class="label">Total</div>
          <div class="mono"><b><?= number_format((float)$doc['total'],2,',','.') ?></b></div>
        </div>
        <div style="grid-column:span 4">
          <div class="label">Saldo</div>
          <div class="mono"><b><?= number_format((float)$doc['saldo'],2,',','.') ?></b></div>
        </div>
        <div style="grid-column:span 4">
          <div class="label">Abonado (no anulado)</div>
          <div class="mono"><b><?= number_format((float)$sum_ab,2,',','.') ?></b></div>
        </div>
      </div>

      <?php if(count($items)>0): ?>
        <hr>
        <div class="notice"><b>Detalle de la venta</b> <span class="small">(desde ventas_detalle)</span></div>
        <div style="margin-top:10px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
          <table class="table">
            <thead><tr><th>Código</th><th>Descripción</th><th class="right">Cant</th><th class="right">Precio</th><th class="right">Subtotal</th></tr></thead>
            <tbody>
              <?php foreach($items as $it): ?>
                <tr>
                  <td class="mono"><b><?= h($it['codigo']) ?></b></td>
                  <td><?= h($it['descripcion']) ?></td>
                  <td class="right mono"><?= number_format((float)$it['cantidad'],2,',','.') ?></td>
                  <td class="right mono"><?= number_format((float)$it['precio_unitario'],2,',','.') ?></td>
                  <td class="right mono"><?= number_format((float)$it['subtotal'],2,',','.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <hr>
      <div class="notice"><b>Historial de abonos</b> <span class="small">(cxc_abonos)</span></div>
      <div style="margin-top:10px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead><tr>
            <th>ID</th><th>Fecha</th><th>Método</th><th>Referencia</th><th>Obs</th><th class="right">Monto</th><th>Estado</th>
          </tr></thead>
          <tbody>
            <?php foreach($abonos as $a): ?>
              <tr>
                <td class="mono"><?= (int)$a['id'] ?></td>
                <td class="mono"><?= h($a['fecha_abono']) ?></td>
                <td><?= h($a['metodo_pago']) ?></td>
                <td class="mono"><?= h($a['referencia_pago'] ?? '') ?></td>
                <td><?= h($a['observaciones'] ?? '') ?></td>
                <td class="right mono"><b><?= number_format((float)$a['monto_abono'],2,',','.') ?></b></td>
                <td><?= ((int)$a['anulado']===1) ? '<span class="tag bad">ANULADO</span>' : '<span class="tag ok">OK</span>' ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if(count($abonos)===0): ?><tr><td colspan="7" class="small">Aún no hay abonos.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body></html>
