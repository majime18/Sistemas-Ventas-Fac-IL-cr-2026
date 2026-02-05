<?php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . '/facturacion_ui.php';

if (empty($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
$empresa_id = (int)($_SESSION['empresa_id'] ?? 0);
if ($empresa_id <= 0) {
  $emp = $pdo->query("SELECT id FROM empresas ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if ($emp) { $empresa_id = (int)$emp['id']; $_SESSION['empresa_id'] = $empresa_id; }
}
if ($empresa_id <= 0) { die("No hay empresas registradas."); }
if (!isset($_SESSION['empresa_id'])) { $_SESSION['empresa_id'] = $empresa_id; }

// Selector de empresa (para ver facturas de otra empresa si estás en multiempresa)
try {
  $cols = $pdo->query("SHOW COLUMNS FROM empresas")->fetchAll(PDO::FETCH_COLUMN, 0);
  $has_nombre = in_array('nombre', $cols, true);
  $has_razon = in_array('razon_social', $cols, true);
  $labelCol = $has_razon ? 'razon_social' : ($has_nombre ? 'nombre' : 'id');
  $empresas = $pdo->query("SELECT id, $labelCol AS label FROM empresas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $empresas = [];
}

if (isset($_GET['empresa_id']) && $_GET['empresa_id'] !== '') {
  $eid = (int)$_GET['empresa_id'];
  if ($eid > 0) { $empresa_id = $eid; $_SESSION['empresa_id'] = $empresa_id; }
}

?>
<?php
$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$where = ["empresa_id = ?"];
$params = [$empresa_id];
if ($q !== '') {
  $where[] = "(clave LIKE ? OR consecutivo LIKE ? OR mensaje_hacienda LIKE ?)";
  $like = "%$q%";
  $params[]=$like; $params[]=$like; $params[]=$like;
}
if (in_array($estado, ['PENDIENTE','ACEPTADA','RECHAZADA'], true)) { $where[]="estado=?"; $params[]=$estado; }
if (in_array($tipo, ['FACTURA','TIQUETE','NC','ND'], true)) { $where[]="tipo=?"; $params[]=$tipo; }
$sql = "SELECT id, venta_id, tipo, clave, consecutivo, estado, mensaje_hacienda, fecha_emision, created_at
        FROM fe_documentos
        WHERE ".implode(" AND ", $where)."
        ORDER BY id DESC
        LIMIT 500";
$st=$pdo->prepare($sql);
$st->execute($params);
$docs=$st->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="card">
  <div class="h1">Facturación electrónica</div>
  <div class="p">Listado de comprobantes FE por empresa. Filtrá por clave/consecutivo/estado.</div>

  <form method="get" class="grid" style="margin-top:14px">
    <div style="grid-column: span 4">
      <div class="small">Buscar</div>
      <input class="input" name="q" value="<?=h($q)?>" placeholder="Clave, consecutivo, mensaje...">
    </div>
    <div style="grid-column: span 3">
      <div class="small">Empresa</div>
      <select class="input" name="empresa_id">
        <?php if(!$empresas): ?>
          <option value="<?= (int)$empresa_id ?>">Empresa #<?= (int)$empresa_id ?></option>
        <?php else: foreach($empresas as $em): ?>
          <option value="<?= (int)$em['id'] ?>" <?= ((int)$empresa_id === (int)$em['id'] ? 'selected' : '') ?>>
            <?= h($em['label'] ?? ('Empresa #'.(int)$em['id'])) ?>
          </option>
        <?php endforeach; endif; ?>
      </select>
    </div>
    <div style="grid-column: span 2">
      <div class="small">Tipo</div>
      <select class="input" name="tipo">
        <option value="">Todos</option>
        <?php foreach(['FACTURA','TIQUETE','NC','ND'] as $t): ?>
          <option value="<?=$t?>" <?=($tipo===$t?'selected':'')?>><?=$t?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="grid-column: span 2">
      <div class="small">Estado</div>
      <select class="input" name="estado">
        <option value="">Todos</option>
        <?php foreach(['PENDIENTE','ACEPTADA','RECHAZADA'] as $e): ?>
          <option value="<?=$e?>" <?=($estado===$e?'selected':'')?>><?=$e?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="grid-column: span 1; display:flex; align-items:end; justify-content:flex-end">
      <button class="btn btn-primary" type="submit">🔎</button>
    </div>
  </form>

  <div style="margin-top:14px; overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Tipo</th><th>Venta</th><th>Clave</th><th>Consecutivo</th><th>Estado</th><th>Mensaje</th><th>Emisión</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if(!$docs): ?>
        <tr><td colspan="9" class="small">No hay documentos. Creá uno con “Nuevo”.</td></tr>
      <?php endif; ?>
      <?php foreach($docs as $d):
        $estadoTag = ($d['estado']==='ACEPTADA') ? 'ok' : (($d['estado']==='RECHAZADA') ? 'err' : '');
      ?>
        <tr>
          <td><b class="mono"><?= (int)$d['id'] ?></b></td>
          <td><span class="pill"><?= h($d['tipo']) ?></span></td>
          <td class="mono"><?= $d['venta_id'] ? (int)$d['venta_id'] : '—' ?></td>
          <td class="mono"><?= h($d['clave'] ?? '—') ?></td>
          <td class="mono"><?= h($d['consecutivo'] ?? '—') ?></td>
          <td><span class="pill <?= $estadoTag ?>"><?= h($d['estado']) ?></span></td>
          <td class="small"><?= h($d['mensaje_hacienda'] ?? '') ?></td>
          <td class="small"><?= h($d['fecha_emision'] ?? $d['created_at'] ?? '') ?></td>
          <td>
            <div class="actions">
              <a class="btn" href="facturacion_ver.php?id=<?= (int)$d['id'] ?>">Ver</a>
              <a class="btn btn-primary" href="facturacion_enviar_real.php?id=<?= (int)$d['id'] ?>" onclick="return confirm('¿Enviar/Reenviar a Hacienda?');">Enviar</a>
              <a class="btn" href="facturacion_estado.php?id=<?= (int)$d['id'] ?>">Estado</a>
              <a class="btn" href="facturacion_pdf.php?id=<?= (int)$d['id'] ?>" target="_blank">PDF</a>
              <a class="btn btn-warning" href="facturacion_eliminar.php?id=<?= (int)$d['id'] ?>" onclick="return confirm('¿Eliminar? Solo recomendado si está PENDIENTE');">Eliminar</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$html = ob_get_clean();
echo facil_wrap_page('Facturación | FAC-IL-CR','Facturación electrónica (FE)',$html,'facturacion');
?>
