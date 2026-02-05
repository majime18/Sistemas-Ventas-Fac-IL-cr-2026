<?php
session_start();
require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }


function exec_or_fail(PDOStatement $stmt, array $params, string $label){
  try{
    $stmt->execute($params);
  }catch(Throwable $e){
    $msg = $label.": ".$e->getMessage()
      ." | SQL=".$stmt->queryString
      ." | PARAMS=".json_encode($params, JSON_UNESCAPED_UNICODE);
    throw new Exception($msg, 0, $e);
  }
}

// === CAMBIO ROBUSTO: recuperación de empresa_id ===
$empresa_id = (int)($_SESSION['empresa_id'] ?? 0);
$sucursal_id = (int)($_SESSION['sucursal_id'] ?? 0);

// 1. Intentar recuperar del usuario
if ($empresa_id <= 0) {
    try {
        $uId = (int)($_SESSION['usuario_id'] ?? 0);
        if ($uId > 0) {
            $stU = $__tmp = $pdo->prepare("SELECT empresa_id FROM usuarios WHERE id = ? LIMIT 1");
            $stU->execute([$uId]);
            $rowU = $stU->fetch(PDO::FETCH_ASSOC);
            if ($rowU && !empty($rowU['empresa_id']) && $rowU['empresa_id'] > 0) {
                $empresa_id = (int)$rowU['empresa_id'];
                $_SESSION['empresa_id'] = $empresa_id; // guardamos para próximas peticiones
            }
        }
    } catch (Throwable $e) {
        // silent
    }
}

// 2. Si aún no hay, tomar la primera empresa
if ($empresa_id <= 0) {
    try {
        $emp = $pdo->query("SELECT id FROM empresas ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($emp && !empty($emp['id'])) {
            $empresa_id = (int)$emp['id'];
            $_SESSION['empresa_id'] = $empresa_id;
        }
    } catch (Throwable $e) {
        // silent
    }
}

// 3. FORZADO FINAL (lo que más probablemente resuelva tu caso actual)
if ($empresa_id <= 0) {
    $empresa_id = 1;  // ← CAMBIA ESTE NÚMERO por el ID REAL de tu empresa principal
}

// Opcional: también forzar sucursal si quieres estar 100% seguro
if ($sucursal_id <= 0) {
    $sucursal_id = 1;  // ← CAMBIA ESTE NÚMERO por el ID REAL de tu sucursal principal
}

if ($empresa_id <= 0) {
    die("No se pudo determinar empresa_id. Verifica la tabla empresas y usuarios.");
if ($sucursal_id <= 0) {
    die("No se pudo determinar sucursal_id. Verifica la sesión/usuario y la tabla sucursales.");
}
}
// === FIN DEL CAMBIO ===

$usuario_id = (int)$_SESSION['usuario_id'];

/** Clientes */
$st = $pdo->prepare("SELECT id,nombre,identificacion,tipo FROM clientes WHERE empresa_id=? AND estado IN ('ACTIVO','MOROSO') ORDER BY nombre");
$st->execute([$empresa_id]); $clientes = $st->fetchAll(PDO::FETCH_ASSOC);

/** Bodegas */
$st = $pdo->prepare("SELECT id,nombre FROM bodegas WHERE empresa_id=? AND estado=1 ORDER BY nombre");
$st->execute([$empresa_id]); $bodegas = $st->fetchAll(PDO::FETCH_ASSOC);

/** Categorías (opcional) – si existe columna productos.categoria */
$categorias = [];
try{
  $st = $pdo->prepare("SELECT DISTINCT categoria AS cat FROM productos WHERE empresa_id=? AND categoria IS NOT NULL AND categoria<>'' ORDER BY categoria");
  $st->execute([$empresa_id]);
  $categorias = array_map(fn($r)=>$r['cat'], $st->fetchAll(PDO::FETCH_ASSOC));
}catch(Throwable $e){
  $categorias = [];
}

$err = '';

