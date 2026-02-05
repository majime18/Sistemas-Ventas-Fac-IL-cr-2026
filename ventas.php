<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id = (int)$_SESSION['empresa_id'];

$q      = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$tipo   = trim($_GET['tipo'] ?? '');
$desde  = trim($_GET['desde'] ?? '');
$hasta  = trim($_GET['hasta'] ?? '');

$where = "WHERE v.empresa_id=?";
$params = [$empresa_id];

if($q!==''){
  // Buscar por ID, cliente, o consecutivo/clave si existiera (a futuro)
  $where .= " AND (v.id = ? OR c.nombre LIKE ? OR c.identificacion LIKE ? OR v.observaciones LIKE ?)";
  $qid = ctype_digit($q) ? (int)$q : 0;
  $like = "%$q%";
  $params[] = $qid;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}
if($estado!==''){
  $where .= " AND v.estado = ?";
  $params[] = $estado;
}
if($tipo!==''){
  $where .= " AND v.tipo = ?";
  $params[] = $tipo;
}
if($desde!==''){
  $where .= " AND DATE(v.created_at) >= ?";
  $params[] = $desde;
}
if($hasta!==''){
  $where .= " AND DATE(v.created_at) <= ?";
  $params[] = $hasta;
}

$sql = "
  SELECT v.id,v.tipo,v.estado,v.total,v.created_at,
         c.nombre cliente,
         v.moneda, v.condicion_venta, v.medio_pago,
         v.fe_documento_id, v.facturada_at
  FROM ventas v
  LEFT JOIN clientes c ON c.id=v.cliente_id
  $where
  ORDER BY v.id DESC
  LIMIT 500
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function crc($n){ return "₡".number_format((float)$n,2,'.',','); }

$css = <<<CSS
:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#0b1220;--card:rgba(17,24,39,.72);--border:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{margin:0;color:var(--txt);
 background:
  radial-gradient(900px 600px at 15% 20%, rgba(11,94,215,.55), transparent 65%),
  radial-gradient(900px 600px at 82% 28%, rgba(255,193,7,.22), transparent 60%),
  linear-gradient(180deg,#020617,var(--fondo));
 min-height:100vh;
}
a{color:inherit;text-decoration:none}
.wrap{max-width:1200px;margin:auto;padding:22px}
.top{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
.h1{font-size:26px;font-weight:1000;margin:0}
.sub{color:var(--muted);margin-top:6px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid var(--border);font-weight:900;cursor:pointer}
.btn-primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));color:#fff}
.btn-ghost{background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));color:var(--txt)}
.card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:16px;box-shadow:0 20px 55px rgba(0,0,0,.45)}
.input, select{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.label{font-size:12px;color:var(--muted);margin:10px 0 6px}
.table{width:100%;border-collapse:collapse;margin-top:10px}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left}
.right{text-align:right}
.badge{display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:999px;font-weight:900;font-size:12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06)}
.badge.ok{border-color:rgba(255,193,7,.45);background:rgba(255,193,7,.12)}
.badge.green{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.filters{display:grid;grid-template-columns:1.4fr .8fr .8fr .8fr .8fr auto;gap:10px;align-items:end}
@media(max-width:980px){.filters{grid-template-columns:1fr 1fr;}.filters .span2{grid-column:1 / -1}}
CSS;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ventas | FAC-IL-CR</title><style><?=$css?></style></head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <h1 class="h1">Ventas</h1>
      <div class="sub">Buscador por #venta, cliente, identificación u observaciones. También podés filtrar por fechas/estado/tipo.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn btn-ghost" href="dashboard.php">🏠 Dashboard</a>
      <a class="btn btn-primary" href="ventas_nuevo.php">+ Nueva venta</a>
    </div>
  </div>

  <div class="card" style="margin-top:14px">
    <form method="get" class="filters">
      <div class="span2">
        <div class="label">Buscar</div>
        <input class="input" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Ej: 125, Ferretería Pérez, 3101..., entrega, etc.">
      </div>

      <div>
        <div class="label">Estado</div>
        <select class="input" name="estado">
          <option value="">Todos</option>
          <?php foreach(['ABIERTA','FACTURADA','ANULADA'] as $e): ?>
            <option value="<?=$e?>" <?=($estado===$e?'selected':'')?>><?=$e?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <div class="label">Tipo</div>
        <select class="input" name="tipo">
          <option value="">Todos</option>
          <?php foreach(['VENTA','COTIZACION','PEDIDO'] as $t): ?>
            <option value="<?=$t?>" <?=($tipo===$t?'selected':'')?>><?=$t?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <div class="label">Desde</div>
        <input class="input" type="date" name="desde" value="<?=htmlspecialchars($desde)?>">
      </div>

      <div>
        <div class="label">Hasta</div>
        <input class="input" type="date" name="hasta" value="<?=htmlspecialchars($hasta)?>">
      </div>

      <div>
        <div class="label">&nbsp;</div>
        <button class="btn btn-ghost" type="submit">Filtrar</button>
      </div>
    </form>

    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Cliente</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th>FE</th>
          <th class="right">Total</th>
          <th>Fecha</th>
          <th class="right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="8" class="muted">No hay resultados con esos filtros.</td></tr>
        <?php endif; ?>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['id'])?></td>
            <td><?=htmlspecialchars($r['cliente'] ?? '—')?></td>
            <td><span class="badge"><?=htmlspecialchars($r['tipo'])?></span></td>
            <td><span class="badge ok"><?=htmlspecialchars($r['estado'])?></span></td>
            <td>
              <?php if(!empty($r['fe_documento_id'])): ?>
                <span class="badge green">SI</span>
              <?php else: ?>
                <span class="badge">NO</span>
              <?php endif; ?>
            </td>
            <td class="right" style="font-weight:1000"><?=crc($r['total'])?></td>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td class="right" style="white-space:nowrap">
              <a class="btn btn-ghost" style="padding:8px 10px" href="ventas_ver.php?id=<?=$r['id']?>">Ver</a>
              <a class="btn btn-ghost" style="padding:8px 10px" target="_blank" href="ventas_imprimir.php?id=<?=$r['id']?>">Imprimir</a>
              <a class="btn btn-ghost" style="padding:8px 10px" href="ventas_editar.php?id=<?=$r['id']?>">Editar</a>
              <a class="btn btn-ghost" style="padding:8px 10px" href="ventas_eliminar.php?id=<?=$r['id']?>" onclick="return confirm('¿Eliminar?');">Eliminar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
