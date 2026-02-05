<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)$_SESSION['empresa_id'];
$usuario_id=(int)$_SESSION['usuario_id'];

$pref_bodega=(int)($_GET['bodega_id']??0);
$pref_producto=(int)($_GET['producto_id']??0);

$st=$pdo->prepare("SELECT id,nombre FROM bodegas WHERE empresa_id=? AND estado=1 ORDER BY nombre");
$st->execute([$empresa_id]);
$bods=$st->fetchAll(PDO::FETCH_ASSOC);

$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $bodega_id=(int)($_POST['bodega_id']??0);
  $producto_id=(int)($_POST['producto_id']??0);
  $tipo=$_POST['tipo']??'AJUSTE'; // ENTRADA/SALIDA/AJUSTE
  $cantidad=(float)($_POST['cantidad']??0);
  $referencia=trim($_POST['referencia']??'');
  $stock_minimo = ($_POST['stock_minimo'] ?? '')!=='' ? (float)$_POST['stock_minimo'] : null;

  if($bodega_id<=0) $err='Seleccioná bodega.';
  if($producto_id<=0) $err='Seleccioná producto.';
  if($cantidad<=0) $err='Cantidad debe ser mayor a 0.';

  if(!$err){
    $pdo->beginTransaction();
    try{
      $st=$pdo->prepare("SELECT existencia, COALESCE(stock_minimo,0) stock_minimo
                         FROM inventario_existencias
                         WHERE empresa_id=? AND bodega_id=? AND producto_id=? LIMIT 1");
      $st->execute([$empresa_id,$bodega_id,$producto_id]);
      $row=$st->fetch(PDO::FETCH_ASSOC);
      $exist = $row ? (float)$row['existencia'] : 0.0;
      $min = $row ? (float)$row['stock_minimo'] : 0.0;

      if($tipo==='ENTRADA') $nuevo = $exist + $cantidad;
      else if($tipo==='SALIDA') $nuevo = $exist - $cantidad;
      else {
        // AJUSTE: cantidad es el valor final deseado
        $nuevo = $cantidad;
        $cantidad = $nuevo - $exist; // movimiento real (puede ser negativo)
      }

      if($nuevo < 0) throw new Exception("Bloqueo: no se permite stock negativo. Existencia actual: ".$exist);

      if(!$row){
        $ins=$pdo->prepare("INSERT INTO inventario_existencias (empresa_id,bodega_id,producto_id,existencia,stock_minimo) VALUES (?,?,?,?,?)");
        $ins->execute([$empresa_id,$bodega_id,$producto_id,$nuevo, ($stock_minimo===null?0:$stock_minimo)]);
      } else {
        $upd=$pdo->prepare("UPDATE inventario_existencias
                            SET existencia=?, stock_minimo=?
                            WHERE empresa_id=? AND bodega_id=? AND producto_id=?");
        $upd->execute([$nuevo, ($stock_minimo===null?$min:$stock_minimo), $empresa_id,$bodega_id,$producto_id]);
      }

      // movimiento
      $movTipo = ($cantidad>=0) ? 'ENTRADA' : 'SALIDA';
      $movCant = abs($cantidad);
      $ref = ($referencia!=='') ? $referencia : ('AJUSTE '.$tipo);

      $im=$pdo->prepare("INSERT INTO inventario_movimientos
        (empresa_id,bodega_id,producto_id,tipo,cantidad,costo_unitario,referencia_tipo,referencia_id,motivo,usuario_id)
        VALUES (?,?,?,?,?,0,?,?,?,?)");
      $im->execute([$empresa_id,$bodega_id,$producto_id,$movTipo,$movCant,'MANUAL',null,$ref,$usuario_id]);

      $pdo->commit();
      header("Location: inventario.php?bodega_id=".$bodega_id);
      exit;
    }catch(Throwable $e){
      $pdo->rollBack();
      $err="Error: ".$e->getMessage();
    }
  }
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Movimiento / Ajuste | FAC-IL-CR</title>
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Inventario • Movimiento</span></div>
  <div class="actions">
    <a class="btn" href="inventario.php">← Volver</a>
    <button class="btn primary" form="f" type="submit">💾 Guardar</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd"><div style="font-weight:1000;font-size:18px">Movimiento / Ajuste</div></div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?=h($err)?></div><?php endif; ?>

      <form id="f" method="post" style="max-width:900px">
        <div class="grid" style="grid-template-columns:1fr 1.3fr 1fr 1fr">
          <div>
            <div class="label">Bodega</div>
            <select class="input" name="bodega_id" id="bodega_id" required>
              <option value="0">— Seleccionar —</option>
              <?php foreach($bods as $b): ?>
                <option value="<?=$b['id']?>" <?=($pref_bodega==(int)$b['id'])?'selected':''?>><?=h($b['nombre'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <div class="label">Producto (buscar)</div>
            <div class="suggest">
              <input class="input" id="busq" placeholder="Código, barras o nombre..." autocomplete="off">
              <div id="slist" class="slist"></div>
            </div>
            <input type="hidden" name="producto_id" id="producto_id" value="<?=$pref_producto?>">
            <div class="small" id="pinfo"></div>
          </div>

          <div>
            <div class="label">Tipo</div>
            <select class="input" name="tipo" id="tipo">
              <option value="AJUSTE">AJUSTE (poner cantidad final)</option>
              <option value="ENTRADA">ENTRADA (sumar)</option>
              <option value="SALIDA">SALIDA (restar)</option>
            </select>
          </div>

          <div>
            <div class="label">Cantidad</div>
            <input class="input" name="cantidad" type="number" step="0.001" min="0.001" required>
          </div>
        </div>

        <div class="grid" style="grid-template-columns:1fr 1fr; margin-top:10px">
          <div>
            <div class="label">Stock mínimo (opcional)</div>
            <input class="input" name="stock_minimo" type="number" step="0.001" min="0" placeholder="Ej: 5">
          </div>
          <div>
            <div class="label">Referencia (opcional)</div>
            <input class="input" name="referencia" placeholder="Compra, conteo, ajuste, etc.">
          </div>
        </div>

        <div class="notice" style="margin-top:12px">
          <b>Reglas:</b>
          <div class="small" style="margin-top:6px">
            • Bloqueo de stock negativo.<br>
            • Cada movimiento se registra en <code>inventario_movimientos</code>.<br>
            • Si no existe (bodega + producto) se crea automáticamente.
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const busq = document.getElementById('busq');
const slist = document.getElementById('slist');
const pid = document.getElementById('producto_id');
const pinfo = document.getElementById('pinfo');

let items = [];
let t = null;

async function search(){
  const q = (busq.value||'').trim();
  if(q.length < 2){ slist.style.display='none'; slist.innerHTML=''; return; }
  const r = await fetch('inventario_productos_buscar.php?q=' + encodeURIComponent(q));
  if(!r.ok) return;
  const j = await r.json();
  items = j.items || [];
  if(items.length === 0){ slist.style.display='none'; slist.innerHTML=''; return; }
  slist.innerHTML = items.map(p => `
    <div class="sitem" data-id="${p.id}">
      <div>
        <div style="font-weight:1000">${p.codigo} — ${p.descripcion}</div>
        <div class="small">Barras: ${p.codigo_barras||'-'} • CABYS: ${p.cabys||'-'}</div>
      </div>
      <div class="pill">Seleccionar</div>
    </div>
  `).join('');
  slist.style.display = 'block';

  Array.from(slist.querySelectorAll('.sitem')).forEach(el => {
    el.addEventListener('click', () => {
      const id = el.getAttribute('data-id');
      const p = items.find(x => String(x.id) === String(id));
      if(p){
        pid.value = p.id;
        pinfo.textContent = 'Seleccionado: ' + p.codigo + ' — ' + p.descripcion;
        busq.value = '';
        slist.style.display = 'none';
      }
    });
  });
}

busq.addEventListener('input', () => { clearTimeout(t); t = setTimeout(search, 150); });
document.addEventListener('click', (e) => { if(!e.target.closest('.suggest')) slist.style.display='none'; });

if(pid.value && Number(pid.value) > 0){
  pinfo.textContent = 'Producto preseleccionado (ID ' + pid.value + ').';
}
</script>
</body></html>
