<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$st=$pdo->query("SELECT s.*, e.nombre empresa FROM sucursales s JOIN empresas e ON e.id=s.empresa_id ORDER BY s.id DESC");
$rows=$st->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Sucursales</title><style>
:root{
 --azul:#0b5ed7; --azul-metal:#084298;
 --amarillo:#ffc107; --amarillo-metal:#ffca2c;
 --fondo:#f4f6f9; --texto:#111827; --muted:#6b7280;
 --card:#fff; --border:#e5e7eb; --shadow:0 10px 30px rgba(0,0,0,.08);
}
*{box-sizing:border-box}
body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:var(--fondo);color:var(--texto)}
a{text-decoration:none;color:inherit}
.container{max-width:1200px;margin:0 auto;padding:20px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.h1{font-size:22px;font-weight:900}
.btn{border:0;border-radius:12px;padding:10px 12px;font-weight:900;cursor:pointer}
.btn-primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));color:#fff}
.btn-warning{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));color:#111827}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:14px;box-shadow:var(--shadow)}
.table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-top:10px}
.table th,.table td{padding:12px;border-bottom:1px solid var(--border)}
.table th{background:#f8fafc;font-size:12px;text-transform:uppercase;letter-spacing:.3px}
.table tr:last-child td{border-bottom:0}
.input{width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--border)}
.alert{margin-top:10px;padding:10px 12px;border-radius:12px;border:1px solid var(--border)}
.alert.ok{background:#dcfce7;border-color:#86efac}
.alert.err{background:#fee2e2;border-color:#fecaca}
</style></head>
<body><div class="container">
<div class="header"><div class="h1">Sucursales</div><a class="btn btn-primary" href="sucursales_nuevo.php">Nueva sucursal</a></div>
<div class="card">
<table class="table">
<thead><tr><th>ID</th><th>Empresa</th><th>Nombre</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($rows as $r):?>
<tr>
<td><?=$r['id']?></td>
<td><?=htmlspecialchars($r['empresa'])?></td>
<td><?=htmlspecialchars($r['nombre'])?></td>
<td><?=$r['estado']?'Activa':'Inactiva'?></td>
<td>
<a class="btn btn-warning" href="sucursales_editar.php?id=<?=$r['id']?>">Editar</a>
<a class="btn" href="sucursales_eliminar.php?id=<?=$r['id']?>" onclick="return confirm('¿Inactivar sucursal?')">Eliminar</a>
</td>
</tr>
<?php endforeach;?>
</tbody></table>
</div></div></body></html>
