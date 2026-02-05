<?php
declare(strict_types=1);
session_start();
require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }

$empresa_id = (int)($_SESSION['empresa_id'] ?? 1);
$id = (int)($_GET['id'] ?? 0);
if($id<=0) die("ID inválido");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n, $d=2){ return number_format((float)$n,$d,'.',','); }
function crc($n){ return "₡".nf($n,2); }

function col_exists(PDO $pdo, string $table, string $col): bool{
  $st=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$table,$col]);
  return ((int)$st->fetchColumn())>0;
}

$has_logo = col_exists($pdo,'empresas','logo_blob') && col_exists($pdo,'empresas','logo_mime');

if($has_logo){
  $emp=$pdo->prepare("SELECT *, (logo_blob IS NOT NULL AND OCTET_LENGTH(logo_blob)>0) AS tiene_logo FROM empresas WHERE id=? LIMIT 1");
} else {
  $emp=$pdo->prepare("SELECT * FROM empresas WHERE id=? LIMIT 1");
}
$emp->execute([$empresa_id]);
$emp=$emp->fetch(PDO::FETCH_ASSOC);

$st=$pdo->prepare("
  SELECT v.*, 
         c.nombre cliente, c.identificacion, c.email cliente_email, c.telefono cliente_tel, c.direccion cliente_dir
  FROM ventas v
  LEFT JOIN clientes c ON c.id=v.cliente_id
  WHERE v.id=? AND v.empresa_id=?
  LIMIT 1
");
$st->execute([$id,$empresa_id]);
$v=$st->fetch(PDO::FETCH_ASSOC);
if(!$v) die("Venta no encontrada");

$det=$pdo->prepare("SELECT * FROM ventas_detalle WHERE venta_id=? ORDER BY id");
$det->execute([$id]);
$det=$det->fetchAll(PDO::FETCH_ASSOC);

// FE info (opcional)
$fe=null;
if(col_exists($pdo,'ventas','fe_documento_id') && !empty($v['fe_documento_id'])){
  if(col_exists($pdo,'fe_documentos','id')){
    $feSt=$pdo->prepare("SELECT * FROM fe_documentos WHERE id=? LIMIT 1");
    $feSt->execute([(int)$v['fe_documento_id']]);
    $fe=$feSt->fetch(PDO::FETCH_ASSOC) ?: null;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Imprimir Venta #<?= (int)$v['id'] ?> | FAC-IL-CR</title>
<style>
:root{
  --azul:#0b5ed7; --azul-metal:#084298;
  --amarillo:#ffc107; --amarillo-metal:#ffca2c;
  --fondo:#071225; --card:rgba(17,24,39,.78);
  --borde:rgba(255,255,255,.12); --txt:#e5e7eb; --muted:#a7b0c2;
  --ok:#22c55e; --bad:#ef4444; --paper:#ffffff; --paperTxt:#111827; --paperMuted:#374151;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{
  margin:0;
  color:var(--txt);
  background:
    radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),
    radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),
    linear-gradient(180deg,#020617,var(--fondo));
  min-height:100vh;
}
.header{
  display:flex; align-items:center; justify-content:space-between; gap:14px;
  padding:12px 18px;
  border-bottom:1px solid rgba(255,255,255,.08);
  background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));
  position:sticky; top:0; backdrop-filter: blur(12px); z-index:60;
}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));
  color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none
}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.wrap{max-width:1050px;margin:auto;padding:14px}
.card{
  background:var(--card); border:1px solid var(--borde); border-radius:18px;
  box-shadow:0 18px 50px rgba(0,0,0,.45); overflow:hidden;
}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:12px}
.small{font-size:12px;color:var(--muted)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:rgba(167,176,194,.95);text-transform:uppercase;letter-spacing:.08em;background:rgba(2,6,23,.55)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.paper{
  background:var(--paper); color:var(--paperTxt);
  border-radius:16px; overflow:hidden;
  border:1px solid rgba(0,0,0,.08);
}
.paper .p-hd{
  padding:16px 16px 10px;
  border-bottom:1px dashed rgba(0,0,0,.20);
}
.paper .p-bd{ padding:12px 16px 18px; }
.logo{
  width:64px;height:64px;border-radius:14px;object-fit:cover;
  border:1px solid rgba(0,0,0,.10);
  background:#f3f4f6;
}
.p-muted{color:var(--paperMuted);font-size:12px}
.p-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media(max-width:720px){ .p-grid{grid-template-columns:1fr} }
.p-table{width:100%;border-collapse:collapse;margin-top:10px}
.p-table th,.p-table td{padding:8px;border-bottom:1px solid rgba(0,0,0,.10)}
.p-table th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--paperMuted);text-align:left}
.p-right{text-align:right}
.totalbox{display:flex;justify-content:flex-end;margin-top:10px}
.totalbox table{width:320px;border-collapse:collapse}
.totalbox td{padding:6px;border-bottom:1px solid rgba(0,0,0,.10)}
.totalbox tr:last-child td{border-bottom:0}
@media print{
  body{background:#fff;color:#111}
  .header,.screen-only{display:none !important}
  .wrap{max-width:100%;padding:0}
  .card{box-shadow:none;border:0;background:#fff}
  .paper{border:0}
}
</style>
</head>
<body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">IMPRESIÓN</span></div>
  <div class="screen-only" style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn" href="ventas_ver.php?id=<?= (int)$v['id'] ?>">← Volver</a>
    <button class="btn primary" onclick="window.print()">🖨 Imprimir</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Comprobante de Venta #<?= (int)$v['id'] ?></div>
        <div class="small">Impresión / PDF • <?= h($v['created_at'] ?? '') ?></div>
      </div>
      <?php if(!empty($v['condicion_venta'])): ?>
        <span class="tag <?= ($v['condicion_venta']==='CREDITO'?'warn':'ok') ?>"><?= h($v['condicion_venta']) ?></span>
      <?php endif; ?>
    </div>

    <div class="bd">
      <div class="paper">
        <div class="p-hd">
          <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
            <div style="display:flex;gap:12px;align-items:center">
              <?php if($has_logo && !empty($emp['tiene_logo'])): ?>
                <img class="logo" src="empresa_logo.php?id=<?= (int)$empresa_id ?>" alt="Logo">
              <?php else: ?>
                <div class="logo" style="display:flex;align-items:center;justify-content:center;font-weight:900;color:#6b7280">LOGO</div>
              <?php endif; ?>
              <div>
                <div style="font-weight:1000;font-size:18px"><?= h($emp['nombre_comercial'] ?? $emp['nombre'] ?? 'Empresa') ?></div>
                <div class="p-muted">
                  Cédula: <span class="mono"><?= h($emp['cedula_juridica'] ?? '') ?></span>
                  <?php if(!empty($emp['actividad_economica'])): ?> • Actividad: <?= h($emp['actividad_economica']) ?><?php endif; ?>
                </div>
                <div class="p-muted">
                  <?= h($emp['direccion'] ?? '') ?>
                </div>
                <div class="p-muted">
                  <?= h($emp['email'] ?? '') ?><?php if(!empty($emp['telefono'])): ?> • Tel: <?= h($emp['telefono']) ?><?php endif; ?>
                </div>
              </div>
            </div>
            <div style="text-align:right">
              <div class="p-muted">Fecha</div>
              <div class="mono" style="font-weight:900"><?= h(substr((string)($v['created_at'] ?? ''),0,19)) ?></div>
              <?php if(!empty($v['moneda'])): ?>
                <div class="p-muted" style="margin-top:6px">Moneda: <b><?= h($v['moneda']) ?></b><?php if(!empty($v['tipo_cambio']) && (float)$v['tipo_cambio']!=1.0): ?> • TC: <span class="mono"><?= nf($v['tipo_cambio'],5) ?></span><?php endif; ?></div>
              <?php endif; ?>
              <?php if(!empty($v['medio_pago'])): ?>
                <div class="p-muted">Pago: <b><?= h($v['medio_pago']) ?></b></div>
              <?php endif; ?>
            </div>
          </div>

          <?php if($fe): ?>
            <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
              <?php
                $estado = $fe['estado'] ?? ($fe['hacienda_estado'] ?? 'PENDIENTE');
                $cls = ($estado==='ACEPTADA'||$estado==='ACEPTADO') ? 'ok' : (($estado==='RECHAZADA'||$estado==='RECHAZADO')?'bad':'warn');
              ?>
              <span class="tag <?= $cls ?>">FE: <?= h($estado) ?></span>
              <?php if(!empty($fe['clave'])): ?><span class="tag"><span class="p-muted">Clave</span> <span class="mono"><?= h($fe['clave']) ?></span></span><?php endif; ?>
              <?php if(!empty($fe['consecutivo'])): ?><span class="tag"><span class="p-muted">Consecutivo</span> <span class="mono"><?= h($fe['consecutivo']) ?></span></span><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="p-bd">
          <div class="p-grid">
            <div>
              <div style="font-weight:900;margin-bottom:6px">Cliente</div>
              <div class="p-muted"><b><?= h($v['cliente'] ?? 'N/D') ?></b></div>
              <div class="p-muted">ID: <span class="mono"><?= h($v['identificacion'] ?? '') ?></span></div>
              <div class="p-muted"><?= h($v['cliente_email'] ?? '') ?><?php if(!empty($v['cliente_tel'])): ?> • Tel: <span class="mono"><?= h($v['cliente_tel']) ?></span><?php endif; ?></div>
              <div class="p-muted"><?= h($v['cliente_dir'] ?? '') ?></div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:900;margin-bottom:6px">Resumen</div>
              <div class="p-muted">Subtotal: <span class="mono"><?= crc($v['subtotal'] ?? 0) ?></span></div>
              <div class="p-muted">Impuestos: <span class="mono"><?= crc($v['impuesto_total'] ?? 0) ?></span></div>
              <div style="font-size:18px;font-weight:1000;margin-top:6px">Total: <span class="mono"><?= crc($v['total'] ?? 0) ?></span></div>
            </div>
          </div>

          <table class="p-table">
            <thead>
              <tr>
                <th>Descripción</th>
                <th class="p-right">Cant.</th>
                <th class="p-right">Precio</th>
                <th class="p-right">IVA</th>
                <th class="p-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($det as $d): ?>
                <tr>
                  <td><?= h($d['descripcion'] ?? '') ?></td>
                  <td class="p-right mono"><?= nf($d['cantidad'] ?? 0,3) ?></td>
                  <td class="p-right mono"><?= crc($d['precio_unitario'] ?? 0) ?></td>
                  <td class="p-right mono"><?= crc($d['impuesto_monto'] ?? 0) ?></td>
                  <td class="p-right mono"><b><?= crc($d['total_linea'] ?? 0) ?></b></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="totalbox">
            <table>
              <tr><td class="p-right">Subtotal</td><td class="p-right mono"><?= crc($v['subtotal'] ?? 0) ?></td></tr>
              <tr><td class="p-right">Impuestos</td><td class="p-right mono"><?= crc($v['impuesto_total'] ?? 0) ?></td></tr>
              <tr><td class="p-right"><b>Total</b></td><td class="p-right mono"><b><?= crc($v['total'] ?? 0) ?></b></td></tr>
            </table>
          </div>

          <?php if(!empty($v['observaciones'])): ?>
            <div style="margin-top:10px" class="p-muted"><b>Observaciones:</b> <?= h($v['observaciones']) ?></div>
          <?php endif; ?>

          <div style="margin-top:14px" class="p-muted">
            Gracias por su compra • Generado por <b>FAC-IL-CR</b>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
