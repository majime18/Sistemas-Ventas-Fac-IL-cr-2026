<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
function col_exists(PDO $pdo,string $t,string $c):bool{
  $st=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$t,$c]); return ((int)$st->fetchColumn())>0;
}

if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);

$usuarios = $pdo->prepare("SELECT id,nombre FROM usuarios ".(col_exists($pdo,'usuarios','empresa_id')?"WHERE empresa_id=?":"")." ORDER BY nombre");
if(col_exists($pdo,'usuarios','empresa_id')) $usuarios->execute([$empresa_id]); else $usuarios->execute();
$usuarios=$usuarios->fetchAll(PDO::FETCH_ASSOC);

$productos = $pdo->prepare("SELECT id,codigo,descripcion FROM productos ".(col_exists($pdo,'productos','empresa_id')?"WHERE empresa_id=?":"")." ORDER BY descripcion LIMIT 5000");
if(col_exists($pdo,'productos','empresa_id')) $productos->execute([$empresa_id]); else $productos->execute();
$productos=$productos->fetchAll(PDO::FETCH_ASSOC);

$err='';
$usuario_id=(int)($_POST['usuario_id'] ?? 0);
$producto_id=(int)($_POST['producto_id'] ?? 0);
$porcentaje=(float)($_POST['porcentaje'] ?? 0);
$tipo=$_POST['tipo'] ?? 'POR_VENTA';
$estado=(int)($_POST['estado'] ?? 1);

if($_SERVER['REQUEST_METHOD']==='POST'){
  if($porcentaje<=0) $err="El porcentaje debe ser mayor que 0.";
  if(!$err){
    $st=$pdo->prepare("INSERT INTO comisiones_reglas (empresa_id,usuario_id,producto_id,porcentaje,tipo,estado) VALUES (?,?,?,?,?,?)");
    $st->execute([$empresa_id, $usuario_id>0?$usuario_id:null, $producto_id>0?$producto_id:null, $porcentaje, $tipo, $estado]);
    header("Location: comisiones.php?tab=reglas&msg=".urlencode("Regla creada")); exit;
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva Regla de Comisión</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
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
.wrap{max-width:1600px;margin:auto;padding:14px}
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
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
hr.sep{border:0;border-top:1px solid rgba(255,255,255,.10);margin:14px 0}
</style></head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">COMISIONES • NUEVA REGLA</span></div>
  <div class="actions"><a class="btn" href="comisiones.php?tab=reglas">← Volver</a><button class="btn primary" form="f" type="submit">💾 Guardar</button></div>
</div>

<div class="wrap"><div class="card">
  <div class="hd"><div><div style="font-weight:1000;font-size:18px">Crear regla</div><div class="small">Podés crear reglas globales (sin usuario o sin producto)</div></div></div>
  <div class="bd">
    <?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
    <form id="f" method="post" class="grid">
      <div style="grid-column:span 4">
        <div class="label">Usuario (opcional)</div>
        <select class="input" name="usuario_id">
          <option value="0">Cualquier usuario</option>
          <?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>" <?=$usuario_id===(int)$u['id']?'selected':''?>><?=h($u['nombre'])?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="grid-column:span 5">
        <div class="label">Producto (opcional)</div>
        <select class="input" name="producto_id">
          <option value="0">Cualquier producto</option>
          <?php foreach($productos as $p): ?>
            <option value="<?=$p['id']?>" <?=$producto_id===(int)$p['id']?'selected':''?>><?=h(($p['codigo']?($p['codigo'].' - '):'').$p['descripcion'])?></option>
          <?php endforeach; ?>
        </select>
        <div class="small">Tip: si son muchos productos, luego hacemos buscador tipo POS aquí también.</div>
      </div>
      <div style="grid-column:span 3">
        <div class="label">Porcentaje *</div>
        <input class="input mono" type="number" step="0.001" min="0" name="porcentaje" value="<?=h((string)$porcentaje)?>" required>
      </div>

      <div style="grid-column:span 4">
        <div class="label">Tipo</div>
        <select class="input" name="tipo">
          <option value="POR_VENTA" <?=$tipo==='POR_VENTA'?'selected':''?>>POR_VENTA</option>
          <option value="POR_COBRO" <?=$tipo==='POR_COBRO'?'selected':''?>>POR_COBRO</option>
        </select>
      </div>
      <div style="grid-column:span 3">
        <div class="label">Estado</div>
        <select class="input" name="estado">
          <option value="1" <?=$estado===1?'selected':''?>>Activa</option>
          <option value="0" <?=$estado===0?'selected':''?>>Inactiva</option>
        </select>
      </div>

      <div style="grid-column:span 12" class="notice small">
        Reglas sugeridas: una global por venta (por ejemplo 2%) + reglas especiales por producto (ej: 5%) o por vendedor.
      </div>
    </form>
  </div>
</div></div>
</body></html>
