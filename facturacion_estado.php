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
$st=$pdo->prepare("SELECT id, clave, estado, mensaje_hacienda, respuesta_hacienda FROM fe_documentos WHERE id=? AND empresa_id=? LIMIT 1");
$st->execute([$id,$empresa_id]);
$d=$st->fetch(PDO::FETCH_ASSOC);
if(!$d) die("No encontrado.");
ob_start();
?>
<div class="card">
  <div class="h1">Estado FE #<?= (int)$id ?></div>
  <div class="p small">Aquí ves lo guardado. La consulta/enviar real se completa en <span class="mono">fe_lib.php</span>.</div>

  <div class="grid" style="margin-top:14px">
    <div style="grid-column: span 4">
      <div class="small">Estado</div>
      <div class="input"><b><?=h($d['estado'] ?? '')?></b></div>
    </div>
    <div style="grid-column: span 8">
      <div class="small">Mensaje</div>
      <div class="input"><?=h($d['mensaje_hacienda'] ?? '')?></div>
    </div>
    <div style="grid-column: span 12">
      <div class="small">Respuesta (raw)</div>
      <textarea class="input mono" rows="16" readonly><?=h($d['respuesta_hacienda'] ?? '')?></textarea>
    </div>
  </div>

  <div class="actions" style="margin-top:14px;justify-content:flex-end">
    <a class="btn" href="facturacion_ver.php?id=<?= (int)$id ?>">← Volver</a>
  </div>
</div>
<?php
$html=ob_get_clean();
echo facil_wrap_page('Estado FE | FAC-IL-CR','Facturación electrónica (FE)',$html,'facturacion');
?>
