<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id = (int)$_SESSION['empresa_id'];

$q = trim($_GET['q'] ?? '');
$params = [$empresa_id];
$where = "empresa_id=?";
if($q !== ''){
  $where .= " AND (codigo LIKE ? OR descripcion LIKE ? OR cabys LIKE ? OR COALESCE(codigo_barras,'') LIKE ? OR COALESCE(categoria,'') LIKE ?)";
  $like = "%$q%";
  $params = array_merge($params, [$like,$like,$like,$like,$like]);
}

$st = $pdo->prepare("SELECT id,codigo,COALESCE(codigo_barras,'') codigo_barras,descripcion,COALESCE(categoria,'') categoria,
                            cabys,unidad,costo,margen,margen_minimo,precio,estado
                     FROM productos
                     WHERE $where
                     ORDER BY id DESC
                     LIMIT 500");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Productos | FAC-IL-CR</title>
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
.wrap{max-width:1400px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.input{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.grid{display:grid;grid-template-columns:1fr auto auto;gap:10px;align-items:end}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.off{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.small{font-size:12px;color:var(--muted)}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
</style>
</head>
<body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Productos</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn warn" href="productos_nuevo.php">➕ Nuevo</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Catálogo de productos</div>
        <div class="small">CABYS + impuestos + márgenes.</div>
      </div>
      <div class="small">Total: <?=count($rows)?></div>
    </div>
    <div class="bd">
      <form method="get" class="grid">
        <div>
          <div class="label">Buscar</div>
          <input class="input" name="q" value="<?=h($q)?>" placeholder="Código, barras, descripción, categoría o CABYS...">
        </div>
        <div>
          <div class="label">&nbsp;</div>
          <button class="btn primary" type="submit">🔎 Buscar</button>
        </div>
        <div>
          <div class="label">&nbsp;</div>
          <a class="btn" href="productos.php">Limpiar</a>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Descripción</th>
              <th>CABYS</th>
              <th>Categoría</th>
              <th class="right">Costo</th>
              <th class="right">Precio</th>
              <th class="right">Margen</th>
              <th>Estado</th>
              <th class="right">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td>
                <div style="font-weight:1000"><?=h($r['codigo'])?></div>
                <?php if($r['codigo_barras']): ?><div class="small">Barras: <?=h($r['codigo_barras'])?></div><?php endif; ?>
              </td>
              <td>
                <div style="font-weight:1000"><?=h($r['descripcion'])?></div>
                <div class="small">Unidad: <?=h($r['unidad'] ?? '-')?></div>
              </td>
              <td><?=h($r['cabys'] ?? '-')?></td>
              <td><?=h($r['categoria'] ?? '-')?></td>
              <td class="right">₡<?=number_format((float)$r['costo'],2,',','.')?></td>
              <td class="right">₡<?=number_format((float)$r['precio'],2,',','.')?></td>
              <td class="right">
                <div class="small">Min: <?=number_format((float)$r['margen_minimo'],3,'.','')?>%</div>
                <div style="font-weight:1000"><?=number_format((float)$r['margen'],3,'.','')?>%</div>
              </td>
              <td>
                <?php if((int)$r['estado']===1): ?>
                  <span class="tag ok">● Activo</span>
                <?php else: ?>
                  <span class="tag off">● Inactivo</span>
                <?php endif; ?>
              </td>
              <td class="right">
                <div class="actions">
                  <a class="btn" href="productos_editar.php?id=<?=$r['id']?>">✏️ Editar</a>
                  <a class="btn bad" href="productos_eliminar.php?id=<?=$r['id']?>" onclick="return confirm('¿Desactivar producto?');">🗑️ Desactivar</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(count($rows)==0): ?>
            <tr><td colspan="9" class="small">Sin resultados.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="small" style="margin-top:10px">
        Nota: La bodega no va en <b>productos</b>. El stock por bodega se controla en <code>inventario_existencias</code>.
      </div>
    </div>
  </div>
</div>
</body>
</html>
