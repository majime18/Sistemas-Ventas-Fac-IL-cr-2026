<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$rows=$pdo->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Roles</title><style>
:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#f4f6f9;--texto:#111827;--border:#e5e7eb;--card:#fff}
body{margin:0;font-family:Segoe UI,Arial;background:var(--fondo);color:var(--texto)}
.container{max-width:1200px;margin:auto;padding:20px}
.h1{font-size:22px;font-weight:900}
.btn{padding:8px 12px;border-radius:10px;border:0;font-weight:900;cursor:pointer}
.btn-primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));color:#fff}
.btn-warning{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal))}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px}
.table{width:100%;border-collapse:collapse;margin-top:10px}
.table th,.table td{padding:10px;border-bottom:1px solid var(--border)}
.input{width:100%;padding:8px;border-radius:8px;border:1px solid var(--border)}
</style></head>
<body><div class="container"><div class="h1">Roles <a class="btn btn-primary" href="roles_nuevo.php">Nuevo</a></div>
<div class="card"><table class="table">
<tr><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
<?php foreach($rows as $r):?>
<tr><td><?=$r['nombre']?></td><td><?=$r['estado']?'Activo':'Inactivo'?></td>
<td><a class="btn btn-warning" href="roles_editar.php?id=<?=$r['id']?>">Editar</a></td></tr>
<?php endforeach;?></table></div></div></body></html>
