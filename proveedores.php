<?php
session_start();
require_once "config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);

$q=trim($_GET['q'] ?? '');
$estado=$_GET['estado'] ?? 'TODOS';

$where="empresa_id=?";
$params=[$empresa_id];

if($q!==''){
  $where.=" AND (nombre LIKE ? OR identificacion LIKE ? OR email LIKE ? OR telefono LIKE ?)";
  $like="%$q%"; $params=array_merge($params,[$like,$like,$like,$like]);
}
if($estado!=='TODOS'){
  $where.=" AND estado=?";
  $params[] = ($estado==='ACTIVO') ? 1 : 0;
}

$st=$pdo->prepare("SELECT id,nombre,identificacion,email,telefono,estado,created_at FROM proveedores WHERE $where ORDER BY id DESC LIMIT 2000");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Proveedores | FAC-IL-CR</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{margin:0;color:var(--txt);background:radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),linear-gradient(180deg,#020617,var(--fondo));min-height:100vh}
.header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08);background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));position:sticky;top:0;backdrop-filter: blur(12px);z-index:60}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.btn.danger{background:linear-gradient(180deg,#ef4444,#b91c1c);border-color:rgba(239,68,68,.55)}
.wrap{max-width:1500px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr))}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.input,select,textarea{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.input:focus,select:focus,textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
</style></head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">PROVEEDORES</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn primary" href="proveedores_nuevo.php">➕ Nuevo</a>
  </div>
</div>
<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Proveedores</div>
        <div class="small">CRUD completo (activo/inactivo). No borra crítico: se desactiva.</div>
      </div>
    </div>
    <div class="bd">
      <form class="grid" method="get">
        <div style="grid-column:span 8">
          <div class="label">Buscar</div>
          <input class="input" name="q" value="<?=h($q)?>" placeholder="Nombre, identificación, email, teléfono...">
        </div>
        <div style="grid-column:span 3">
          <div class="label">Estado</div>
          <select class="input" name="estado">
            <option value="TODOS" <?=$estado==='TODOS'?'selected':''?>>Todos</option>
            <option value="ACTIVO" <?=$estado==='ACTIVO'?'selected':''?>>Activos</option>
            <option value="INACTIVO" <?=$estado==='INACTIVO'?'selected':''?>>Inactivos</option>
          </select>
        </div>
        <div style="grid-column:span 1;display:flex;align-items:end;justify-content:flex-end">
          <button class="btn primary" type="submit">🔎</button>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Proveedor</th>
              <th>Contacto</th>
              <th>Estado</th>
              <th>Creado</th>
              <th class="right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): $tag=((int)$r['estado']===1)?'ok':'bad'; ?>
              <tr>
                <td class="mono"><b><?= (int)$r['id'] ?></b></td>
                <td>
                  <b><?= h($r['nombre']) ?></b>
                  <?php if(!empty($r['identificacion'])): ?><div class="small mono"><?= h($r['identificacion']) ?></div><?php endif; ?>
                </td>
                <td>
                  <?php if(!empty($r['email'])): ?><div><?= h($r['email']) ?></div><?php endif; ?>
                  <?php if(!empty($r['telefono'])): ?><div class="small mono"><?= h($r['telefono']) ?></div><?php endif; ?>
                </td>
                <td><span class="tag <?=$tag?>"><?= ((int)$r['estado']===1)?'ACTIVO':'INACTIVO' ?></span></td>
                <td class="small mono"><?= h($r['created_at']) ?></td>
                <td class="right">
                  <div class="actions">
                    <a class="btn" href="proveedores_editar.php?id=<?= (int)$r['id'] ?>">✏️ Editar</a>
                    <a class="btn danger" href="proveedores_eliminar.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('¿Seguro? Esto solo desactiva el proveedor.');">⛔ Desactivar</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; if(count($rows)===0): ?>
              <tr><td colspan="6" class="small">Sin proveedores.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body></html>
