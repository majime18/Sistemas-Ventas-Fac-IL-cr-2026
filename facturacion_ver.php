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
?>
<?php
$id=(int)($_GET['id'] ?? 0);
if($id<=0){ header("Location: facturacion.php"); exit; }
$st=$pdo->prepare("SELECT * FROM fe_documentos WHERE id=? AND empresa_id=? LIMIT 1");
$st->execute([$id,$empresa_id]);
$d=$st->fetch(PDO::FETCH_ASSOC);
if(!$d){ die("Documento no encontrado."); }
ob_start();
?>
<div class="card">
  <div class="h1">Documento FE #<?= (int)$id ?></div>
  <div class="p small">Tipo: <span class="mono"><?=h($d['tipo'])?></span> · Venta: <span class="mono"><?=h($d['venta_id'] ?? '—')?></span></div>

  <div class="grid" style="margin-top:14px">
    <div style="grid-column: span 6">
      <div class="small">Clave</div>
      <div class="input mono" style="display:flex;align-items:center"><?=h($d['clave'] ?? '')?></div>
    </div>
    <div style="grid-column: span 3">
      <div class="small">Consecutivo</div>
      <div class="input mono" style="display:flex;align-items:center"><?=h($d['consecutivo'] ?? '')?></div>
    </div>
    <div style="grid-column: span 3">
      <div class="small">Estado</div>
      <div class="input" style="display:flex;align-items:center"><b><?=h($d['estado'] ?? '')?></b></div>
    </div>

    <div style="grid-column: span 12">
      <div class="small">Mensaje Hacienda</div>
      <div class="input" style="display:flex;align-items:center"><?=h($d['mensaje_hacienda'] ?? '')?></div>
    </div>

    <div style="grid-column: span 6">
      <div class="small">XML firmado</div>
      <textarea class="input mono" rows="14" readonly><?=h($d['xml_firmado'] ?? '')?></textarea>
    </div>
    <div style="grid-column: span 6">
      <div class="small">Respuesta Hacienda</div>
      <textarea class="input mono" rows="14" readonly><?=h($d['respuesta_hacienda'] ?? '')?></textarea>
    </div>
  </div>

  <div class="actions" style="margin-top:14px;justify-content:flex-end">
    <a class="btn" href="facturacion.php">← Lista</a>
    <a class="btn btn-primary" href="facturacion_enviar_real.php?id=<?= (int)$id ?>" onclick="return confirm('¿Enviar/Reenviar a Hacienda?');">📤 Enviar</a>
    <a class="btn" href="facturacion_estado.php?id=<?= (int)$id ?>">🛰️ Estado</a>
    <a class="btn" href="facturacion_pdf.php?id=<?= (int)$id ?>" target="_blank">🧾 PDF</a>
  </div>
</div>
<?php
$html=ob_get_clean();
echo facil_wrap_page('FE #'.$id.' | FAC-IL-CR','Facturación electrónica (FE)',$html,'facturacion');
?>
