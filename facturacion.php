<?php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/facturacion_ui.php";

if (empty($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

// Empresa seleccionada (para que SIEMPRE veas las FE creadas desde Ventas)
$empresa_id_sesion = (int)($_SESSION['empresa_id'] ?? 0);
$empresa_id = (int)($_GET['empresa_id'] ?? $empresa_id_sesion);

// fallback: primera empresa
if ($empresa_id <= 0) {
  $emp = $pdo->query("SELECT id FROM empresas ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if ($emp) { $empresa_id = (int)$emp['id']; }
}
if ($empresa_id <= 0) { die("No hay empresas registradas."); }

// Persistir selección para el resto del sistema
$_SESSION['empresa_id'] = $empresa_id;

// Listado de empresas para selector
$empresas = $pdo->query("SELECT id, COALESCE(nombre,'Empresa') AS nombre FROM empresas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

$where = ["d.empresa_id = ?"];
$params = [$empresa_id];

if ($q !== '') {
  $where[] = "(d.clave LIKE ? OR d.consecutivo LIKE ? OR d.mensaje_hacienda LIKE ? OR CAST(d.venta_id AS CHAR) LIKE ?)";
  $like = "%$q%";
  $params[]=$like; $params[]=$like; $params[]=$like; $params[]=$like;
}
if (in_array($estado, ['PENDIENTE','ACEPTADA','RECHAZADA','ANULADA'], true)) {
  $where[] = "d.estado = ?";
  $params[] = $estado;
}
if (in_array($tipo, ['FACTURA','TIQUETE','NC','ND'], true)) {
  $where[] = "d.tipo = ?";
  $params[] = $tipo;
}

$sql = "SELECT d.id, d.tipo, d.clave, d.consecutivo, d.estado, d.venta_id, d.fecha_emision, d.mensaje_hacienda, d.created_at,
               v.total AS venta_total, v.moneda AS venta_moneda, v.condicion_venta, v.medio_pago
        FROM fe_documentos d
        LEFT JOIN ventas v ON v.id = d.venta_id
        WHERE ".implode(" AND ", $where)."
        ORDER BY d.id DESC
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ventas que ya hiciste pero no aparecen aquí (porque no tienen FE amarrada)
$ventas_sql = "SELECT v.id, v.created_at, v.total, v.moneda, v.condicion_venta, v.medio_pago, v.facturada_at, v.fe_documento_id,
                      c.nombre AS cliente
               FROM ventas v
               LEFT JOIN clientes c ON c.id = v.cliente_id
               WHERE v.empresa_id = ?
                 AND COALESCE(v.anulado,0)=0
                 AND v.tipo IN ('VENTA','PEDIDO')
               ORDER BY v.id DESC
               LIMIT 200";
$vstmt = $pdo->prepare($ventas_sql);
$vstmt->execute([$empresa_id]);
$ventas = $vstmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div style="display:flex;gap:14px;flex-wrap:wrap;align-items:end;margin-bottom:16px">
  <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;width:100%">
    <div style="min-width:220px">
      <div style="opacity:.85;font-size:13px;margin-bottom:6px">Empresa</div>
      <select name="empresa_id" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.5);color:#e5e7eb">
        <?php foreach($empresas as $e): ?>
          <option value="<?= (int)$e['id'] ?>" <?= ((int)$e['id']===$empresa_id?'selected':'') ?>>
            #<?= (int)$e['id'] ?> — <?= h($e['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="flex:1;min-width:260px">
      <div style="opacity:.85;font-size:13px;margin-bottom:6px">Buscar (clave / consecutivo / venta / mensaje)</div>
      <input name="q" value="<?= h($q) ?>" placeholder="Ej: clave, consecutivo, #venta..." style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.5);color:#e5e7eb">
    </div>

    <div style="min-width:180px">
      <div style="opacity:.85;font-size:13px;margin-bottom:6px">Tipo</div>
      <select name="tipo" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.5);color:#e5e7eb">
        <option value="">Todos</option>
        <?php foreach(['FACTURA','TIQUETE','NC','ND'] as $t): ?>
          <option value="<?= $t ?>" <?= ($tipo===$t?'selected':'') ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width:180px">
      <div style="opacity:.85;font-size:13px;margin-bottom:6px">Estado</div>
      <select name="estado" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.5);color:#e5e7eb">
        <option value="">Todos</option>
        <?php foreach(['PENDIENTE','ACEPTADA','RECHAZADA','ANULADA'] as $es): ?>
          <option value="<?= $es ?>" <?= ($estado===$es?'selected':'') ?>><?= $es ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <button type="submit" style="padding:12px 16px;border-radius:14px;border:0;background:#ffc107;color:#1f2937;font-weight:900;cursor:pointer">Filtrar</button>
      <a href="facturacion.php?empresa_id=<?= (int)$empresa_id ?>" style="margin-left:8px;padding:12px 16px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#e5e7eb;text-decoration:none;font-weight:800">Limpiar</a>
    </div>
  </form>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:16px">
  <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);border-radius:18px;padding:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <div>
        <div style="font-size:20px;font-weight:900">Facturas electrónicas (FE)</div>
        <div style="opacity:.8">Se muestran registros de <b>fe_documentos</b> para la empresa seleccionada.</div>
      </div>
      <a href="facturacion_nuevo.php" style="padding:10px 14px;border-radius:14px;background:#0b5ed7;color:#fff;text-decoration:none;font-weight:900">+ Nuevo</a>
    </div>

    <div style="overflow:auto;margin-top:12px">
      <table style="width:100%;border-collapse:separate;border-spacing:0 10px">
        <thead>
          <tr style="opacity:.8;text-align:left;font-size:12px">
            <th style="padding:0 10px">ID</th>
            <th style="padding:0 10px">Tipo</th>
            <th style="padding:0 10px">Consecutivo</th>
            <th style="padding:0 10px">Clave</th>
            <th style="padding:0 10px">Estado</th>
            <th style="padding:0 10px">Venta</th>
            <th style="padding:0 10px">Total</th>
            <th style="padding:0 10px">Fecha</th>
            <th style="padding:0 10px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$docs): ?>
            <tr>
              <td colspan="9" style="padding:14px 10px;opacity:.85">No hay FE registradas con esos filtros.</td>
            </tr>
          <?php endif; ?>
          <?php foreach($docs as $d): ?>
            <tr style="background:rgba(2,6,23,.55);border:1px solid rgba(255,255,255,.10)">
              <td style="padding:12px 10px;border-radius:14px 0 0 14px;font-weight:900"><?= (int)$d['id'] ?></td>
              <td style="padding:12px 10px"><?= h($d['tipo']) ?></td>
              <td style="padding:12px 10px"><?= h($d['consecutivo'] ?? '') ?></td>
              <td style="padding:12px 10px;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($d['clave'] ?? '') ?></td>
              <td style="padding:12px 10px">
                <?php
                  $es = $d['estado'] ?? 'PENDIENTE';
                  $bg = ($es==='ACEPTADA') ? 'rgba(34,197,94,.18)' : (($es==='RECHAZADA') ? 'rgba(239,68,68,.18)' : (($es==='ANULADA') ? 'rgba(148,163,184,.18)' : 'rgba(255,193,7,.18)'));
                  $bd = ($es==='ACEPTADA') ? '#22c55e' : (($es==='RECHAZADA') ? '#ef4444' : (($es==='ANULADA') ? '#94a3b8' : '#ffc107'));
                ?>
                <span style="display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid <?= $bd ?>;background:<?= $bg ?>;font-weight:900"><?= h($es) ?></span>
              </td>
              <td style="padding:12px 10px">#<?= (int)($d['venta_id'] ?? 0) ?></td>
              <td style="padding:12px 10px;font-weight:900"><?= h($d['venta_moneda'] ?? '') ?> <?= number_format((float)($d['venta_total'] ?? 0),2) ?></td>
              <td style="padding:12px 10px;opacity:.85"><?= h(substr((string)($d['fecha_emision'] ?? $d['created_at']),0,19)) ?></td>
              <td style="padding:12px 10px;border-radius:0 14px 14px 0;white-space:nowrap">
                <a href="facturacion_ver.php?id=<?= (int)$d['id'] ?>" style="padding:8px 10px;border-radius:12px;background:#0b5ed7;color:#fff;text-decoration:none;font-weight:900">Ver</a>
                <a href="facturacion_estado.php?id=<?= (int)$d['id'] ?>" style="padding:8px 10px;border-radius:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#e5e7eb;text-decoration:none;font-weight:900;margin-left:6px">Estado</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);border-radius:18px;padding:16px">
    <div style="font-size:18px;font-weight:900">Ventas recientes (para encontrar tus facturas hechas desde POS)</div>
    <div style="opacity:.8;margin-top:4px">
      Esta lista sirve para ubicar ventas. Si una venta tiene <b>fe_documento_id</b> debería aparecer arriba en FE.
      Si no, entrá a la venta y presioná <b>FACTURAR</b>.
    </div>

    <div style="overflow:auto;margin-top:12px">
      <table style="width:100%;border-collapse:separate;border-spacing:0 10px">
        <thead>
          <tr style="opacity:.8;text-align:left;font-size:12px">
            <th style="padding:0 10px">Venta</th>
            <th style="padding:0 10px">Cliente</th>
            <th style="padding:0 10px">Total</th>
            <th style="padding:0 10px">Condición</th>
            <th style="padding:0 10px">Medio</th>
            <th style="padding:0 10px">FE</th>
            <th style="padding:0 10px">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($ventas as $v): ?>
            <tr style="background:rgba(2,6,23,.55);border:1px solid rgba(255,255,255,.10)">
              <td style="padding:12px 10px;border-radius:14px 0 0 14px;font-weight:900">#<?= (int)$v['id'] ?></td>
              <td style="padding:12px 10px"><?= h($v['cliente'] ?? '—') ?></td>
              <td style="padding:12px 10px;font-weight:900"><?= h($v['moneda'] ?? '') ?> <?= number_format((float)$v['total'],2) ?></td>
              <td style="padding:12px 10px"><?= h($v['condicion_venta'] ?? '') ?></td>
              <td style="padding:12px 10px"><?= h($v['medio_pago'] ?? '') ?></td>
              <td style="padding:12px 10px">
                <?php if(!empty($v['fe_documento_id'])): ?>
                  <span style="display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid #22c55e;background:rgba(34,197,94,.18);font-weight:900">#<?= (int)$v['fe_documento_id'] ?></span>
                <?php else: ?>
                  <span style="display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid #ffc107;background:rgba(255,193,7,.18);font-weight:900">SIN FE</span>
                <?php endif; ?>
              </td>
              <td style="padding:12px 10px;border-radius:0 14px 14px 0;white-space:nowrap">
                <a href="ventas_ver.php?id=<?= (int)$v['id'] ?>" style="padding:8px 10px;border-radius:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#e5e7eb;text-decoration:none;font-weight:900">Abrir venta</a>
                <?php if(empty($v['fe_documento_id'])): ?>
                  <span style="opacity:.75;margin-left:8px">→ Facturar desde la venta</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$html = ob_get_clean();
echo facil_wrap_page("Facturación Electrónica", "Facturación • FE", $html, "facturacion");
?>
