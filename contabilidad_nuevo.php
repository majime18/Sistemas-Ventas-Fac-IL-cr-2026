<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$usuario_id=(int)($_SESSION['usuario_id'] ?? 0);
require_once "contabilidad_helpers.php";

$err='';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$referencia = $_POST['referencia'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $fecha = $_POST['fecha'] ?? date('Y-m-d');
  $referencia = trim($_POST['referencia'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');

  $cuenta_id = $_POST['cuenta_id'] ?? [];
  $linea_desc = $_POST['linea_desc'] ?? [];
  $debito = $_POST['debito'] ?? [];
  $credito = $_POST['credito'] ?? [];

  $per = periodo_get_or_create($pdo,$empresa_id,$fecha);
  if(($per['estado'] ?? 'ABIERTO')!=='ABIERTO') $err = "Periodo cerrado: no se permite registrar asientos en $fecha.";
  if(!$err && $descripcion==='') $err = "Descripción requerida.";

  $lines=[];
  $sumD=0; $sumC=0;
  for($i=0;$i<count($cuenta_id);$i++){
    $cid = (int)($cuenta_id[$i] ?? 0);
    $ld  = trim($linea_desc[$i] ?? '');
    $d   = (float)($debito[$i] ?? 0);
    $c   = (float)($credito[$i] ?? 0);
    if($cid<=0) continue;
    if($d<=0 && $c<=0) continue;
    if($d>0 && $c>0){ $err="Una línea no puede tener Debe y Haber a la vez."; break; }
    $sumD += $d; $sumC += $c;
    $lines[]=['cuenta_id'=>$cid,'descripcion'=>$ld,'debito'=>$d,'credito'=>$c];
  }
  if(!$err && count($lines)<2) $err="Debe ingresar al menos 2 líneas contables.";
  if(!$err && round($sumD,2)!==round($sumC,2)) $err="Asiento desbalanceado. Debe (".number_format($sumD,2,'.','').") != Haber (".number_format($sumC,2,'.','').").";

  if(!$err){
    $pdo->beginTransaction();
    try{
      $ins=$pdo->prepare("INSERT INTO cont_asientos (empresa_id,fecha,periodo_id,referencia,descripcion,origen,origen_id,anulado,created_at)
                          VALUES (?,?,?,?,?,?,?,0,NOW())");
      $ins->execute([$empresa_id,$fecha,(int)$per['id'],$referencia,$descripcion,'MANUAL',null]);
      $asiento_id = (int)$pdo->lastInsertId();

      $insd=$pdo->prepare("INSERT INTO cont_asientos_detalle (asiento_id,cuenta_id,descripcion,debito,credito) VALUES (?,?,?,?,?)");
      foreach($lines as $ln){
        $insd->execute([$asiento_id,$ln['cuenta_id'],$ln['descripcion'],$ln['debito'],$ln['credito']]);
      }

      audit_log($pdo,$empresa_id,$usuario_id,'Contabilidad','CREAR','cont_asientos',$asiento_id,null,[
        'fecha'=>$fecha,'referencia'=>$referencia,'descripcion'=>$descripcion,'debe'=>$sumD,'haber'=>$sumC,'lineas'=>$lines
      ]);

      $pdo->commit();
      header("Location: contabilidad_ver.php?id=".$asiento_id); exit;
    } catch(Throwable $e){
      $pdo->rollBack();
      $err = "Error al guardar: ".$e->getMessage();
    }
  }
}
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuevo asiento | Contabilidad</title>
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
.wrap{max-width:1500px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr));}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
.input, select, textarea{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
textarea{min-height:90px;resize:vertical}
.input:focus, select:focus, textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
@media(max-width:1100px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:640px){.kpis{grid-template-columns:1fr;}}
.kpi{padding:14px;border-radius:18px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.kpi .t{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.10em}
.kpi .n{font-size:24px;font-weight:1000;margin-top:6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.suggest{position:relative}
.slist{
  position:absolute;left:0;right:0;top:100%;margin-top:6px;z-index:80;
  border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden;
  background:rgba(2,6,23,.92);backdrop-filter: blur(12px);display:none;max-height:320px;overflow:auto;
}
.sitem{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.sitem:hover{background:rgba(255,255,255,.06)}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Asiento • Nuevo</span></div>
  <div class="actions">
    <a class="btn" href="contabilidad.php">← Contabilidad</a>
    <button class="btn primary" form="f" type="submit">💾 Guardar</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Asiento contable</div>
        <div class="small">Debe = Haber • Periodos cerrados bloquean movimientos.</div>
      </div>
      <div class="small">Origen: MANUAL</div>
    </div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?=h($err)?></div><?php endif; ?>

      <form id="f" method="post">
        <div class="grid">
          <div style="grid-column:span 2">
            <div class="label">Fecha</div>
            <input class="input" type="date" name="fecha" value="<?=h($fecha)?>" required>
          </div>
          <div style="grid-column:span 3">
            <div class="label">Referencia</div>
            <input class="input" name="referencia" value="<?=h($referencia)?>" placeholder="Ej: AJ-0001">
          </div>
          <div style="grid-column:span 7">
            <div class="label">Descripción</div>
            <input class="input" name="descripcion" value="<?=h($descripcion)?>" placeholder="Asiento por ..." required>
          </div>
        </div>

        <div class="notice" style="margin-top:12px">
          <b>Partidas</b>
          <div class="small">Buscá cuentas por código/nombre, agregá líneas y revisá el balance.</div>
        </div>

        <div style="margin-top:10px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
          <table class="table" id="t">
            <thead><tr>
              <th style="min-width:340px">Cuenta</th>
              <th style="min-width:280px">Detalle</th>
              <th class="right" style="min-width:140px">Debe</th>
              <th class="right" style="min-width:140px">Haber</th>
              <th class="right" style="min-width:80px">#</th>
            </tr></thead>
            <tbody id="tb"></tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="right"><b>Totales</b></td>
                <td class="right mono" id="sumD">0.00</td>
                <td class="right mono" id="sumC">0.00</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="actions" style="margin-top:10px">
          <button class="btn warn" type="button" id="add">➕ Agregar línea</button>
          <span class="tag" id="bal">Balance: 0.00</span>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const tb = document.getElementById('tb');
const addBtn = document.getElementById('add');
const sumD = document.getElementById('sumD');
const sumC = document.getElementById('sumC');
const bal = document.getElementById('bal');

function fmt(n){ return (Math.round(n*100)/100).toFixed(2); }

function recalc(){
  let d=0,c=0;
  tb.querySelectorAll('tr').forEach(tr=>{
    const deb = parseFloat(tr.querySelector('input[name="debito[]"]').value||0);
    const cre = parseFloat(tr.querySelector('input[name="credito[]"]').value||0);
    d += deb; c += cre;
  });
  sumD.textContent = fmt(d);
  sumC.textContent = fmt(c);
  const b = d - c;
  bal.textContent = "Balance: " + fmt(b);
  bal.className = (Math.abs(b) < 0.005) ? "tag ok" : "tag warn";
}

async function searchAccounts(q){
  const r = await fetch('contabilidad_cuentas_buscar.php?q='+encodeURIComponent(q));
  if(!r.ok) return [];
  const j = await r.json();
  return j.items || [];
}

function rowTemplate(){
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <div class="suggest">
        <input class="input mono acc_q" placeholder="Buscar cuenta..." autocomplete="off">
        <div class="slist"></div>
      </div>
      <input type="hidden" name="cuenta_id[]" value="0">
      <div class="small acc_info"></div>
    </td>
    <td><input class="input" name="linea_desc[]" placeholder="Detalle de la línea"></td>
    <td class="right"><input class="input mono" name="debito[]" type="number" step="0.01" min="0" value="0"></td>
    <td class="right"><input class="input mono" name="credito[]" type="number" step="0.01" min="0" value="0"></td>
    <td class="right"><button class="btn bad" type="button">✖</button></td>
  `;
  const q = tr.querySelector('.acc_q');
  const sl = tr.querySelector('.slist');
  const hid = tr.querySelector('input[name="cuenta_id[]"]');
  const info = tr.querySelector('.acc_info');
  let t=null, items=[];

  q.addEventListener('input', ()=>{
    clearTimeout(t);
    t=setTimeout(async ()=>{
      const term=(q.value||'').trim();
      if(term.length<2){ sl.style.display='none'; sl.innerHTML=''; return; }
      items = await searchAccounts(term);
      if(items.length===0){ sl.style.display='none'; sl.innerHTML=''; return; }
      sl.innerHTML = items.map(a=>`
        <div class="sitem" data-id="${a.id}">
          <div><b class="mono">${a.codigo}</b> — ${a.nombre}<div class="small">${a.tipo}</div></div>
          <div class="pill">OK</div>
        </div>
      `).join('');
      sl.style.display='block';
      sl.querySelectorAll('.sitem').forEach(el=>{
        el.addEventListener('click', ()=>{
          const id=el.getAttribute('data-id');
          const a=items.find(x=>String(x.id)===String(id));
          if(!a) return;
          hid.value = a.id;
          info.textContent = "Seleccionada: "+a.codigo+" — "+a.nombre;
          q.value='';
          sl.style.display='none';
        });
      });
    }, 150);
  });
  document.addEventListener('click', (e)=>{ if(!e.target.closest('.suggest')) sl.style.display='none'; });

  tr.querySelector('input[name="debito[]"]').addEventListener('input', ()=>{
    if(parseFloat(tr.querySelector('input[name="debito[]"]').value||0)>0){
      tr.querySelector('input[name="credito[]"]').value = 0;
    }
    recalc();
  });
  tr.querySelector('input[name="credito[]"]').addEventListener('input', ()=>{
    if(parseFloat(tr.querySelector('input[name="credito[]"]').value||0)>0){
      tr.querySelector('input[name="debito[]"]').value = 0;
    }
    recalc();
  });
  tr.querySelector('button.btn.bad').addEventListener('click', ()=>{ tr.remove(); recalc(); });

  return tr;
}

addBtn.addEventListener('click', ()=> tb.appendChild(rowTemplate()));

// iniciar con 2 filas
tb.appendChild(rowTemplate());
tb.appendChild(rowTemplate());
recalc();
</script>
</body></html>
