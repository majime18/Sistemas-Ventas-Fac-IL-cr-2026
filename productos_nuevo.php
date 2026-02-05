<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id = (int)$_SESSION['empresa_id'];

$imp = $pdo->prepare("SELECT id,nombre,porcentaje FROM impuestos WHERE empresa_id=? OR empresa_id IS NULL ORDER BY porcentaje ASC");
$imp->execute([$empresa_id]);
$impuestos = $imp->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$err=''; $ok='';
$r = [];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $codigo = trim($_POST['codigo'] ?? '');
  $codigo_barras = trim($_POST['codigo_barras'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $categoria = trim($_POST['categoria'] ?? '');
  $cabys = trim($_POST['cabys'] ?? '');
  $unidad = trim($_POST['unidad'] ?? '');
  $costo = (float)($_POST['costo'] ?? 0);
  $margen = (float)($_POST['margen'] ?? 0);
  $margen_minimo = (float)($_POST['margen_minimo'] ?? 0);
  $precio = (float)($_POST['precio'] ?? 0);
  $impuesto_id = ($_POST['impuesto_id'] ?? '')==='' ? null : (int)$_POST['impuesto_id'];
  $estado = (int)($_POST['estado'] ?? 1);

  if($codigo==='') $err='Código requerido.';
  if($descripcion==='') $err='Descripción requerida.';

if(!$err){
  // Si precio viene vacío, calcular automáticamente
  if($precio<=0 && $costo>0 && $margen>0){ $precio = round($costo*(1+($margen/100)),2); }
  $st = $pdo->prepare("INSERT INTO productos (empresa_id,codigo,codigo_barras,descripcion,categoria,cabys,unidad,costo,margen,margen_minimo,precio,impuesto_id,estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
  $st->execute([$empresa_id,$codigo,$codigo_barras===''?null:$codigo_barras,$descripcion,$categoria===''?null:$categoria,$cabys===''?null:$cabys,$unidad===''?null:$unidad,$costo,$margen,$margen_minimo,$precio,$impuesto_id,$estado]);
  header("Location: productos.php"); exit;
}

}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuevo producto | FAC-IL-CR</title>
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
.wrap{max-width:1100px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.input, select{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus, select:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
@media(max-width:900px){.grid3{grid-template-columns:1fr 1fr} .grid2{grid-template-columns:1fr}}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);margin-bottom:10px}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.notice.ok{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.35)}
.small{font-size:12px;color:var(--muted)}
</style>
<script>
function calcPrecio(){
  const costo = parseFloat(document.getElementById('costo').value||'0');
  const margen = parseFloat(document.getElementById('margen').value||'0');
  if(costo>0 && margen>0){
    const p = (costo*(1+(margen/100))).toFixed(2);
    const precio = document.getElementById('precio');
    if(!precio.value || parseFloat(precio.value||'0')<=0){ precio.value = p; }
  }
}
</script>

</head>
<body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Productos • Nuevo</span></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="productos.php">← Volver</a>
    <button class="btn primary" form="f" type="submit">💾 Guardar</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Nuevo producto</div>
        <div class="small">CABYS + impuesto + márgenes. Precio puede calcularse automáticamente.</div>
      </div>
    </div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?=h($err)?></div><?php endif; ?>
      <?php if($ok): ?><div class="notice ok"><?=h($ok)?></div><?php endif; ?>
      <form id="f" method="post">
        <div class="grid3">
          <div>
            <div class="label">Código</div>
            <input class="input" name="codigo" value="<?=h($r['codigo'] ?? '')?>" required>
          </div>
          <div>
            <div class="label">Código de barras</div>
            <input class="input" name="codigo_barras" value="<?=h($r['codigo_barras'] ?? '')?>" placeholder="Opcional">
          </div>
          <div>
            <div class="label">Categoría</div>
            <input class="input" name="categoria" value="<?=h($r['categoria'] ?? '')?>" placeholder="Ej: Abarrotes">
          </div>
        </div>

        <div class="label">Descripción</div>
        <input class="input" name="descripcion" value="<?=h($r['descripcion'] ?? '')?>" required>

        <div class="grid3" style="margin-top:6px">
          <div>
            <div class="label">CABYS</div>
            <input class="input" name="cabys" value="<?=h($r['cabys'] ?? '')?>" placeholder="Opcional">
          </div>
          <div>
            <div class="label">Unidad</div>
            <input class="input" name="unidad" value="<?=h($r['unidad'] ?? '')?>" placeholder="Ej: Unid, kg">
          </div>
          <div>
            <div class="label">Impuesto</div>
            <select class="input" name="impuesto_id">
              <option value="">— IVA por defecto (13%) —</option>
              <?php foreach($impuestos as $i): ?>
                <option value="<?=$i['id']?>" <?=(isset($r['impuesto_id']) && (int)$r['impuesto_id']==(int)$i['id'])?'selected':''?>>
                  <?=h($i['nombre'])?> (<?=number_format((float)$i['porcentaje'],2,'.','')?>%)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid3" style="margin-top:6px">
          <div>
            <div class="label">Costo</div>
            <input class="input" id="costo" name="costo" type="number" step="0.01" value="<?=h($r['costo'] ?? 0)?>" oninput="calcPrecio()">
          </div>
          <div>
            <div class="label">Margen %</div>
            <input class="input" id="margen" name="margen" type="number" step="0.001" value="<?=h($r['margen'] ?? 0)?>" oninput="calcPrecio()">
          </div>
          <div>
            <div class="label">Margen mínimo %</div>
            <input class="input" name="margen_minimo" type="number" step="0.001" value="<?=h($r['margen_minimo'] ?? 0)?>">
          </div>
        </div>

        <div class="grid2" style="margin-top:6px">
          <div>
            <div class="label">Precio</div>
            <input class="input" id="precio" name="precio" type="number" step="0.01" value="<?=h($r['precio'] ?? 0)?>" placeholder="Si está vacío se calcula por margen">
          </div>
          <div>
            <div class="label">Estado</div>
            <select class="input" name="estado">
              <option value="1" <?=(isset($r['estado']) && (int)$r['estado']===1)?'selected':''?>>Activo</option>
              <option value="0" <?=(isset($r['estado']) && (int)$r['estado']===0)?'selected':''?>>Inactivo</option>
            </select>
          </div>
        </div>

        <div class="notice" style="margin-top:12px">
          <b>Inventario por bodega:</b>
          <div class="small" style="margin-top:6px">
            El producto NO guarda bodega. La existencia se maneja en <code>inventario_existencias</code> por cada bodega.
          </div>
        </div>
      </form>

    </div>
  </div>
</div>
</body>
</html>
