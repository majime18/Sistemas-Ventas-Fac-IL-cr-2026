<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
require_once "contabilidad_helpers.php";

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$q = trim($_GET['q'] ?? '');
$anulado = $_GET['anulado'] ?? 'TODOS';

$where="empresa_id=? AND fecha>=? AND fecha<DATE_ADD(?, INTERVAL 1 DAY)";
$params=[$empresa_id,$desde,$hasta];
if($q!==''){
  $where.=" AND (referencia LIKE ? OR descripcion LIKE ? OR CAST(id AS CHAR) LIKE ?)";
  $like="%$q%"; $params=array_merge($params,[$like,$like,$like]);
}
if($anulado!=='TODOS'){
  $where.=" AND anulado=?";
  $params[] = ($anulado==='SI'?1:0);
}

$st=$pdo->prepare("SELECT id,fecha,referencia,descripcion,origen,origen_id,anulado,created_at FROM cont_asientos WHERE $where ORDER BY fecha DESC,id DESC LIMIT 800");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Asientos | Contabilidad</title>
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
.btn.bad{background:linear-gradient(180deg,rgba(239,68,68,.9),rgba(239,68,68,.55));border-color:rgba(239,68,68,.55);color:#450a0a}
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
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.suggest{position:relative}
.slist{
  position:absolute;left:0;right:0;top:100%;margin-top:6px;z-index:80;
  border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden;
  background:rgba(2,6,23,.92);backdrop-filter: blur(12px);display:none;max-height:320px;overflow:auto;
}
.sitem{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.sitem:hover{background:rgba(255,255,255,.06)}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Asientos</span></div>
  <div class="actions">
    <a class="btn" href="contabilidad.php">← Contabilidad</a>
    <a class="btn primary" href="contabilidad_nuevo.php">➕ Nuevo asiento</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Búsqueda de asientos</div>
        <div class="small">Filtrá por fechas, texto o estado.</div>
      </div>
      <div class="small">Filas: <?=count($rows)?></div>
    </div>
    <div class="bd">
      <form method="get" class="grid">
        <div style="grid-column:span 2"><div class="label">Desde</div><input class="input" type="date" name="desde" value="<?=h($desde)?>"></div>
        <div style="grid-column:span 2"><div class="label">Hasta</div><input class="input" type="date" name="hasta" value="<?=h($hasta)?>"></div>
        <div style="grid-column:span 4"><div class="label">Buscar</div><input class="input" name="q" value="<?=h($q)?>" placeholder="ID, referencia o descripción..."></div>
        <div style="grid-column:span 2"><div class="label">Anulado</div>
          <select class="input" name="anulado">
            <option value="TODOS" <?=$anulado==='TODOS'?'selected':''?>>Todos</option>
            <option value="NO" <?=$anulado==='NO'?'selected':''?>>No</option>
            <option value="SI" <?=$anulado==='SI'?'selected':''?>>Sí</option>
          </select>
        </div>
        <div style="grid-column:span 2;display:flex;gap:10px;align-items:end;justify-content:flex-end">
          <button class="btn primary" type="submit">🔎 Aplicar</button>
          <a class="btn" href="contabilidad_asientos.php">Limpiar</a>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead><tr>
            <th>ID</th><th>Fecha</th><th>Referencia</th><th>Descripción</th><th>Origen</th><th>Estado</th><th class="right">Acción</th>
          </tr></thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td class="mono"><?= (int)$r['id'] ?></td>
                <td class="mono"><?= h($r['fecha']) ?></td>
                <td><?= h($r['referencia'] ?? '') ?></td>
                <td><?= h($r['descripcion'] ?? '') ?></td>
                <td class="mono"><?= h($r['origen'] ?? '') ?><?= $r['origen_id'] ? ' #'.h($r['origen_id']) : '' ?></td>
                <td><?= ((int)$r['anulado']===1) ? '<span class="tag bad">ANULADO</span>' : '<span class="tag ok">OK</span>' ?></td>
                <td class="right"><a class="btn" href="contabilidad_ver.php?id=<?=$r['id']?>">Ver</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if(count($rows)===0): ?><tr><td colspan="7" class="small">Sin resultados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body></html>
