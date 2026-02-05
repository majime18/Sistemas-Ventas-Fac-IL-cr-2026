<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
require_once "cxc_helpers.php";

$vence_col = col_exists($pdo,'cxc_documentos','vencimiento') ? 'vencimiento' : (col_exists($pdo,'cxc_documentos','vence') ? 'vence' : 'vence');

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$q = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? 'TODOS';
$solo_vencidos = (int)($_GET['solo_vencidos'] ?? 0);

$where="d.empresa_id=? AND DATE(d.fecha) >= ? AND DATE(d.fecha) <= ?";
$params=[$empresa_id,$desde,$hasta];

if($q!==''){
  $where.=" AND (CAST(d.id AS CHAR) LIKE ? OR CAST(d.venta_id AS CHAR) LIKE ? OR c.nombre LIKE ? OR c.identificacion LIKE ?)";
  $like="%$q%";
  $params=array_merge($params,[$like,$like,$like,$like]);
}
if($estado!=='TODOS'){ $where.=" AND d.estado=?"; $params[]=$estado; }
if($solo_vencidos===1){ $where.=" AND d.saldo>0 AND d.$vence_col IS NOT NULL AND d.$vence_col < CURDATE()"; }

$st = $pdo->prepare("
  SELECT d.id, d.cliente_id, c.nombre cliente, c.identificacion,
         d.venta_id, d.fe_id, d.fecha, d.$vence_col AS vence,
         d.total, d.saldo, d.estado,
         DATEDIFF(CURDATE(), d.$vence_col) AS dias_venc
  FROM cxc_documentos d
  JOIN clientes c ON c.id=d.cliente_id
  WHERE $where
  ORDER BY d.estado='VENCIDO' DESC, d.$vence_col ASC, d.id DESC
  LIMIT 1200
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$k = $pdo->prepare("SELECT
    SUM(CASE WHEN saldo>0 THEN 1 ELSE 0 END) pendientes,
    SUM(CASE WHEN saldo>0 THEN saldo ELSE 0 END) saldo_total,
    SUM(CASE WHEN saldo>0 AND $vence_col IS NOT NULL AND $vence_col < CURDATE() THEN saldo ELSE 0 END) vencido_total
  FROM cxc_documentos
  WHERE empresa_id=?
");
$k->execute([$empresa_id]);
$kpi = $k->fetch(PDO::FETCH_ASSOC) ?: ['pendientes'=>0,'saldo_total'=>0,'vencido_total'=>0];
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CXC | FAC-IL-CR</title>
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Cuentas por Cobrar</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn primary" href="cxc_nuevo.php">➕ Nuevo documento</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Panel CXC</div>
        <div class="small">Control de saldo, vencimientos y abonos (tipo POS).</div>
      </div>
      <div class="small">Empresa #<?= (int)$empresa_id ?></div>
    </div>
    <div class="bd">
      <div class="kpis">
        <div class="kpi">
          <div class="t">Documentos pendientes</div>
          <div class="n"><?= (int)($kpi['pendientes'] ?? 0) ?></div>
          <div class="small">Saldo total: <?= money($kpi['saldo_total'] ?? 0) ?></div>
        </div>
        <div class="kpi">
          <div class="t">Vencido</div>
          <div class="n"><?= money($kpi['vencido_total'] ?? 0) ?></div>
          <div class="small">Solo saldo vencido</div>
        </div>
        <div class="kpi">
          <div class="t">Rango de consulta</div>
          <div class="n"><?= h($desde) ?> → <?= h($hasta) ?></div>
          <div class="small">Filtra por fecha de creación del documento</div>
        </div>
        <div class="kpi">
          <div class="t">Atajos</div>
          <div class="actions" style="justify-content:flex-start;margin-top:8px">
            <a class="btn warn" href="cxc.php?solo_vencidos=1">Ver vencidos</a>
            <a class="btn" href="cxc.php">Reset</a>
          </div>
        </div>
      </div>

      <form method="get" class="grid" style="margin-top:12px">
        <div style="grid-column:span 2"><div class="label">Desde</div><input class="input" type="date" name="desde" value="<?=h($desde)?>"></div>
        <div style="grid-column:span 2"><div class="label">Hasta</div><input class="input" type="date" name="hasta" value="<?=h($hasta)?>"></div>
        <div style="grid-column:span 4"><div class="label">Buscar</div><input class="input" name="q" value="<?=h($q)?>" placeholder="Cliente, cédula, ID doc, venta..."></div>
        <div style="grid-column:span 2"><div class="label">Estado</div>
          <select class="input" name="estado">
            <option value="TODOS" <?=$estado==='TODOS'?'selected':''?>>Todos</option>
            <?php foreach(['PENDIENTE','VENCIDO','PAGADO'] as $e): ?>
              <option value="<?=$e?>" <?=$estado===$e?'selected':''?>><?=$e?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="grid-column:span 1">
          <div class="label">Vencidos</div>
          <select class="input" name="solo_vencidos">
            <option value="0" <?=$solo_vencidos===0?'selected':''?>>No</option>
            <option value="1" <?=$solo_vencidos===1?'selected':''?>>Sí</option>
          </select>
        </div>
        <div style="grid-column:span 1;display:flex;gap:10px;align-items:end;justify-content:flex-end">
          <button class="btn primary" type="submit">🔎</button>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead><tr>
            <th>ID</th><th>Cliente</th><th>Fecha</th><th>Vence</th><th>Estado</th>
            <th class="right">Total</th><th class="right">Saldo</th><th class="right">Acciones</th>
          </tr></thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <?php
                $tag = 'info';
                if($r['estado']==='PAGADO') $tag='ok';
                if($r['estado']==='VENCIDO') $tag='bad';
                if($r['estado']==='PENDIENTE') $tag='warn';
              ?>
              <tr>
                <td class="mono"><b><?= (int)$r['id'] ?></b></td>
                <td>
                  <div><b><?= h($r['cliente']) ?></b></div>
                  <div class="small mono"><?= h($r['identificacion'] ?? '') ?> • Venta: <?= h($r['venta_id'] ?? '-') ?></div>
                </td>
                <td class="mono"><?= h(substr((string)$r['fecha'],0,10)) ?></td>
                <td class="mono">
                  <?= $r['vence'] ? h($r['vence']) : '—' ?>
                  <?php if($r['estado']!=='PAGADO' && !empty($r['vence']) && (int)$r['dias_venc']>0): ?>
                    <div class="small"><span class="tag bad">+<?= (int)$r['dias_venc'] ?> días</span></div>
                  <?php endif; ?>
                </td>
                <td><span class="tag <?= $tag ?>"><?= h($r['estado']) ?></span></td>
                <td class="right mono"><?= number_format((float)$r['total'],2,',','.') ?></td>
                <td class="right mono"><b><?= number_format((float)$r['saldo'],2,',','.') ?></b></td>
                <td class="right">
                  <div class="actions">
                    <a class="btn" href="cxc_detalle.php?id=<?=$r['id']?>">Ver</a>
                    <?php if((float)$r['saldo']>0.00001): ?>
                      <a class="btn warn" href="cxc_pago.php?cxc_id=<?=$r['id']?>">💳 Abonar</a>
                    <?php else: ?>
                      <span class="small">—</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(count($rows)===0): ?><tr><td colspan="8" class="small">Sin resultados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body></html>
