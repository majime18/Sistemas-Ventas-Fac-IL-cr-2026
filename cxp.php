<?php
session_start(); require_once "config/db.php"; require_once "cxp_helpers.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$vence_col=cxp_vence_col($pdo);
$num_col=cxp_num_col($pdo);
[$prov_ced,$prov_email,$prov_tel]=prov_cols($pdo);

$desde=$_GET['desde'] ?? date('Y-m-01');
$hasta=$_GET['hasta'] ?? date('Y-m-d');
$q=trim($_GET['q'] ?? '');
$estado=$_GET['estado'] ?? 'TODOS';

$where="d.empresa_id=? AND DATE(d.fecha)>=? AND DATE(d.fecha)<=?";
$params=[$empresa_id,$desde,$hasta];

// build search (only existing columns)
$searchParts=["p.nombre LIKE ?","CAST(d.id AS CHAR) LIKE ?"];
if($prov_ced) $searchParts[]="p.$prov_ced LIKE ?";
if($num_col) $searchParts[]="d.$num_col LIKE ?";

if($q!==''){
  $where.=" AND (".implode(" OR ", $searchParts).")";
  $like="%$q%";
  // name + id always
  $params[]=$like; $params[]=$like;
  if($prov_ced) $params[]=$like;
  if($num_col) $params[]=$like;
}

if($estado!=='TODOS'){ $where.=" AND d.estado=?"; $params[]=$estado; }

$sel_num = $num_col ? ", d.$num_col AS numero_doc" : ", NULL AS numero_doc";
$sel_ced = $prov_ced ? ", p.$prov_ced AS cedula" : ", NULL AS cedula";
$sel_email = $prov_email ? ", p.$prov_email AS email" : ", NULL AS email";

$st=$pdo->prepare("SELECT d.id,d.fecha,d.$vence_col AS vence,d.total,d.saldo,d.estado,p.nombre proveedor $sel_num $sel_ced $sel_email
                   FROM cxp_documentos d JOIN proveedores p ON p.id=d.proveedor_id
                   WHERE $where ORDER BY d.id DESC LIMIT 1200");
$st->execute($params); $rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CXP | FAC-IL-CR</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444}
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CXP</span></div>
  <div class="actions"><a class="btn" href="dashboard.php">🏠 Dashboard</a><a class="btn primary" href="cxp_nuevo.php">➕ Nuevo</a></div>
</div>
<div class="wrap">
  <div class="card">
    <div class="hd"><div><div style="font-weight:1000;font-size:18px">Cuentas por Pagar</div><div class="small">Ultra-compatible (proveedores sin cédula/email)</div></div></div>
    <div class="bd">
      <form class="grid" method="get">
        <div style="grid-column:span 2"><div class="label">Desde</div><input class="input" type="date" name="desde" value="<?=h($desde)?>"></div>
        <div style="grid-column:span 2"><div class="label">Hasta</div><input class="input" type="date" name="hasta" value="<?=h($hasta)?>"></div>
        <div style="grid-column:span 5"><div class="label">Buscar</div><input class="input" name="q" value="<?=h($q)?>" placeholder="Proveedor, id, doc (si existe), cédula (si existe)..."></div>
        <div style="grid-column:span 2"><div class="label">Estado</div>
          <select class="input" name="estado">
            <option value="TODOS" <?=$estado==='TODOS'?'selected':''?>>Todos</option>
            <?php foreach(['PENDIENTE','VENCIDO','PAGADO'] as $e): ?><option value="<?=$e?>" <?=$estado===$e?'selected':''?>><?=$e?></option><?php endforeach; ?>
          </select>
        </div>
        <div style="grid-column:span 1;display:flex;align-items:end;justify-content:flex-end"><button class="btn primary" type="submit">🔎</button></div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead><tr><th>ID</th><th>Proveedor</th><th>Fecha</th><th>Vence</th><th>Estado</th><th class="right">Total</th><th class="right">Saldo</th><th class="right">Acciones</th></tr></thead>
          <tbody>
          <?php foreach($rows as $r): $tag=$r['estado']==='PAGADO'?'ok':($r['estado']==='VENCIDO'?'bad':'warn'); ?>
            <tr>
              <td class="mono"><b><?= (int)$r['id'] ?></b></td>
              <td>
                <b><?= h($r['proveedor']) ?></b>
                <?php if(!empty($r['cedula'])): ?><div class="small mono"><?= h($r['cedula']) ?></div><?php endif; ?>
                <?php if(!empty($r['email'])): ?><div class="small"><?= h($r['email']) ?></div><?php endif; ?>
                <?php if(!empty($r['numero_doc'])): ?><div class="small">Doc: <span class="mono"><?= h($r['numero_doc']) ?></span></div><?php endif; ?>
              </td>
              <td class="mono"><?= h($r['fecha']) ?></td>
              <td class="mono"><?= h($r['vence'] ?? '') ?></td>
              <td><span class="tag <?= $tag ?>"><?= h($r['estado']) ?></span></td>
              <td class="right mono"><?= nf($r['total']) ?></td>
              <td class="right mono"><b><?= nf($r['saldo']) ?></b></td>
              <td class="right"><div class="actions">
                <a class="btn" href="cxp_detalle.php?id=<?= (int)$r['id'] ?>">Ver</a>
                <?php if((float)$r['saldo']>0.00001): ?><a class="btn warn" href="cxp_pago.php?cxp_id=<?= (int)$r['id'] ?>">💳 Pagar</a><?php endif; ?>
              </div></td>
            </tr>
          <?php endforeach; if(count($rows)===0): ?><tr><td colspan="8" class="small">Sin datos.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body></html>