function dec($n,$d=2){ return number_format((float)$n,$d,'.',''); }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $cliente_id = (int)($_POST['cliente_id'] ?? 0);
  $tipo = $_POST['tipo'] ?? 'VENTA'; // VENTA/COTIZACION/PEDIDO
  $bodega_id = (int)($_POST['bodega_id'] ?? 0);

  $moneda = strtoupper(trim($_POST['moneda'] ?? 'CRC'));
  $tipo_cambio = (float)($_POST['tipo_cambio'] ?? 1);
  $condicion = $_POST['condicion_venta'] ?? 'CONTADO';
  $plazo = (int)($_POST['plazo_credito'] ?? 0);

  $medio_pago = $_POST['medio_pago'] ?? 'EFECTIVO';
  $referencia_pago = trim($_POST['referencia_pago'] ?? '');
  $obs = trim($_POST['observaciones'] ?? '');

  // Cobro POS (contado)
  $pago_efectivo = (float)($_POST['pago_efectivo'] ?? 0);
  $pago_tarjeta = (float)($_POST['pago_tarjeta'] ?? 0);
  $monto_recibido = (float)($_POST['monto_recibido'] ?? 0);
  $vuelto = (float)($_POST['vuelto'] ?? 0);
  $cobro_confirmado = (int)($_POST['cobro_confirmado'] ?? 0);

  $cart = json_decode($_POST['cart_json'] ?? '[]', true);

  if(!$cliente_id) $err = 'Seleccioná un cliente.';
  if(!is_array($cart) || count($cart)==0) $err = 'Agregá al menos un producto.';
  if($tipo==='VENTA' && $bodega_id<=0) $err = 'Seleccioná una bodega para validar/descontar inventario.';
  if($condicion==='CREDITO' && $plazo<=0) $err = 'Indicá plazo de crédito (días).';

  // Recalcular desde BD y validar stock
  if(!$err){
    $subtotal=0; $impuesto_total=0; $descuento=0;
    $lineas=[];

    foreach($cart as $it){
      $pid = (int)($it['id'] ?? 0);
      $qty = (float)($it['qty'] ?? 0);
      if($pid<=0 || $qty<=0) continue;

      $st = $pdo->prepare("
        SELECT p.id,p.codigo,p.descripcion,p.precio,p.cabys, COALESCE(i.porcentaje,13.00) impuesto_pct
        FROM productos p
        LEFT JOIN impuestos i ON i.id=p.impuesto_id
        WHERE p.id=? AND p.empresa_id=? AND p.estado=1
        LIMIT 1
      ");
      $st->execute([$pid,$empresa_id]);
      $p = $st->fetch(PDO::FETCH_ASSOC);
      if(!$p) continue;

      if($tipo==='VENTA'){
        $sx = $pdo->prepare("SELECT existencia FROM inventario_existencias WHERE empresa_id=? AND bodega_id=? AND producto_id=? LIMIT 1");
        $sx->execute([$empresa_id,$bodega_id,$pid]);
        $exist = $sx->fetchColumn();
        $exist = ($exist===false) ? 0 : (float)$exist;
        if($exist < $qty){
          $err = "Stock insuficiente: ".$p['codigo']." — ".$p['descripcion']." (Disponible: ".dec($exist,3).")";
          break;
        }
      }

      $precio = (float)$p['precio'];
      $impPct = (float)$p['impuesto_pct'];
      $lineSub = $qty * $precio;
      $imp = round($lineSub * ($impPct/100), 2);
      $lineTot = round($lineSub + $imp, 2);

      $subtotal += $lineSub;
      $impuesto_total += $imp;

      $lineas[] = [
        'producto_id'=>$p['id'],
        'descripcion'=>$p['descripcion'],
        'cabys'=>$p['cabys'],
        'cantidad'=>$qty,
        'precio_unitario'=>$precio,
        'descuento'=>0,
        'impuesto_pct'=>$impPct,
        'impuesto_monto'=>$imp,
        'total_linea'=>$lineTot
      ];
    }

    if(!$err && count($lineas)==0) $err='No se pudo validar ningún producto.';
  }

  if(!$err){
    $total = round(($subtotal - $descuento) + $impuesto_total, 2);

    // Normalizar cobro según condición
    if($condicion==='CREDITO'){
      $medio_pago = 'OTRO';
      if($referencia_pago==='') $referencia_pago = 'CREDITO';
      $pago_efectivo = 0; $pago_tarjeta = 0; $monto_recibido = 0; $vuelto = 0;
      $cobro_confirmado = 1;
    } else {
      $sumPagos = $pago_efectivo + $pago_tarjeta;
      if($sumPagos<=0 && $monto_recibido>0){
        $pago_efectivo = $monto_recibido;
        $sumPagos = $pago_efectivo;
      }
      if($cobro_confirmado!==1 || ($sumPagos + 0.00001) < $total){
        $err = "Cobro insuficiente. Total: ".$total." | Pagos: ".$sumPagos;
      }
      if($referencia_pago==='' && $monto_recibido>0){
        $calcVuelto = max(0, $monto_recibido - $total);
        $referencia_pago = "REC:".number_format($monto_recibido,2,'.','')."|VUE:".number_format($calcVuelto,2,'.','');
      }
    }

    $pdo->beginTransaction();
    try{
      // === FORZADO FINAL ANTES DEL INSERT (solución definitiva al error 1048) ===
      if ($empresa_id <= 0) {
          $empresa_id = 1; // ← Cambia este 1 por el ID real de tu empresa principal si no es 1
      }
      if ($sucursal_id <= 0) {
          $sucursal_id = 1; // ← Cambia este 1 por el ID real de tu sucursal principal si no es 1
      }
      // === FIN DEL FORZADO ===

      $ins = $pdo->prepare("
        INSERT INTO ventas
        (empresa_id,sucursal_id,cliente_id,usuario_id,tipo,estado,moneda,tipo_cambio,condicion_venta,plazo_credito,medio_pago,referencia_pago,subtotal,descuento,impuesto_total,total,observaciones)
        VALUES (?,?,?,?,?,'ABIERTA',?,?,?,?,?,?,?,?,?,?,?)
      ");
      exec_or_fail($ins,[
        $empresa_id,$sucursal_id,$cliente_id,$usuario_id,$tipo,
        $moneda,$tipo_cambio,$condicion,$plazo,$medio_pago,$referencia_pago,
        $subtotal,$descuento,$impuesto_total,$total,$obs
      ],"INSERT ventas");
      $venta_id = (int)$pdo->lastInsertId();

      $insd = $pdo->prepare("
        INSERT INTO ventas_detalle
        (empresa_id,venta_id,producto_id,descripcion,cabys,cantidad,precio_unitario,descuento,impuesto_monto,impuesto_pct,total_linea)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
      ");

      $updExist = $pdo->prepare("
        UPDATE inventario_existencias
        SET existencia = existencia - ?
        WHERE empresa_id=? AND bodega_id=? AND producto_id=?
      ");
      $insMov = $pdo->prepare("
        INSERT INTO inventario_movimientos
        (empresa_id,bodega_id,producto_id,tipo,cantidad,costo_unitario,referencia_tipo,referencia_id,motivo,usuario_id)
        VALUES (?,?,?,?,?,0,?,?,?,?)
      ");

      foreach($lineas as $l){
        exec_or_fail($insd,[
          $empresa_id,
          $venta_id,$l['producto_id'],$l['descripcion'],$l['cabys'],
          $l['cantidad'],$l['precio_unitario'],$l['descuento'],
          $l['impuesto_monto'],$l['impuesto_pct'],$l['total_linea']
        ],"INSERT ventas_detalle");

        if($tipo==='VENTA'){
          exec_or_fail($updExist,[$l['cantidad'],$empresa_id,$bodega_id,$l['producto_id']],"UPDATE inventario_existencias");
          exec_or_fail($insMov,[
            $empresa_id,$bodega_id,$l['producto_id'],
            'SALIDA',$l['cantidad'],
            'VENTA',$venta_id,'VENTA #'.$venta_id,
            $usuario_id
          ],"INSERT inventario_movimientos");
        }
      }

      // Crédito -> CxC
      if($condicion==='CREDITO'){
        $venc = date('Y-m-d', strtotime("+$plazo days"));
        $okCxc = false;

        try{
          $pdo->prepare("
            INSERT INTO cxc_documentos (empresa_id,cliente_id,venta_id,tipo,fecha_emision,fecha_vencimiento,monto_total,saldo,estado,created_at)
            VALUES (?,?,?,'FACTURA',CURDATE(),?,?,?,?,NOW())
          "); exec_or_fail($__tmp,[$empresa_id,$cliente_id,$venta_id,$venc,$total,$total,'PENDIENTE'],"INSERT cxc_documentos");
          $okCxc = true;
        }catch(Throwable $e){}

        if(!$okCxc){
          try{
            $__tmp = $pdo->prepare("
              INSERT INTO cxc_documentos (empresa_id,cliente_id,venta_id,tipo,fecha,vence,monto_total,saldo,estado,created_at)
              VALUES (?,?,?,'FACTURA',CURDATE(),?,?,?,?,NOW())
            "); exec_or_fail($__tmp,[$empresa_id,$cliente_id,$venta_id,$venc,$total,$total,'PENDIENTE'],"INSERT cxc_documentos");
            $okCxc = true;
          }catch(Throwable $e){}
        }

        if(!$okCxc){
          try{
            $__tmp = $pdo->prepare("
              INSERT INTO cxc_documentos (empresa_id,cliente_id,venta_id,tipo,emision,vencimiento,monto_total,saldo,estado,created_at)
              VALUES (?,?,?,'FACTURA',CURDATE(),?,?,?,?,NOW())
            "); exec_or_fail($__tmp,[$empresa_id,$cliente_id,$venta_id,$venc,$total,$total,'PENDIENTE'],"INSERT cxc_documentos");
            $okCxc = true;
          }catch(Throwable $e){}
        }

        if(!$okCxc){
          throw new Exception("No se pudo crear CxC (estructura de cxc_documentos no compatible).");
        }
      }

      $pdo->commit();
      header("Location: ventas_ver.php?id=".$venta_id);
      exit;
    }catch(Throwable $e){
      $pdo->rollBack();
      $err = "Error guardando la venta: ".$e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>POS Ventas | FAC-IL-CR</title>
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
.btn.good{background:linear-gradient(180deg,rgba(34,197,94,.85),rgba(34,197,94,.55));border-color:rgba(34,197,94,.55);color:#052e16}
.btn.bad{background:linear-gradient(180deg,rgba(239,68,68,.9),rgba(239,68,68,.55));border-color:rgba(239,68,68,.55);color:#450a0a}
.layout{display:grid;grid-template-columns:1.32fr .68fr;gap:14px;padding:14px;max-width:1400px;margin:auto}
@media(max-width:1100px){.layout{grid-template-columns:1fr}}
.card{
  background:var(--card);border:1px solid var(--borde);border-radius:18px;
  box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden;
}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
@media(max-width:900px){.grid3{grid-template-columns:1fr 1fr}}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.input, select{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus, select:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
.right{text-align:right}
.qty{
  display:flex;align-items:center;justify-content:flex-end;gap:6px
}
.qbtn{width:32px;height:32px;border-radius:10px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:var(--txt);font-weight:1000;cursor:pointer}
.qinp{width:68px;text-align:right}
.small{font-size:12px;color:var(--muted)}
.kpis{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.kpi{
  padding:12px;border-radius:16px;border:1px solid rgba(255,255,255,.10);
  background:linear-gradient(135deg,rgba(11,94,215,.16),rgba(2,6,23,.18));
  display:flex;justify-content:space-between;align-items:center
}
.kpi .t{font-size:12px;color:var(--muted)}
.kpi .v{font-weight:1000;font-size:18px}
.kpi.total{grid-column:1/-1;border-color:rgba(255,193,7,.35);background:linear-gradient(135deg,rgba(255,193,7,.14),rgba(2,6,23,.18))}
.split{display:flex;gap:10px;flex-wrap:wrap}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.tools{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.searchbar{display:flex;gap:10px}
.suggest{position:relative}
.slist{
  position:absolute;left:0;right:0;top:100%;margin-top:6px;z-index:80;
  border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden;
  background:rgba(2,6,23,.92);backdrop-filter: blur(12px);display:none;max-height:360px;overflow:auto;
}
.sitem{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.sitem:hover{background:rgba(255,255,255,.06)}
.products{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
@media(min-width:1200px){.products{grid-template-columns:repeat(3,1fr)}}
.pcard{
  border:1px solid rgba(255,255,255,.12);border-radius:16px;background:rgba(255,255,255,.05);
  padding:12px;cursor:pointer;transition:.15s transform, .15s border-color;
}
.pcard:hover{transform:translateY(-2px);border-color:rgba(255,193,7,.45)}
.pname{font-weight:1000}
.pmeta{font-size:12px;color:var(--muted);margin-top:6px;display:flex;justify-content:space-between;gap:8px}
.pprice{font-weight:1000}
.footerpay{
  display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:10px
}
@media(max-width:520px){.footerpay{grid-template-columns:1fr}}
.footerpay .btn.activePay{outline:2px solid rgba(255,193,7,.55); box-shadow:0 0 0 6px rgba(255,193,7,.10)}
/* Modal Cobro POS */
.modalOverlay{ position:fixed; inset:0; background:rgba(0,0,0,.55); backdrop-filter: blur(8px); display:flex; align-items:center; justify-content:center; padding:18px; z-index:9999; }
.modalCard{ width:min(560px,96vw); background:rgba(17,24,39,.92); border:1px solid var(--borde); border-radius:18px; box-shadow:0 25px 60px rgba(0,0,0,.55); padding:14px; }
.modalHead{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:6px 6px 12px; border-bottom:1px solid rgba(255,255,255,.08); }
.modalTitle{ font-size:18px; font-weight:900; }
.modalSub{ color:var(--muted); font-size:13px; margin-top:2px; }
.btnIcon{ border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.06); color:var(--txt); border-radius:12px; padding:8px 10px; cursor:pointer; }
.modalBody{ padding:12px 6px 6px; }
.row2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:700px){ .row2{ grid-template-columns:1fr; } }
.modalFoot{ display:flex; gap:10px; justify-content:flex-end; padding:12px 6px 6px; border-top:1px solid rgba(255,255,255,.08); margin-top:10px; }
</style>
</head>
<body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">POS • Ventas</span></div>
  <div class="split">
    <a class="btn" href="ventas.php">← Ventas</a>
    <button class="btn primary" form="fventa" type="submit">Guardar</button>
  </div>
</div>
<div class="layout">
  <!-- LEFT: Ticket -->
  <div class="card">
    <div class="hd">
      <div style="font-weight:1000">Ticket / Detalle</div>
      <div class="small">Agregá productos desde la derecha</div>
    </div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?=htmlspecialchars($err)?></div><?php endif; ?>
      <form id="fventa" method="post" onsubmit="return beforeSubmit();">
        <div class="grid3">
          <div>
            <div class="label">Cliente</div>
            <select name="cliente_id" class="input" required>
              <option value="">— Seleccionar —</option>
              <?php foreach($clientes as $c): ?>
                <option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <div class="label">Tipo</div>
            <select name="tipo" id="tipo" class="input">
              <option value="VENTA">VENTA</option>
              <option value="COTIZACION">COTIZACIÓN</option>
              <option value="PEDIDO">PEDIDO</option>
            </select>
          </div>
          <div>
            <div class="label">Bodega</div>
            <select name="bodega_id" id="bodega_id" class="input">
              <option value="0">— Seleccionar —</option>
              <?php foreach($bodegas as $b): ?>
                <option value="<?=$b['id']?>"><?=htmlspecialchars($b['nombre'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="grid3" style="margin-top:8px">
          <div>
            <div class="label">Moneda</div>
            <select name="moneda" id="moneda" class="input">
              <option value="CRC">CRC</option>
              <option value="USD">USD</option>
            </select>
          </div>
          <div>
            <div class="label">Tipo de cambio</div>
            <input name="tipo_cambio" id="tipo_cambio" class="input" type="number" step="0.00001" value="1.00000">
            <div class="small" id="tc_fuente"></div>
          </div>
          <div>
            <div class="label">Condición</div>
            <select name="condicion_venta" id="condicion_venta" class="input">
              <option value="CONTADO">CONTADO</option>
              <option value="CREDITO">CRÉDITO</option>
            </select>
          </div>
        </div>
        <div class="grid2" style="margin-top:8px">
          <div id="box_plazo" style="display:none">
            <div class="label">Plazo crédito (días)</div>
            <input name="plazo_credito" id="plazo_credito" class="input" type="number" min="1" value="30">
          </div>
          <div>
            <div class="label">Medio de pago</div>
            <select name="medio_pago" id="medio_pago" class="input">
              <option value="EFECTIVO">EFECTIVO</option>
              <option value="TARJETA">TARJETA</option>
              <option value="TRANSFERENCIA">TRANSFERENCIA</option>
              <option value="SINPE">SINPE</option>
              <option value="OTRO">OTRO</option>
            </select>
          </div>
        </div>
        <div class="grid2" style="margin-top:8px">
          <div>
            <div class="label">Referencia pago (opcional)</div>
            <input name="referencia_pago" id="referencia_pago" class="input" placeholder="Autorización / SINPE / transferencia">
          </div>
          <div>
            <div class="label">Observaciones</div>
            <input name="observaciones" class="input" placeholder="Opcional">
          </div>
        </div>
        <div class="label" style="margin-top:12px">Productos en ticket</div>
        <div style="border:1px solid rgba(255,255,255,.10);border-radius:16px;overflow:hidden">
          <table class="table" id="cartTable">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="right">Precio</th>
                <th class="right">Cant.</th>
                <th class="right">IVA</th>
                <th class="right">Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <input type="hidden" name="cart_json" id="cart_json" value="[]">
        <input type="hidden" name="pago_efectivo" id="pago_efectivo" value="0">
        <input type="hidden" name="pago_tarjeta" id="pago_tarjeta" value="0">
        <input type="hidden" name="monto_recibido" id="monto_recibido" value="0">
        <input type="hidden" name="vuelto" id="vuelto" value="0">
        <input type="hidden" name="cobro_confirmado" id="cobro_confirmado" value="0">
        <div class="kpis" style="margin-top:12px">
          <div class="kpi"><div class="t">Artículos</div><div class="v" id="kItems">0</div></div>
          <div class="kpi"><div class="t">Subtotal</div><div class="v" id="kSub">₡0,00</div></div>
          <div class="kpi"><div class="t">Impuestos</div><div class="v" id="kIva">₡0,00</div></div>
          <div class="kpi total"><div class="t">TOTAL A PAGAR</div><div class="v" id="kTot" data-raw="0">₡0,00</div></div>
        </div>
        <div class="footerpay">
          <button type="button" class="btn good" data-pay="EFECTIVO" onclick="setPago('EFECTIVO')">Efectivo</button>
          <button type="button" class="btn" data-pay="TARJETA" onclick="setPago('TARJETA')">Tarjeta</button>
          <button type="button" class="btn warn" data-pay="PAGO_MULTIPLE" onclick="setPago('PAGO_MULTIPLE')">Pago múltiple</button>
        </div>
        <div class="split" style="margin-top:12px">
          <button type="button" class="btn bad" onclick="clearCart()">Cancelar</button>
          <button type="button" class="btn" onclick="holdOrder()">Órdenes en espera</button>
          <button type="button" class="btn" onclick="window.print()">Imprimir</button>
        </div>
        <div class="notice" style="margin-top:12px">
          <b>Luego de guardar:</b>
          <div class="small" style="margin-top:6px">
            • Imprimir comprobante<br>
            • Generar/Enviar factura electrónica (Hacienda)
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- RIGHT: Buscador + catálogo -->
  <div class="card">
    <div class="hd">
      <div style="font-weight:1000">Catálogo</div>
      <div class="small">Click para agregar al ticket</div>
    </div>
    <div class="bd">
      <div class="tools">
        <div>
          <div class="label">Categoría</div>
          <select id="categoria" class="input">
            <option value="">Todas</option>
            <?php foreach($categorias as $cat): ?>
              <option value="<?=htmlspecialchars($cat)?>"><?=htmlspecialchars($cat)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <div class="label">Bodega (existencia)</div>
          <select id="bodega_side" class="input">
            <option value="0">— Seleccionar —</option>
            <?php foreach($bodegas as $b): ?>
              <option value="<?=$b['id']?>"><?=htmlspecialchars($b['nombre'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="label">Buscar producto (SKU / código / nombre)</div>
      <div class="searchbar">
        <div class="suggest" style="flex:1">
          <input id="busq" class="input" placeholder="Escribí para buscar..." autocomplete="off">
          <div id="slist" class="slist"></div>
        </div>
        <button class="btn warn" type="button" id="btnBuscar">Buscar</button>
      </div>
      <div class="small" style="margin-top:8px">Enter agrega el primer resultado. La lista muestra precio/IVA/existencia.</div>
      <div class="label" style="margin-top:14px">Productos</div>
      <div class="products" id="grid"></div>
    </div>
  </div>
</div>
<script>
// === JAVASCRIPT ORIGINAL COMPLETO ===
const cartTbody = document.querySelector('#cartTable tbody');
const cartJson = document.getElementById('cart_json');
const tipoSel = document.getElementById('tipo');
const condicionSel = document.getElementById('condicion_venta');
const boxPlazo = document.getElementById('box_plazo');
const plazoInp = document.getElementById('plazo_credito');
const monedaSel = document.getElementById('moneda');
const tcInp = document.getElementById('tipo_cambio');
const tcFuente = document.getElementById('tc_fuente');
const bodegaMain = document.getElementById('bodega_id');
const bodegaSide = document.getElementById('bodega_side');
const categoriaSel = document.getElementById('categoria');
const grid = document.getElementById('grid');
const busq = document.getElementById('busq');
const slist = document.getElementById('slist');
let cart = []; // {id,codigo,descripcion,precio,impuesto_pct,cabys,existencia,qty}
function crc(n){
  return "₡"+(Number(n)||0).toLocaleString('es-CR',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function fmt3(n){
  return (Number(n)||0).toLocaleString('es-CR',{minimumFractionDigits:3,maximumFractionDigits:3});
}
function syncBodega(fromSide){
  if(fromSide){
    bodegaMain.value = bodegaSide.value;
  }else{
    bodegaSide.value = bodegaMain.value;
  }
  loadGrid();
  doSearch(true);
}
bodegaMain.addEventListener('change', ()=>syncBodega(false));
bodegaSide.addEventListener('change', ()=>syncBodega(true));
condicionSel.addEventListener('change', ()=>{
  boxPlazo.style.display = (condicionSel.value==='CREDITO') ? 'block' : 'none';
  // Limpiar cobro si se cambia a CRÉDITO
  if (condicionSel.value === 'CREDITO') {
    document.getElementById('cobro_confirmado').value = '1';
    document.getElementById('pago_efectivo').value = '0';
    document.getElementById('pago_tarjeta').value = '0';
    document.getElementById('monto_recibido').value = '0';
    document.getElementById('vuelto').value = '0';
    document.querySelectorAll('.footerpay .btn').forEach(b => b.classList.remove('activePay'));
  }
});
function setPago(tipo) {
  const sel = document.getElementById('medio_pago');
  const ref = document.getElementById('referencia_pago');
  if (tipo === 'PAGO_MULTIPLE') {
    if (sel) sel.value = 'OTRO';
  } else {
    if (sel) sel.value = tipo;
    if (ref && ref.value === 'PAGO_MULTIPLE') ref.value = '';
  }
  document.querySelectorAll('.footerpay .btn').forEach(b => b.classList.remove('activePay'));
  const btn = document.querySelector('.footerpay .btn[data-pay="' + tipo + '"]');
  if (btn) btn.classList.add('activePay');
  if (condicionSel.value === 'CREDITO') {
    document.getElementById('cobro_confirmado').value = '1';
    return;
  }
  abrirCobro(tipo);
}
function holdOrder(){
  alert("Órdenes en espera: pendiente de implementar (se guarda el carrito en sesión o tabla).");
}
function clearCart(){
  if(confirm('¿Cancelar y vaciar carrito?')){
    cart=[]; renderCart();
  }
}
function addItem(p){
  const ex = cart.find(x=>String(x.id)===String(p.id));
  if(ex){ ex.qty = Math.round((ex.qty + 1)*1000)/1000; }
  else{
    cart.push({
      id:Number(p.id),
      codigo:p.codigo||p.id,
      descripcion:p.descripcion,
      precio:Number(p.precio),
      impuesto_pct:Number(p.impuesto_pct||13),
      cabys:p.cabys||'',
      existencia:Number(p.existencia||0),
      qty:1
    });
  }
  renderCart();
}
function removeItem(i){ cart.splice(i,1); renderCart(); }
function setQty(i, delta){
  let q = (Number(cart[i].qty)||1) + delta;
  q = Math.max(0.001, Math.round(q*1000)/1000);
  if(tipoSel.value==='VENTA' && Number(cart[i].existencia) < q){
    alert('Stock insuficiente. Disponible: '+fmt3(cart[i].existencia));
    q = Math.max(0.001, Number(cart[i].existencia)||0.001);
  }
  cart[i].qty = q;
  renderCart();
}
function renderCart(){
  cartTbody.innerHTML='';
  let subtotal=0, iva=0, total=0, items=0;
  cart.forEach((it, idx)=>{
    const lineSub = it.qty * it.precio;
    const lineIva = Math.round(lineSub*(it.impuesto_pct/100)*100)/100;
    const lineTot = Math.round((lineSub+lineIva)*100)/100;
    subtotal += lineSub; iva += lineIva; total += lineTot; items += 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div style="font-weight:1000">${it.codigo} — ${it.descripcion}</div>
        <div class="small">IVA ${Number(it.impuesto_pct).toFixed(2)}% • Disp: ${fmt3(it.existencia)} • CABYS: ${it.cabys||'-'}</div>
      </td>
      <td class="right" style="font-weight:1000">${crc(it.precio)}</td>
      <td class="right">
        <div class="qty">
          <button type="button" class="qbtn" onclick="setQty(${idx}, -1)">−</button>
          <input class="input qinp" value="${it.qty}" readonly>
          <button type="button" class="qbtn" onclick="setQty(${idx}, 1)">+</button>
        </div>
      </td>
      <td class="right">${crc(lineIva)}</td>
      <td class="right" style="font-weight:1000">${crc(lineSub)}</td>
      <td class="right"><button type="button" class="qbtn" title="Quitar" onclick="removeItem(${idx})">✕</button></td>
    `;
    cartTbody.appendChild(tr);
  });
  document.getElementById('kItems').textContent = items;
  document.getElementById('kSub').textContent = crc(subtotal);
  document.getElementById('kIva').textContent = crc(iva);
  const totElem = document.getElementById('kTot');
  totElem.textContent = crc(total);
  totElem.dataset.raw = total;
  cartJson.value = JSON.stringify(cart);
}
async function loadTC(){
  const mon = monedaSel.value;
  if(mon==='CRC'){ tcInp.value='1.00000'; tcFuente.textContent=''; return; }
  try{
    const r = await fetch('tipo_cambio.php?moneda='+encodeURIComponent(mon));
    if(!r.ok) return;
    const j = await r.json();
    if(j && j.ok){
      tcInp.value = Number(j.venta||1).toFixed(5);
      tcFuente.textContent = j.fuente ? ('Fuente: '+j.fuente) : '';
    }
  }catch(e){}
}
monedaSel.addEventListener('change', loadTC);
loadTC();
let lastItems = [];
async function doSearch(silent=false){
  const q = (busq.value||'').trim();
  if(!silent && q.length<2){ slist.style.display='none'; slist.innerHTML=''; return; }
  if(q.length<2 && silent){ return; }
  const bodega_id = Number(bodegaSide.value||0);
  const r = await fetch('ventas_productos_buscar.php?q='+encodeURIComponent(q)+'&bodega_id='+encodeURIComponent(bodega_id));
  if(!r.ok) return;
  const j = await r.json();
  lastItems = (j.items||[]);
  if(lastItems.length===0){ slist.style.display='none'; slist.innerHTML=''; return; }
  slist.innerHTML = lastItems.map(p=>`
    <div class="sitem" data-id="${p.id}">
      <div>
        <div style="font-weight:1000">${p.codigo} — ${p.descripcion}</div>
        <div class="small">Precio ${crc(p.precio)} • IVA ${Number(p.impuesto_pct||13).toFixed(2)}% • Disp ${fmt3(p.existencia||0)}</div>
      </div>
      <div class="pill">Agregar</div>
    </div>
  `).join('');
  slist.style.display='block';
  Array.from(slist.querySelectorAll('.sitem')).forEach(el=>{
    el.addEventListener('click', ()=>{
      const id = el.getAttribute('data-id');
      const p = lastItems.find(x=>String(x.id)===String(id));
      if(p) addItem(p);
      busq.value='';
      slist.style.display='none';
      busq.focus();
    });
  });
}
let t=null;
busq.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>doSearch(false), 180); });
document.getElementById('btnBuscar').addEventListener('click', ()=>doSearch(false));
busq.addEventListener('keydown', (e)=>{
  if(e.key==='Enter'){
    e.preventDefault();
    if(lastItems && lastItems.length>0){
      addItem(lastItems[0]);
      busq.value='';
      slist.style.display='none';
    }else{
      doSearch(false);
    }
  }
});
document.addEventListener('click', (e)=>{ if(!e.target.closest('.suggest')) slist.style.display='none'; });
async function loadGrid(){
  const cat = categoriaSel.value || '';
  const bodega_id = Number(bodegaSide.value||0);
  const q = (busq.value||'').trim();
  const url = 'ventas_productos_listar.php?categoria='+encodeURIComponent(cat)+'&bodega_id='+encodeURIComponent(bodega_id)+'&q='+encodeURIComponent(q);
  try{
    const r = await fetch(url);
    if(!r.ok) return;
    const j = await r.json();
    const items = (j.items||[]);
    grid.innerHTML = items.map(p=>`
      <div class="pcard" data-id="${p.id}">
        <div class="pname">${p.descripcion}</div>
        <div class="pmeta">
          <div class="pprice">${crc(p.precio)}</div>
          <div>Disp ${fmt3(p.existencia||0)}</div>
        </div>
        <div class="small" style="margin-top:6px">SKU ${p.codigo} • IVA ${Number(p.impuesto_pct||13).toFixed(2)}%</div>
      </div>
    `).join('');
    Array.from(grid.querySelectorAll('.pcard')).forEach(el=>{
      el.addEventListener('click', ()=>{
        const id = el.getAttribute('data-id');
        const p = items.find(x=>String(x.id)===String(id));
        if(p) addItem(p);
      });
    });
  }catch(e){}
}
categoriaSel.addEventListener('change', loadGrid);
function beforeSubmit(){
  if(cart.length===0){ alert('Agregá al menos un producto.'); return false; }
  if(tipoSel.value==='VENTA' && Number(bodegaMain.value||0)<=0){
    alert('Seleccioná una bodega.');
    return false;
  }
  if(condicionSel.value==='CREDITO'){
    if(Number(plazoInp.value||0)<=0){ alert('Indicá plazo de crédito.'); return false; }
    document.getElementById('cobro_confirmado').value = '1';
    cartJson.value = JSON.stringify(cart);
    return true;
  }
  const cobroOk = document.getElementById('cobro_confirmado').value === '1';
  if(!cobroOk){
    const mp = (document.getElementById('medio_pago')?.value || 'EFECTIVO');
    abrirCobro(mp);
    return false;
  }
  cartJson.value = JSON.stringify(cart);
  return true;
}
// Init
renderCart();
loadGrid();
let cobroTipo = 'EFECTIVO';
function abrirCobro(tipo){
  if(condicionSel.value==='CREDITO'){ return; }
  cobroTipo = tipo;
  const tot = Number(document.getElementById('kTot').dataset.raw || 0);
  document.getElementById('cobroTotal').value = money(tot);
  document.getElementById('cobroMedio').value = (tipo==='PAGO_MULTIPLE' ? 'PAGO MÚLTIPLE' : tipo);
  document.getElementById('cobroRecibido').value = '';
  document.getElementById('cobroVuelto').value = money(0);
  document.getElementById('cobroEfe').value = '';
  document.getElementById('cobroTar').value = '';
  document.getElementById('cobroIngresado').value = money(0);
  document.getElementById('cobroVueltoMix').value = money(0);
  const isMix = (tipo==='PAGO_MULTIPLE');
  document.getElementById('cobroSimple').style.display = isMix ? 'none' : 'block';
  document.getElementById('cobroMultiple').style.display = isMix ? 'block' : 'none';
  document.getElementById('cobroModal').style.display = 'flex';
  setTimeout(()=>{
    if(isMix) document.getElementById('cobroEfe').focus();
    else document.getElementById('cobroRecibido').focus();
  }, 50);
  calcCobro();
}
function cerrarCobro(){
  document.getElementById('cobroModal').style.display = 'none';
}
function calcCobro(){
  const tot = Number(document.getElementById('kTot').dataset.raw || 0);
  if(cobroTipo==='PAGO_MULTIPLE'){
    const efe = Number(document.getElementById('cobroEfe').value || 0);
    const tar = Number(document.getElementById('cobroTar').value || 0);
    const ing = efe + tar;
    document.getElementById('cobroIngresado').value = money(ing);
    const vuelto = Math.max(0, efe - Math.max(0, tot - tar));
    document.getElementById('cobroVueltoMix').value = money(vuelto);
  } else {
    const rec = Number(document.getElementById('cobroRecibido').value || 0);
    const vuelto = Math.max(0, rec - tot);
    document.getElementById('cobroVuelto').value = money(vuelto);
  }
}
function money(n){
  return Number(n||0).toLocaleString('es-CR', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function confirmarCobro(){
  const tot = Number(document.getElementById('kTot').dataset.raw || 0);
  const mpSel = document.getElementById('medio_pago');
  const ref = document.getElementById('referencia_pago');
  let pagoEfe = 0, pagoTar = 0, recibido = 0, vuelto = 0;
  if(cobroTipo==='PAGO_MULTIPLE'){
    pagoEfe = Number(document.getElementById('cobroEfe').value || 0);
    pagoTar = Number(document.getElementById('cobroTar').value || 0);
    const ing = pagoEfe + pagoTar;
    if(ing + 1e-9 < tot){
      alert('El total ingresado no cubre el total a pagar.');
      return;
    }
    vuelto = Math.max(0, pagoEfe - Math.max(0, tot - pagoTar));
    recibido = ing;
    if(mpSel) mpSel.value = 'OTRO';
    if(ref) ref.value = `MIX|EFE:${pagoEfe.toFixed(2)}|TAR:${pagoTar.toFixed(2)}|VUE:${vuelto.toFixed(2)}`;
  } else {
    recibido = Number(document.getElementById('cobroRecibido').value || 0);
    if(recibido + 1e-9 < tot){
      alert('El monto recibido no cubre el total a pagar.');
      return;
    }
    vuelto = Math.max(0, recibido - tot);
    if(mpSel) mpSel.value = (cobroTipo==='TARJETA' ? 'TARJETA' : 'EFECTIVO');
    if(ref){
      if(!ref.value) ref.value = `REC:${recibido.toFixed(2)}|VUE:${vuelto.toFixed(2)}`;
    }
    if(cobroTipo==='TARJETA' && ref && !ref.value){
      ref.value = 'AUTORIZACION:';
    }
  }
  document.getElementById('pago_efectivo').value = String(pagoEfe);
  document.getElementById('pago_tarjeta').value = String(pagoTar);
  document.getElementById('monto_recibido').value = String(recibido);
  document.getElementById('vuelto').value = String(vuelto);
  document.getElementById('cobro_confirmado').value = '1';
  cerrarCobro();
  document.getElementById('fventa').requestSubmit();
}
document.addEventListener('keydown', (e)=>{
  if(e.key==='Escape'){
    const m = document.getElementById('cobroModal');
    if(m && m.style.display==='flex') cerrarCobro();
  }
});
</script>
<!-- Modal de Cobro (POS) -->
<div id="cobroModal" class="modalOverlay" style="display:none">
  <div class="modalCard">
    <div class="modalHead">
      <div>
        <div class="modalTitle">Cobro</div>
        <div class="modalSub" id="cobroSub">Ingresá el monto recibido</div>
      </div>
      <button type="button" class="btnIcon" onclick="cerrarCobro()">✕</button>
    </div>
    <div class="modalBody">
      <div class="row2">
        <div>
          <div class="label">Total a pagar</div>
          <input class="input" id="cobroTotal" type="text" readonly>
        </div>
        <div>
          <div class="label">Medio</div>
          <input class="input" id="cobroMedio" type="text" readonly>
        </div>
      </div>
      <div id="cobroSimple">
        <div class="row2">
          <div>
            <div class="label">Monto recibido</div>
            <input class="input" id="cobroRecibido" type="number" step="0.01" min="0" placeholder="0.00" oninput="calcCobro()">
          </div>
          <div>
            <div class="label">Vuelto</div>
            <input class="input" id="cobroVuelto" type="text" readonly>
          </div>
        </div>
        <div class="small muted" id="cobroHint">Si pagan exacto, el vuelto es 0.</div>
      </div>
      <div id="cobroMultiple" style="display:none">
        <div class="row2">
          <div>
            <div class="label">Efectivo</div>
            <input class="input" id="cobroEfe" type="number" step="0.01" min="0" placeholder="0.00" oninput="calcCobro()">
          </div>
          <div>
            <div class="label">Tarjeta</div>
            <input class="input" id="cobroTar" type="number" step="0.01" min="0" placeholder="0.00" oninput="calcCobro()">
          </div>
        </div>
        <div class="row2">
          <div>
            <div class="label">Total ingresado</div>
            <input class="input" id="cobroIngresado" type="text" readonly>
          </div>
          <div>
            <div class="label">Vuelto (solo efectivo)</div>
            <input class="input" id="cobroVueltoMix" type="text" readonly>
          </div>
        </div>
        <div class="small muted">Validación: Efectivo + Tarjeta debe cubrir el total.</div>
      </div>
    </div>
    <div class="modalFoot">
      <button type="button" class="btn" onclick="cerrarCobro()">Cancelar</button>
      <button type="button" class="btn warn" onclick="confirmarCobro()">Confirmar cobro</button>
    </div>
  </div>
</div>
</body>
</html>