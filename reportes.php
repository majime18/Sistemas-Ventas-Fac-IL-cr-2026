<?php
session_start();
require_once "config/db.php";
if (empty($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$empresa_id = (int)($_SESSION['empresa_id'] ?? 1);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return '₡'.number_format((float)$n, 2, ',', '.'); }
function pct($n){ return number_format((float)$n, 2, ',', '.').'%'; }

// Filtros
$hoy = new DateTime('now');
$desde = $_GET['desde'] ?? $hoy->modify('-30 days')->format('Y-m-d');
$hasta = $_GET['hasta'] ?? (new DateTime('now'))->format('Y-m-d');

$sucursal_id = (int)($_GET['sucursal_id'] ?? 0);
$bodega_id   = (int)($_GET['bodega_id'] ?? 0);
$vendedor_id = (int)($_GET['vendedor_id'] ?? 0);
$condicion   = $_GET['condicion'] ?? 'TODAS'; // CONTADO/CREDITO/TODAS
$moneda      = $_GET['moneda'] ?? 'TODAS';    // CRC/USD/TODAS

// Listas para filtros
$suc = $pdo->prepare("SELECT id,nombre FROM sucursales WHERE empresa_id=? AND estado=1 ORDER BY nombre");
$suc->execute([$empresa_id]);
$sucursales = $suc->fetchAll(PDO::FETCH_ASSOC);

$bod = $pdo->prepare("SELECT id,nombre,sucursal_id FROM bodegas WHERE empresa_id=? AND estado=1 ORDER BY nombre");
$bod->execute([$empresa_id]);
$bodegas = $bod->fetchAll(PDO::FETCH_ASSOC);

$vend = $pdo->prepare("SELECT id,nombre,email FROM usuarios WHERE empresa_id=? AND estado=1 ORDER BY nombre");
$vend->execute([$empresa_id]);
$vendedores = $vend->fetchAll(PDO::FETCH_ASSOC);

// Armado WHERE dinámico (ventas)
$where = "v.empresa_id=? AND v.estado<>'ANULADA' AND v.created_at >= ? AND v.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
$params = [$empresa_id, $desde, $hasta];

if($sucursal_id>0){ $where.=" AND v.sucursal_id=?"; $params[]=$sucursal_id; }
if($vendedor_id>0){ $where.=" AND v.usuario_id=?"; $params[]=$vendedor_id; }
if($condicion!=='TODAS'){ $where.=" AND v.condicion_venta=?"; $params[]=$condicion; }
if($moneda!=='TODAS'){ $where.=" AND v.moneda=?"; $params[]=$moneda; }

// KPIs Ventas
$kpi = $pdo->prepare("
  SELECT
    COUNT(*) cant_ventas,
    SUM(v.descuento) descuento,
    SUM(v.subtotal) subtotal,
    SUM(v.impuesto_total) iva,
    SUM(v.total) total
  FROM ventas v
  WHERE $where
");
$kpi->execute($params);
$k = $kpi->fetch(PDO::FETCH_ASSOC) ?: ['cant_ventas'=>0,'descuento'=>0,'subtotal'=>0,'iva'=>0,'total'=>0];

// Costo total (por productos.costo) y utilidad real
$util = $pdo->prepare("
  SELECT
    SUM(x.sub_linea) sub_linea,
    SUM(x.costo_linea) costo_linea,
    SUM(x.sub_linea - x.costo_linea) utilidad
  FROM (
    SELECT
      (vd.precio_unitario*vd.cantidad - vd.descuento) AS sub_linea,
      (COALESCE(p.costo,0)*vd.cantidad) AS costo_linea
    FROM ventas v
    JOIN ventas_detalle vd ON vd.venta_id=v.id
    JOIN productos p ON p.id=vd.producto_id
    WHERE $where
  ) x
");
$util->execute($params);
$u = $util->fetch(PDO::FETCH_ASSOC) ?: ['sub_linea'=>0,'costo_linea'=>0,'utilidad'=>0];

$margen = 0;
if((float)$u['sub_linea'] > 0) $margen = ((float)$u['utilidad']/(float)$u['sub_linea'])*100.0;

$ticket_prom = 0;
if((int)$k['cant_ventas'] > 0) $ticket_prom = ((float)$k['total']/(int)$k['cant_ventas']);

// Top productos por ventas (subtotal sin IVA)
$top_prod = $pdo->prepare("
  SELECT
    vd.producto_id,
    p.codigo,
    p.descripcion,
    SUM(vd.cantidad) cantidad,
    SUM(vd.precio_unitario*vd.cantidad - vd.descuento) subtotal,
    SUM((vd.precio_unitario*vd.cantidad - vd.descuento) - (COALESCE(p.costo,0)*vd.cantidad)) utilidad
  FROM ventas v
  JOIN ventas_detalle vd ON vd.venta_id=v.id
  JOIN productos p ON p.id=vd.producto_id
  WHERE $where
  GROUP BY vd.producto_id, p.codigo, p.descripcion
  ORDER BY subtotal DESC
  LIMIT 10
");
$top_prod->execute($params);
$topProductos = $top_prod->fetchAll(PDO::FETCH_ASSOC);

// Top vendedores
$top_vend = $pdo->prepare("
  SELECT u.nombre, u.email, COUNT(*) cant, SUM(v.total) total
  FROM ventas v
  JOIN usuarios u ON u.id=v.usuario_id
  WHERE $where
  GROUP BY u.id, u.nombre, u.email
  ORDER BY total DESC
  LIMIT 10
");
$top_vend->execute($params);
$topVendedores = $top_vend->fetchAll(PDO::FETCH_ASSOC);

// Inventario bajo mínimo (usa filtro de bodega si aplica)
$whereInv = "ie.empresa_id=? AND ie.stock_minimo>0 AND ie.existencia <= ie.stock_minimo";
$paramsInv = [$empresa_id];
if($bodega_id>0){ $whereInv .= " AND ie.bodega_id=?"; $paramsInv[]=$bodega_id; }
if($sucursal_id>0){ $whereInv .= " AND b.sucursal_id=?"; $paramsInv[]=$sucursal_id; }

$inv = $pdo->prepare("
  SELECT b.nombre bodega, p.codigo, p.descripcion, ie.existencia, ie.stock_minimo
  FROM inventario_existencias ie
  JOIN bodegas b ON b.id=ie.bodega_id
  JOIN productos p ON p.id=ie.producto_id
  WHERE $whereInv
  ORDER BY (ie.existencia/NULLIF(ie.stock_minimo,0)) ASC, p.descripcion ASC
  LIMIT 30
");
$inv->execute($paramsInv);
$lowStock = $inv->fetchAll(PDO::FETCH_ASSOC);

// CXC: aging buckets (saldo pendiente)
$cxc = $pdo->prepare("
  SELECT
    SUM(CASE WHEN c.saldo<=0 THEN 0 ELSE c.saldo END) pendiente_total,
    SUM(CASE WHEN c.saldo>0 AND (c.vence IS NULL OR DATEDIFF(CURDATE(), c.vence) <= 0) THEN c.saldo ELSE 0 END) AS no_vencido,
    SUM(CASE WHEN c.saldo>0 AND DATEDIFF(CURDATE(), c.vence) BETWEEN 1 AND 30 THEN c.saldo ELSE 0 END) AS d1_30,
    SUM(CASE WHEN c.saldo>0 AND DATEDIFF(CURDATE(), c.vence) BETWEEN 31 AND 60 THEN c.saldo ELSE 0 END) AS d31_60,
    SUM(CASE WHEN c.saldo>0 AND DATEDIFF(CURDATE(), c.vence) BETWEEN 61 AND 90 THEN c.saldo ELSE 0 END) AS d61_90,
    SUM(CASE WHEN c.saldo>0 AND DATEDIFF(CURDATE(), c.vence) >= 91 THEN c.saldo ELSE 0 END) AS d90p
  FROM cxc_documentos c
  WHERE c.empresa_id=?
");
$cxc->execute([$empresa_id]);
$c = $cxc->fetch(PDO::FETCH_ASSOC) ?: ['pendiente_total'=>0,'no_vencido'=>0,'d1_30'=>0,'d31_60'=>0,'d61_90'=>0,'d90p'=>0];

// Pagos recibidos en rango (abonos)
$ab = $pdo->prepare("
  SELECT SUM(monto_abono) total_abonos
  FROM cxc_abonos
  WHERE empresa_id=? AND anulado=0
    AND fecha_abono >= ? AND fecha_abono < DATE_ADD(?, INTERVAL 1 DAY)
");
$ab->execute([$empresa_id,$desde,$hasta]);
$abonos = $ab->fetchColumn();
if($abonos===false) $abonos = 0;

?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reportes | FAC-IL-CR</title>
<style>
:root{
  --azul:#0b5ed7; --azul-metal:#084298;
  --amarillo:#ffc107; --amarillo-metal:#ffca2c;
  --fondo:#071225; --card:rgba(17,24,39,.78);
  --borde:rgba(255,255,255,.12); --txt:#e5e7eb; --muted:#a7b0c2;
  --ok:#22c55e; --bad:#ef4444;
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
.wrap{max-width:1400px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr));}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
.input, select{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus, select:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
@media(max-width:1200px){.kpis{grid-template-columns:repeat(3,minmax(0,1fr));}}
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
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
</style>
</head><body>

<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Reportes & KPIs</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn primary" href="ventas.php">🧾 Ventas</a>
  </div>
</div>

<div class="wrap">

  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Filtros</div>
        <div class="small">Período por <b>created_at</b> de ventas. Los filtros de bodega afectan inventario/stock crítico.</div>
      </div>
      <div class="small">Empresa #<?= (int)$empresa_id ?></div>
    </div>
    <div class="bd">
      <form method="get" class="grid">
        <div style="grid-column: span 2;">
          <div class="label">Desde</div>
          <input class="input" type="date" name="desde" value="<?=h($desde)?>">
        </div>
        <div style="grid-column: span 2;">
          <div class="label">Hasta</div>
          <input class="input" type="date" name="hasta" value="<?=h($hasta)?>">
        </div>

        <div style="grid-column: span 2;">
          <div class="label">Sucursal</div>
          <select class="input" name="sucursal_id">
            <option value="0">Todas</option>
            <?php foreach($sucursales as $s): ?>
              <option value="<?=$s['id']?>" <?=$sucursal_id==(int)$s['id']?'selected':''?>><?=h($s['nombre'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: span 2;">
          <div class="label">Bodega</div>
          <select class="input" name="bodega_id">
            <option value="0">Todas</option>
            <?php foreach($bodegas as $b): ?>
              <option value="<?=$b['id']?>" <?=$bodega_id==(int)$b['id']?'selected':''?>><?=h($b['nombre'])?></option>
            <?php endforeach; ?>
          </select>
          <div class="small">Afecta stock crítico.</div>
        </div>

        <div style="grid-column: span 2;">
          <div class="label">Vendedor</div>
          <select class="input" name="vendedor_id">
            <option value="0">Todos</option>
            <?php foreach($vendedores as $v): ?>
              <option value="<?=$v['id']?>" <?=$vendedor_id==(int)$v['id']?'selected':''?>><?=h($v['nombre'])?> (<?=h($v['email'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: span 1;">
          <div class="label">Condición</div>
          <select class="input" name="condicion">
            <option value="TODAS" <?=$condicion==='TODAS'?'selected':''?>>Todas</option>
            <option value="CONTADO" <?=$condicion==='CONTADO'?'selected':''?>>Contado</option>
            <option value="CREDITO" <?=$condicion==='CREDITO'?'selected':''?>>Crédito</option>
          </select>
        </div>

        <div style="grid-column: span 1;">
          <div class="label">Moneda</div>
          <select class="input" name="moneda">
            <option value="TODAS" <?=$moneda==='TODAS'?'selected':''?>>Todas</option>
            <option value="CRC" <?=$moneda==='CRC'?'selected':''?>>CRC</option>
            <option value="USD" <?=$moneda==='USD'?'selected':''?>>USD</option>
          </select>
        </div>

        <div style="grid-column: span 12; display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; margin-top:8px">
          <button class="btn primary" type="submit">🔎 Aplicar</button>
          <a class="btn" href="reportes.php">Limpiar</a>
          <a class="btn warn" href="reportes.php?desde=<?=date('Y-m-01')?>&hasta=<?=date('Y-m-d')?>">Mes actual</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <div class="hd">
      <div style="font-weight:1000;font-size:18px">KPIs</div>
      <div class="small">Ventas + utilidad + CXC</div>
    </div>
    <div class="bd">
      <div class="kpis">
        <div class="kpi"><div class="t">Ventas (cantidad)</div><div class="n"><?= (int)$k['cant_ventas'] ?></div></div>
        <div class="kpi"><div class="t">Total vendido</div><div class="n"><?= money($k['total'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">Subtotal</div><div class="n"><?= money($k['subtotal'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">IVA</div><div class="n"><?= money($k['iva'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">Utilidad real</div><div class="n"><?= money($u['utilidad'] ?? 0) ?></div><div class="small">Margen: <?= pct($margen) ?></div></div>
        <div class="kpi"><div class="t">Ticket promedio</div><div class="n"><?= money($ticket_prom) ?></div></div>

        <div class="kpi"><div class="t">Descuentos</div><div class="n"><?= money($k['descuento'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">CXC pendiente</div><div class="n"><?= money($c['pendiente_total'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">Abonos en rango</div><div class="n"><?= money($abonos ?? 0) ?></div></div>
        <div class="kpi"><div class="t">CXC 1-30</div><div class="n"><?= money($c['d1_30'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">CXC 31-60</div><div class="n"><?= money($c['d31_60'] ?? 0) ?></div></div>
        <div class="kpi"><div class="t">CXC 90+</div><div class="n"><?= money($c['d90p'] ?? 0) ?></div></div>
      </div>
    </div>
  </div>

  <div class="grid" style="margin-top:12px">
    <div class="card" style="grid-column: span 7;">
      <div class="hd">
        <div style="font-weight:1000;font-size:18px">Top productos</div>
        <div class="small">Por subtotal (sin IVA) y utilidad real</div>
      </div>
      <div class="bd" style="overflow:auto">
        <table class="table">
          <thead><tr>
            <th>Producto</th><th class="right">Cant.</th><th class="right">Subtotal</th><th class="right">Utilidad</th>
          </tr></thead>
          <tbody>
          <?php foreach($topProductos as $r): ?>
            <tr>
              <td><b><?=h($r['codigo'])?></b> — <?=h($r['descripcion'])?></td>
              <td class="right"><?=number_format((float)$r['cantidad'],3,',','.')?></td>
              <td class="right"><?=money($r['subtotal'])?></td>
              <td class="right"><?=money($r['utilidad'])?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(count($topProductos)===0): ?><tr><td colspan="4" class="small">Sin datos en el rango.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="grid-column: span 5;">
      <div class="hd">
        <div style="font-weight:1000;font-size:18px">Top vendedores</div>
        <div class="small">Por total vendido</div>
      </div>
      <div class="bd" style="overflow:auto">
        <table class="table">
          <thead><tr>
            <th>Vendedor</th><th class="right">Ventas</th><th class="right">Total</th>
          </tr></thead>
          <tbody>
          <?php foreach($topVendedores as $r): ?>
            <tr>
              <td><b><?=h($r['nombre'])?></b><div class="small"><?=h($r['email'])?></div></td>
              <td class="right"><?= (int)$r['cant'] ?></td>
              <td class="right"><?= money($r['total']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(count($topVendedores)===0): ?><tr><td colspan="3" class="small">Sin datos en el rango.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <div class="hd">
      <div style="font-weight:1000;font-size:18px">Inventario crítico</div>
      <div class="small">Bajo mínimo (por bodega)</div>
    </div>
    <div class="bd" style="overflow:auto">
      <?php if(count($lowStock)>0): ?>
        <div class="notice" style="margin-bottom:10px">
          <span class="tag warn">⚠ Hay productos bajo mínimo</span>
          <span class="small">Usá <b>Inventario → Movimiento</b> para reponer o ajustar.</span>
        </div>
      <?php else: ?>
        <div class="notice"><span class="tag ok">● OK</span> <span class="small">No hay productos bajo mínimo con los filtros actuales.</span></div>
      <?php endif; ?>

      <table class="table">
        <thead><tr>
          <th>Bodega</th><th>Producto</th><th class="right">Existencia</th><th class="right">Mínimo</th><th>Estado</th>
        </tr></thead>
        <tbody>
        <?php foreach($lowStock as $r): ?>
          <tr>
            <td><b><?=h($r['bodega'])?></b></td>
            <td><b><?=h($r['codigo'])?></b> — <?=h($r['descripcion'])?></td>
            <td class="right"><?=number_format((float)$r['existencia'],3,',','.')?></td>
            <td class="right"><?=number_format((float)$r['stock_minimo'],3,',','.')?></td>
            <td><span class="tag warn">⚠ Bajo mínimo</span></td>
          </tr>
        <?php endforeach; ?>
        <?php if(count($lowStock)===0): ?><tr><td colspan="5" class="small">Sin registros.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <div class="hd">
      <div style="font-weight:1000;font-size:18px">CXC vencido por rango</div>
      <div class="small">Saldos pendientes (aging)</div>
    </div>
    <div class="bd">
      <div class="kpis" style="grid-template-columns:repeat(5,minmax(0,1fr))">
        <div class="kpi"><div class="t">No vencido</div><div class="n"><?=money($c['no_vencido']??0)?></div></div>
        <div class="kpi"><div class="t">1-30</div><div class="n"><?=money($c['d1_30']??0)?></div></div>
        <div class="kpi"><div class="t">31-60</div><div class="n"><?=money($c['d31_60']??0)?></div></div>
        <div class="kpi"><div class="t">61-90</div><div class="n"><?=money($c['d61_90']??0)?></div></div>
        <div class="kpi"><div class="t">90+</div><div class="n"><?=money($c['d90p']??0)?></div></div>
      </div>
      <div class="small" style="margin-top:10px">
        Nota: este bloque no se filtra por fecha porque representa el saldo vigente en CXC (cartera actual).
      </div>
    </div>
  </div>

  <div class="notice" style="margin-top:12px">
    <b>Exportación rápida:</b> si querés que este reporte exporte CSV (Top productos / ventas / CXC), decime y te lo agrego sin librerías.
  </div>

</div>
</body></html>
