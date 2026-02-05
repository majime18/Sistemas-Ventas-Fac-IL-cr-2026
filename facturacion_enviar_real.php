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
$st=$pdo->prepare("SELECT id FROM fe_documentos WHERE id=? AND empresa_id=? LIMIT 1");
$st->execute([$id,$empresa_id]);
if(!$st->fetchColumn()){ die("No encontrado"); }
$pdo->prepare("UPDATE fe_documentos SET mensaje_hacienda=? WHERE id=?")->execute(["(Pendiente) Completar token/firma/envío real a Hacienda.", $id]);
header("Location: facturacion_ver.php?id=".$id); exit;
?>
