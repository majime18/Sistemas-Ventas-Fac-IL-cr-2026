<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
$usuario_id=(int)($_SESSION['usuario_id'] ?? 0);
require_once "cxc_helpers.php";

$vence_col = col_exists($pdo,'cxc_documentos','vencimiento') ? 'vencimiento' : (col_exists($pdo,'cxc_documentos','vence') ? 'vence' : 'vence');

$cxc_id = (int)($_GET['cxc_id'] ?? $_POST['cxc_id'] ?? 0);
$err='';
$ok='';

// Carga documento si viene específico
$doc=null;
if($cxc_id>0){
  $st=$pdo->prepare("SELECT d.*, c.nombre cliente, c.identificacion FROM cxc_documentos d JOIN clientes c ON c.id=d.cliente_id WHERE d.id=? AND d.empresa_id=? LIMIT 1");
  $st->execute([$cxc_id,$empresa_id]);
  $doc=$st->fetch(PDO::FETCH_ASSOC);
  if(!$doc){ header("Location: cxc.php"); exit; }
}

$metodo = $_POST['metodo_pago'] ?? 'EFECTIVO';
$referencia = trim($_POST['referencia_pago'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

if($_SERVER['REQUEST_METHOD']==='POST'){
  $mode = $_POST['mode'] ?? 'SINGLE';

  if($mode==='SINGLE'){
    $monto = (float)($_POST['monto_abono'] ?? 0);
    if(!$doc) $err="Documento no encontrado.";
    if(!$err && $monto<=0) $err="Monto debe ser mayor a 0.";
    if(!$err && $monto > (float)$doc['saldo']+0.00001) $err="Monto supera el saldo del documento.";

    if(!$err){
      $pdo->beginTransaction();
      try{
        $ins=$pdo->prepare("INSERT INTO cxc_abonos (empresa_id,cxc_documento_id,venta_id,cliente_id,fecha_abono,monto_abono,metodo_pago,referencia_pago,usuario_id,observaciones,anulado,created_at)
                            VALUES (?,?,?,?,NOW(),?,?,?,?,?,0,NOW())");
        $ins->execute([
          $empresa_id,
          (int)$doc['id'],
          $doc['venta_id']? (int)$doc['venta_id'] : null,
          (int)$doc['cliente_id'],
          $monto,
          $metodo,
          ($referencia===''?null:$referencia),
          $usuario_id,
          ($observaciones===''?null:$observaciones),
        ]);

        $antes = ['saldo'=>$doc['saldo'],'estado'=>$doc['estado']];
        $up=$pdo->prepare("UPDATE cxc_documentos SET saldo = GREATEST(saldo - ?, 0) WHERE id=? AND empresa_id=?");
        $up->execute([$monto,(int)$doc['id'],$empresa_id]);

        cxc_recalc($pdo,$empresa_id,(int)$doc['id'],$vence_col);

        $st2=$pdo->prepare("SELECT saldo, estado FROM cxc_documentos WHERE id=? AND empresa_id=?");
        $st2->execute([(int)$doc['id'],$empresa_id]);
        $after=$st2->fetch(PDO::FETCH_ASSOC) ?: null;

        audit_log($pdo,$empresa_id,$usuario_id,'CXC','ABONO','cxc_abonos',(int)$pdo->lastInsertId(),null,[
          'cxc_documento_id'=>(int)$doc['id'],'monto'=>$monto,'metodo'=>$metodo,'referencia'=>$referencia,'saldo_antes'=>$antes,'saldo_despues'=>$after
        ]);

        $pdo->commit();
        header("Location: cxc_detalle.php?id=".$doc['id']); exit;
      }catch(Throwable $e){
        $pdo->rollBack();
        $err="Error al registrar: ".$e->getMessage();
      }
    }
  } else {
    // MULTI: pago a múltiples documentos del mismo cliente
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $ids = $_POST['doc_id'] ?? [];
    $aplica = $_POST['aplica'] ?? []; // montos

    if($cliente_id<=0) $err="Seleccione cliente.";
    if(!$err && !is_array($ids)) $err="Docs inválidos.";

    // cargar docs
    if(!$err){
      $docs = [];
      $q = $pdo->prepare("SELECT id, venta_id, cliente_id, saldo, estado, $vence_col AS vence FROM cxc_documentos WHERE empresa_id=? AND cliente_id=? AND saldo>0 ORDER BY $vence_col ASC, id ASC");
      $q->execute([$empresa_id,$cliente_id]);
      $docs = $q->fetchAll(PDO::FETCH_ASSOC);

      // Modo MULTI: lógica de aplicación aún no implementada (solo UI).
      // Se cargan los documentos en $docs para mostrarlos en pantalla, pero no se procesan aquí.
    }
    // We'll keep multi in UI only for now to avoid partial. If submitted, show message.
    if(!$err) $err="Modo pago múltiple aún no está habilitado en esta versión (UI lista). Use abono por documento.";
  }
}

// Para selector de cliente en MULTI
$clientes = $pdo->query("SELECT id,nombre,identificacion FROM clientes ORDER BY nombre ASC LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrar abono | CXC</title>
<style>
:root{
  --azul:#0b5ed7; --azul-metal:#084298;
  --amarillo:#ffc107; --amarillo-metal:#ffca2c;
  --fondo:#071225; --card:rgba(17,24,39,.78);
  --borde:rgba(255,255,255,.12); --txt:#e5e7eb; --muted:#a7b0c2;
  --ok:#22c55e; --bad:#ef4444; --info:#38bdf8;
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
.btn.info{background:linear-gradient(180deg,rgba(56,189,248,.9),rgba(56,189,248,.45));border-color:rgba(56,189,248,.55);color:#082f49}
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
.tag.info{border-color:rgba(56,189,248,.55);background:rgba(56,189,248,.12)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
hr{border:none;border-top:1px solid rgba(255,255,255,.08);margin:12px 0}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">CXC • Abonos</span></div>
  <div class="actions">
    <a class="btn" href="<?= $doc ? 'cxc_detalle.php?id='.(int)$doc['id'] : 'cxc.php' ?>">← Volver</a>
    <button class="btn primary" form="f" type="submit">💾 Registrar</button>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Registrar abono</div>
        <div class="small">Actualiza saldo y estado (Pendiente/Vencido/Pagado). Todo queda auditado.</div>
      </div>
      <div class="actions">
        <span class="tag info"><?= $doc ? 'Documento #'.(int)$doc['id'] : 'Pago múltiple' ?></span>
      </div>
    </div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?=h($err)?></div><?php endif; ?>

      <form id="f" method="post">
        <?php if($doc): ?>
          <input type="hidden" name="mode" value="SINGLE">
          <input type="hidden" name="cxc_id" value="<?= (int)$doc['id'] ?>">

          <div class="notice">
            <b><?= h($doc['cliente']) ?></b> • Cédula: <span class="mono"><?= h($doc['identificacion'] ?? '') ?></span><br>
            Saldo actual: <b class="mono"><?= number_format((float)$doc['saldo'],2,',','.') ?></b>
          </div>

          <div class="grid" style="margin-top:10px">
            <div style="grid-column:span 3">
              <div class="label">Monto a abonar</div>
              <input class="input mono" type="number" step="0.01" min="0" name="monto_abono" value="0" required>
            </div>
            <div style="grid-column:span 3">
              <div class="label">Método</div>
              <select class="input" name="metodo_pago">
                <?php foreach(['EFECTIVO','TARJETA','TRANSFERENCIA','SINPE','CHEQUE','OTRO'] as $m): ?>
                  <option value="<?=$m?>" <?=$metodo===$m?'selected':''?>><?=$m?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="grid-column:span 6">
              <div class="label">Referencia</div>
              <input class="input mono" name="referencia_pago" value="<?=h($referencia)?>" placeholder="Opcional">
            </div>
            <div style="grid-column:span 12">
              <div class="label">Observaciones</div>
              <textarea class="input" name="observaciones" placeholder="Opcional"><?=h($observaciones)?></textarea>
            </div>
          </div>

        <?php else: ?>
          <input type="hidden" name="mode" value="MULTI">

          <div class="notice err">
            <b>Nota:</b> El pago múltiple (distribuir un monto a varios documentos) se habilita en el siguiente ajuste.
            Por ahora, registrá abono desde cada documento.
          </div>

          <div class="grid" style="margin-top:10px">
            <div style="grid-column:span 6">
              <div class="label">Cliente</div>
              <select class="input" name="cliente_id">
                <option value="0">— Seleccione —</option>
                <?php foreach($clientes as $c): ?>
                  <option value="<?=$c['id']?>"><?=h($c['nombre'])?> (<?=h($c['identificacion'] ?? '')?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="grid-column:span 3">
              <div class="label">Monto total</div>
              <input class="input mono" type="number" step="0.01" min="0" name="monto_total" value="0">
            </div>
            <div style="grid-column:span 3">
              <div class="label">Método</div>
              <select class="input" name="metodo_pago">
                <?php foreach(['EFECTIVO','TARJETA','TRANSFERENCIA','SINPE','CHEQUE','OTRO'] as $m): ?>
                  <option value="<?=$m?>"><?=$m?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
</body></html>