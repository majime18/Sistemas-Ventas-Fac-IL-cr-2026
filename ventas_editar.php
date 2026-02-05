<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}
$id=(int)($_GET['id']??0);
$v=$pdo->prepare("SELECT * FROM ventas WHERE id=? AND empresa_id=?");
$v->execute([$id,$_SESSION['empresa_id']]); $v=$v->fetch();
if(!$v)die("Venta no encontrada");
if($_POST){
 $pdo->prepare("UPDATE ventas SET total=?,tipo=? WHERE id=?")
     ->execute([$_POST['total'],$_POST['tipo'],$id]);
 header("Location: ventas.php"); exit;
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Editar Venta</title><style>
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
.badge{padding:4px 8px;border-radius:8px;font-weight:800}
</style></head>
<body><div class="container">
<div class="h1">Editar Venta</div>
<div class="card"><form method="post">
<label>Tipo</label>
<select class="input" name="tipo">
<option value="COTIZACION" <?=$v['tipo']=='COTIZACION'?'selected':''?>>COTIZACIÓN</option>
<option value="PEDIDO" <?=$v['tipo']=='PEDIDO'?'selected':''?>>PEDIDO</option>
<option value="VENTA" <?=$v['tipo']=='VENTA'?'selected':''?>>VENTA</option>
</select>
<label>Total</label><input class="input" type="number" step="0.01" name="total" value="<?=$v['total']?>">
<button class="btn btn-primary">Actualizar</button>
</form></div></div></body></html>
