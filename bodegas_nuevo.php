<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)$_SESSION['empresa_id'];
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$err=''; $ok='';
$r=['nombre'=>'','estado'=>1,'sucursal_id'=>(int)($_SESSION['sucursal_id']??1)];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $nombre=trim($_POST['nombre']??'');
  $sucursal_id=(int)($_POST['sucursal_id']??($r['sucursal_id']??(int)($_SESSION['sucursal_id']??1)));
  $estado=(int)($_POST['estado']??1);
  if($nombre==='') $err='Nombre requerido.';

  if(!$err){
    $st=$pdo->prepare("INSERT INTO bodegas (empresa_id,sucursal_id,nombre,estado) VALUES (?,?,?,?)");
    $st->execute([$empresa_id,$sucursal_id,$nombre,$estado]);
    header("Location: bodegas.php"); exit;
  }

}
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nueva bodega | FAC-IL-CR</title>
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
.btn.good{background:linear-gradient(180deg,rgba(34,197,94,.85),rgba(34,197,94,.55));border-color:rgba(34,197,94,.55);color:#052e16}
.wrap{max-width:1400px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.input, select{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus, select:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.grid{display:grid;grid-template-columns:1fr 1fr auto auto;gap:10px;align-items:end}
@media(max-width:1000px){.grid{grid-template-columns:1fr}}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.small{font-size:12px;color:var(--muted)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);margin-bottom:10px}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.notice.ok{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.35)}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.suggest{position:relative}
.slist{
  position:absolute;left:0;right:0;top:100%;margin-top:6px;z-index:80;
  border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden;
  background:rgba(2,6,23,.92);backdrop-filter: blur(12px);display:none;max-height:360px;overflow:auto;
}
.sitem{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.sitem:hover{background:rgba(255,255,255,.06)}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Bodegas • Nuevo</span></div>
  <div class="actions">
    <a class="btn" href="bodegas.php">← Volver</a>
    <button class="btn primary" form="f" type="submit">💾 Guardar</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd"><div style="font-weight:1000;font-size:18px">Nueva bodega</div></div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?=h($err)?></div><?php endif; ?>
      <?php if($ok): ?><div class="notice ok"><?=h($ok)?></div><?php endif; ?>
      <form id="f" method="post" style="max-width:760px">
        <div class="label">Nombre</div>
        <input class="input" name="nombre" value="<?=h($r['nombre'] ?? '')?>" required>

        <div class="label">Sucursal ID</div>
        <input class="input" name="sucursal_id" type="number" value="<?= (int)($r['sucursal_id'] ?? 1) ?>">

        <div class="label">Estado</div>
        <select class="input" name="estado">
          <option value="1" <?=(isset($r['estado']) && (int)$r['estado']===1)?'selected':''?>>Activa</option>
          <option value="0" <?=(isset($r['estado']) && (int)$r['estado']===0)?'selected':''?>>Inactiva</option>
        </select>

        <div class="notice" style="margin-top:12px">
          <b>Nota:</b> El stock por bodega se maneja en <code>inventario_existencias</code>.
        </div>
      </form>
    </div>
  </div>
</div>
</body></html>
