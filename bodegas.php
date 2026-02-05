<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id = (int)$_SESSION['empresa_id'];

$q = trim($_GET['q'] ?? '');
$params = [$empresa_id];
$where = "empresa_id=?";
if($q!==''){
  $where .= " AND nombre LIKE ?";
  $params[] = "%$q%";
}
$st = $pdo->prepare("SELECT id,sucursal_id,nombre,estado,created_at FROM bodegas WHERE $where ORDER BY id DESC");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bodegas | FAC-IL-CR</title>
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
.btn.good{background:linear-gradient(180deg,rgba(34,197,94,.85),rgba(34,197,94,.55));border-color:rgba(34,197,94,.55);color:#052e16}
.wrap{max-width:1400px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.input, select{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus, select:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.grid{display:grid;grid-template-columns:1fr 1fr auto auto;gap:10px;align-items:end}
@media(max-width:1000px){.grid{grid-template-columns:1fr}}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.small{font-size:12px;color:var(--muted)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);margin-bottom:10px}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.notice.ok{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.35)}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.suggest{position:relative}
.slist{
  position:absolute;left:0;right:0;top:100%;margin-top:6px;z-index:80;
  border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden;
  background:rgba(2,6,23,.92);backdrop-filter: blur(12px);display:none;max-height:360px;overflow:auto;
}
.sitem{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.sitem:hover{background:rgba(255,255,255,.06)}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Bodegas</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn warn" href="bodegas_nuevo.php">➕ Nueva bodega</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Bodegas</div>
        <div class="small">Necesarias para ventas POS e inventario por ubicación.</div>
      </div>
      <div class="small">Total: <?=count($rows)?></div>
    </div>
    <div class="bd">
      <form method="get" class="grid" style="grid-template-columns:1fr auto auto;max-width:900px">
        <div>
          <div class="label">Buscar</div>
          <input class="input" name="q" value="<?=h($q)?>" placeholder="Nombre de la bodega...">
        </div>
        <div>
          <div class="label">&nbsp;</div>
          <button class="btn primary" type="submit">🔎 Buscar</button>
        </div>
        <div>
          <div class="label">&nbsp;</div>
          <a class="btn" href="bodegas.php">Limpiar</a>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead><tr>
            <th>ID</th><th>Nombre</th><th>Sucursal</th><th>Estado</th><th class="right">Acciones</th>
          </tr></thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td style="font-weight:1000"><?=h($r['nombre'])?></td>
                <td><?= (int)$r['sucursal_id'] ?></td>
                <td>
                  <?php if((int)$r['estado']===1): ?><span class="tag ok">● Activa</span>
                  <?php else: ?><span class="tag bad">● Inactiva</span><?php endif; ?>
                </td>
                <td class="right">
                  <div class="actions">
                    <a class="btn" href="bodegas_editar.php?id=<?=$r['id']?>">✏️ Editar</a>
                    <a class="btn bad" href="bodegas_eliminar.php?id=<?=$r['id']?>" onclick="return confirm('¿Desactivar bodega?');">🗑️ Desactivar</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(count($rows)===0): ?><tr><td colspan="5" class="small">Sin bodegas registradas.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="notice" style="margin-top:12px">
        <b>Tip:</b> Creá al menos <b>Bodega Principal</b> para que el POS te deje guardar ventas.
      </div>
    </div>
  </div>
</div>
</body></html>
