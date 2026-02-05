<?php
session_start(); require_once "config/db.php"; require_once "cxp_helpers.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$vence_col=cxp_vence_col($pdo);
$num_col=cxp_num_col($pdo);
[$prov_ced,$prov_email,$prov_tel]=prov_cols($pdo);

$id=(int)($_GET['id'] ?? 0); if($id<=0){ header("Location: cxp.php"); exit; }

$sel_num = $num_col ? ", d.$num_col AS numero_doc" : ", NULL AS numero_doc";
$sel_ced = $prov_ced ? ", p.$prov_ced AS cedula" : ", NULL AS cedula";
$sel_email = $prov_email ? ", p.$prov_email AS email" : ", NULL AS email";

$st=$pdo->prepare("SELECT d.*, p.nombre proveedor $sel_ced $sel_email $sel_num
                   FROM cxp_documentos d JOIN proveedores p ON p.id=d.proveedor_id
                   WHERE d.id=? AND d.empresa_id=?");
$st->execute([$id,$empresa_id]); $doc=$st->fetch(PDO::FETCH_ASSOC); if(!$doc){ header("Location: cxp.php"); exit; }

$pag=$pdo->prepare("SELECT id, fecha, metodo, monto, referencia, usuario_id, anulado
                    FROM cxp_pagos WHERE empresa_id=? AND proveedor_id=? ORDER BY id DESC LIMIT 1000");
$pag->execute([$empresa_id,(int)$doc['proveedor_id']]);
$pagos=$pag->fetchAll(PDO::FETCH_ASSOC);

$vence=$doc[$vence_col] ?? null; $estado=$doc['estado'] ?? 'PENDIENTE';
$tag=$estado==='PAGADO'?'ok':($estado==='VENCIDO'?'bad':'warn');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CXP #<?= (int)$id ?></title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444}
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CXP • #<?= (int)$id ?></span></div>
  <div class="actions"><a class="btn" href="cxp.php">← CXP</a><?php if((float)$doc['saldo']>0.00001): ?><a class="btn warn" href="cxp_pago.php?cxp_id=<?= (int)$id ?>">💳 Pagar</a><?php endif; ?></div>
</div>
<div class="wrap"><div class="card">
  <div class="hd"><div><div style="font-weight:1000;font-size:18px">Detalle</div>
    <div class="small"><?= h($doc['proveedor']) ?>
      <?php if(!empty($doc['cedula'])): ?> • <span class="mono"><?= h($doc['cedula']) ?></span><?php endif; ?>
      <?php if(!empty($doc['email'])): ?> • <?= h($doc['email']) ?><?php endif; ?>
      <?php if(!empty($doc['numero_doc'])): ?> • Doc: <span class="mono"><?= h($doc['numero_doc']) ?></span><?php endif; ?>
    </div></div>
    <span class="tag <?= $tag ?>"><?= h($estado) ?></span></div>
  <div class="bd">
    <div class="grid">
      <div style="grid-column:span 4"><div class="label">Fecha</div><div class="mono"><b><?= h($doc['fecha']) ?></b></div></div>
      <div style="grid-column:span 4"><div class="label">Vence</div><div class="mono"><b><?= h($vence ?? '—') ?></b></div></div>
      <div style="grid-column:span 4"><div class="label">Saldo</div><div class="mono"><b><?= nf($doc['saldo']) ?></b></div></div>
    </div>
    <div style="margin-top:12px" class="notice small">Pagos (cxp_pagos): tu tabla actual guarda pagos por proveedor (sin cxp_documento_id).</div>
    <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
      <table class="table">
        <thead><tr><th>ID</th><th>Fecha</th><th>Método</th><th>Ref</th><th class="right">Monto</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach($pagos as $a): ?>
          <tr>
            <td class="mono"><?= (int)$a['id'] ?></td>
            <td class="mono"><?= h($a['fecha']) ?></td>
            <td><?= h($a['metodo']) ?></td>
            <td class="mono"><?= h($a['referencia'] ?? '') ?></td>
            <td class="right mono"><b><?= nf($a['monto']) ?></b></td>
            <td><?= ((int)$a['anulado']===1) ? '<span class="tag bad">ANULADO</span>' : '<span class="tag ok">OK</span>' ?></td>
          </tr>
        <?php endforeach; if(count($pagos)===0): ?><tr><td colspan="6" class="small">Sin pagos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>
</body></html>
