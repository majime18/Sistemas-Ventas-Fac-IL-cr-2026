<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){header("Location: login.php");exit;}

$roles=$pdo->prepare("SELECT id,nombre FROM roles WHERE empresa_id=? AND estado=1");
$roles->execute([$_SESSION['empresa_id']]);
$roles=$roles->fetchAll();

$modulos=['dashboard','reportes','empresas','sucursales','usuarios','roles','productos','inventario','clientes','ventas','facturacion','cxc','proveedores','cxp','contabilidad','comisiones','auditoria'];

if($_POST){
 foreach($_POST['perm'] as $rol_id=>$mods){
  foreach($mods as $mod=>$p){
   $st=$pdo->prepare("REPLACE INTO permisos (empresa_id,rol_id,modulo,puede_ver,puede_crear,puede_editar,puede_eliminar)
                      VALUES (?,?,?,?,?,?,?)");
   $st->execute([
     $_SESSION['empresa_id'],$rol_id,$mod,
     isset($p['ver'])?1:0,
     isset($p['crear'])?1:0,
     isset($p['editar'])?1:0,
     isset($p['eliminar'])?1:0
   ]);
  }
 }
 header("Location: permisos.php"); exit;
}

$permsRaw=$pdo->prepare("SELECT * FROM permisos WHERE empresa_id=?");
$permsRaw->execute([$_SESSION['empresa_id']]);
$perms=[];
foreach($permsRaw as $p){
 $perms[$p['rol_id']][$p['modulo']]=$p;
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Permisos</title><style>

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
.chk{transform:scale(1.2)}

</style></head>
<body><div class="container">
<div class="h1">Permisos por Rol</div>
<form method="post">
<?php foreach($roles as $r):?>
<div class="card" style="margin-top:14px">
<b><?=$r['nombre']?></b>
<table class="table">
<tr><th>Módulo</th><th>Ver</th><th>Crear</th><th>Editar</th><th>Eliminar</th></tr>
<?php foreach($modulos as $m):
$p=$perms[$r['id']][$m]??[];?>
<tr>
<td><?=$m?></td>
<td><input class="chk" type="checkbox" name="perm[<?=$r['id']?>][<?=$m?>][ver]" <?=!empty($p['puede_ver'])?'checked':''?>></td>
<td><input class="chk" type="checkbox" name="perm[<?=$r['id']?>][<?=$m?>][crear]" <?=!empty($p['puede_crear'])?'checked':''?>></td>
<td><input class="chk" type="checkbox" name="perm[<?=$r['id']?>][<?=$m?>][editar]" <?=!empty($p['puede_editar'])?'checked':''?>></td>
<td><input class="chk" type="checkbox" name="perm[<?=$r['id']?>][<?=$m?>][eliminar]" <?=!empty($p['puede_eliminar'])?'checked':''?>></td>
</tr>
<?php endforeach;?>
</table>
</div>
<?php endforeach;?>
<button class="btn btn-primary" style="margin-top:14px">Guardar Permisos</button>
</form>
</div></body></html>
