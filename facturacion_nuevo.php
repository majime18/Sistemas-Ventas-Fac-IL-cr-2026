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
$tipo=$_POST['tipo'] ?? 'FACTURA';
$venta_id=(int)($_POST['venta_id'] ?? 0);
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!in_array($tipo,['FACTURA','TIQUETE','NC','ND'],true)) $tipo='FACTURA';
  $st=$pdo->prepare("INSERT INTO fe_documentos (empresa_id, venta_id, tipo, estado, created_at) VALUES (?,?,?,?,NOW())");
  $st->execute([$empresa_id, $venta_id>0?$venta_id:null, $tipo, 'PENDIENTE']);
  $newId=(int)$pdo->lastInsertId();
  header("Location: facturacion_ver.php?id=".$newId); exit;
}
ob_start();
?>
<div class="card">
  <div class="h1">Nuevo documento FE</div>
  <div class="p small">Crea un registro <b>PENDIENTE</b> en <span class="mono">fe_documentos</span>.</div>

  <form method="post" class="grid" style="margin-top:14px">
    <div style="grid-column: span 4">
      <div class="small">Tipo</div>
      <select class="input" name="tipo">
        <?php foreach(['FACTURA','TIQUETE','NC','ND'] as $t): ?>
          <option value="<?=$t?>" <?=($tipo===$t?'selected':'')?>><?=$t?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="grid-column: span 4">
      <div class="small">Venta (opcional)</div>
      <input class="input mono" type="number" name="venta_id" value="<?= (int)$venta_id ?>" placeholder="ID venta">
    </div>
    <div style="grid-column: span 12; display:flex; justify-content:flex-end; gap:10px; align-items:end">
      <a class="btn" href="facturacion.php">Cancelar</a>
      <button class="btn btn-primary" type="submit">Crear</button>
    </div>
  </form>
</div>
<?php
$html=ob_get_clean();
echo facil_wrap_page('Nuevo FE | FAC-IL-CR','Facturación electrónica (FE)',$html,'facturacion');
?>
