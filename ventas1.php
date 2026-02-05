<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$empresa_id=(int)$_SESSION['empresa_id'];

$rows=$pdo->prepare("
 SELECT v.id,v.tipo,v.estado,v.total,v.created_at,c.nombre cliente
 FROM ventas v
 LEFT JOIN clientes c ON c.id=v.cliente_id
 WHERE v.empresa_id=? ORDER BY v.id DESC
");
$rows->execute([$empresa_id]); $rows=$rows->fetchAll(PDO::FETCH_ASSOC);
function crc($n){ return "₡".number_format((float)$n,2,'.',','); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ventas | FAC-IL-CR</title><style>
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
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.h1{font-size:26px;font-weight:1000;margin:0}
.sub{color:var(--muted);margin-top:6px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid var(--border);font-weight:900;cursor:pointer}
.btn-primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));color:#fff}
.btn-warning{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));color:#111827;border-color:rgba(255,193,7,.55)}
.btn-ghost{background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));color:var(--txt)}
.card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:16px;box-shadow:0 20px 55px rgba(0,0,0,.45)}
.grid{display:grid;grid-template-columns:1.3fr .7fr;gap:14px;margin-top:14px}
@media(max-width:980px){.grid{grid-template-columns:1fr}}
.input, select{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.label{font-size:12px;color:var(--muted);margin:10px 0 6px}
.table{width:100%;border-collapse:collapse;margin-top:10px}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left}
.badge{display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:999px;font-weight:900;font-size:12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06)}
.badge.ok{border-color:rgba(255,193,7,.45);background:rgba(255,193,7,.12)}
.kpi{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:linear-gradient(135deg,rgba(11,94,215,.18),rgba(2,6,23,.18))}
.kpi .t{color:var(--muted);font-size:12px}
.kpi .v{font-weight:1000;font-size:18px}
.alert{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);margin-top:12px}
.alert.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.alert.ok{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.35)}
/* buscador */
.suggest{position:relative}
.suggest-list{position:absolute;z-index:50;left:0;right:0;top:100%;margin-top:6px;border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.92);backdrop-filter: blur(10px);box-shadow:0 18px 40px rgba(0,0,0,.45);display:none;max-height:320px;overflow:auto}
.suggest-item{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.suggest-item:hover{background:rgba(255,255,255,.06)}
.suggest-item b{font-weight:1000}
.muted{color:var(--muted);font-size:12px}
.pill{padding:4px 8px;border-radius:999px;border:1px solid rgba(255,193,7,.45);background:rgba(255,193,7,.12);font-weight:900;font-size:12px;color:var(--txt)}
.right{text-align:right}
.qty{width:92px}
</style></head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <h1 class="h1">Ventas</h1>
      <div class="sub">Listado de ventas. Creá una nueva venta con buscador de productos y carrito.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn btn-ghost" href="dashboard.php">← Dashboard</a>
      <a class="btn btn-primary" href="ventas_nuevo.php">+ Nueva venta</a>
    </div>
  </div>

  <div class="card" style="margin-top:14px">
    <table class="table">
      <thead>
        <tr>
          <th>#</th><th>Cliente</th><th>Tipo</th><th>Estado</th><th class="right">Total</th><th>Fecha</th><th class="right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['id'])?></td>
            <td><?=htmlspecialchars($r['cliente'] ?? '—')?></td>
            <td><span class="badge"><?=htmlspecialchars($r['tipo'])?></span></td>
            <td><span class="badge ok"><?=htmlspecialchars($r['estado'])?></span></td>
            <td class="right" style="font-weight:1000"><?=crc($r['total'])?></td>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td class="right" style="white-space:nowrap">
              <a class="btn btn-ghost" style="padding:8px 10px" href="ventas_ver.php?id=<?=$r['id']?>">Ver</a>
              <a class="btn btn-warning" style="padding:8px 10px" target="_blank" href="ventas_imprimir.php?id=<?=$r['id']?>">Imprimir</a>
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
