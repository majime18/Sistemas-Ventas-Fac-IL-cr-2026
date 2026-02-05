<?php
session_start();
require_once "config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id<=0){ header("Location: proveedores.php"); exit; }

$st=$pdo->prepare("SELECT * FROM proveedores WHERE id=? AND empresa_id=?");
$st->execute([$id,$empresa_id]);
$row=$st->fetch(PDO::FETCH_ASSOC);
if(!$row){ header("Location: proveedores.php"); exit; }

$err='';
$nombre=trim($_POST['nombre'] ?? $row['nombre']);
$ident=trim($_POST['identificacion'] ?? ($row['identificacion'] ?? ''));
$email=trim($_POST['email'] ?? ($row['email'] ?? ''));
$tel=trim($_POST['telefono'] ?? ($row['telefono'] ?? ''));
$dir=trim($_POST['direccion'] ?? ($row['direccion'] ?? ''));
$estado=(int)($_POST['estado'] ?? (int)$row['estado']);

if($_SERVER['REQUEST_METHOD']==='POST'){
  if($nombre==='') $err="El nombre es obligatorio.";
  if(!$err){
    $up=$pdo->prepare("UPDATE proveedores SET nombre=?,identificacion=?,email=?,telefono=?,direccion=?,estado=? WHERE id=? AND empresa_id=?");
    $up->execute([$nombre,$ident===''?null:$ident,$email===''?null:$email,$tel===''?null:$tel,$dir===''?null:$dir,$estado,$id,$empresa_id]);
    header("Location: proveedores.php"); exit;
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Editar Proveedor</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">PROVEEDORES • EDITAR</span></div>
  <div class="actions"><a class="btn" href="proveedores.php">← Volver</a><button class="btn primary" form="f" type="submit">💾 Guardar</button></div>
</div>
<div class="wrap"><div class="card">
  <div class="hd"><div><div style="font-weight:1000;font-size:18px">Editar proveedor</div><div class="small">ID: <span class="mono"><?= (int)$id ?></span></div></div></div>
  <div class="bd">
    <?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
    <form id="f" method="post" class="grid">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <div style="grid-column:span 6"><div class="label">Nombre *</div><input class="input" name="nombre" value="<?= h($nombre) ?>" required></div>
      <div style="grid-column:span 3"><div class="label">Identificación</div><input class="input mono" name="identificacion" value="<?= h($ident) ?>"></div>
      <div style="grid-column:span 3"><div class="label">Teléfono</div><input class="input mono" name="telefono" value="<?= h($tel) ?>"></div>

      <div style="grid-column:span 6"><div class="label">Email</div><input class="input" type="email" name="email" value="<?= h($email) ?>"></div>
      <div style="grid-column:span 6"><div class="label">Dirección</div><textarea class="input" name="direccion" rows="3"><?= h($dir) ?></textarea></div>

      <div style="grid-column:span 3"><div class="label">Estado</div>
        <select class="input" name="estado">
          <option value="1" <?=$estado===1?'selected':''?>>Activo</option>
          <option value="0" <?=$estado===0?'selected':''?>>Inactivo</option>
        </select>
      </div>
    </form>
  </div>
</div></div>
</body></html>
