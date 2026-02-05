<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$id=(int)($_GET['id']??0);
$r=$pdo->prepare("SELECT * FROM roles WHERE id=?");$r->execute([$id]);$r=$r->fetch();
if($_POST){
 $pdo->prepare("UPDATE roles SET nombre=? WHERE id=?")->execute([$_POST['nombre'],$id]);
 header("Location: roles.php"); exit;
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Editar Rol</title><style>
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
<body><div class="container"><div class="h1">Editar Rol</div>
<div class="card"><form method="post">
<label>Nombre</label><input class="input" name="nombre" value="<?=$r['nombre']?>">
<button class="btn btn-primary">Actualizar</button>
</form></div></div></body></html>
