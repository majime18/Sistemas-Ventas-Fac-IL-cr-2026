<?php
session_start();
require_once __DIR__ . "/config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
$empresa_id = (int)($_SESSION['empresa_id'] ?? 0);
$id=(int)($_GET['id'] ?? 0);
if($id<=0){ die("ID inválido"); }
$st=$pdo->prepare("SELECT d.*, e.nombre AS empresa_nombre, e.cedula_juridica AS empresa_iden FROM fe_documentos d
                   JOIN empresas e ON e.id=d.empresa_id
                   WHERE d.id=? AND d.empresa_id=? LIMIT 1");
$st->execute([$id,$empresa_id]);
$d=$st->fetch(PDO::FETCH_ASSOC);
if(!$d) die("No encontrado");
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PDF FE <?= (int)$id ?></title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto; margin:24px}
.h{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px}
.box{border:1px solid #ddd;border-radius:10px;padding:12px;margin-top:10px}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace}
</style></head><body>
<div class="h">
  <div>
    <h2 style="margin:0">Comprobante electrónico</h2>
    <div><?= h($d['empresa_nombre'] ?? '') ?></div>
    <div class="mono"><?= h($d['empresa_iden'] ?? '') ?></div>
  </div>
  <div style="text-align:right">
    <div><b><?= h($d['tipo'] ?? '') ?></b></div>
    <div class="mono"><?= h($d['consecutivo'] ?? '') ?></div>
    <div class="mono"><?= h($d['clave'] ?? '') ?></div>
  </div>
</div>
<div class="box"><b>Estado:</b> <?= h($d['estado'] ?? '') ?> · <?= h($d['mensaje_hacienda'] ?? '') ?></div>
<div class="box"><b>XML firmado (vista previa):</b><br><div class="mono" style="white-space:pre-wrap"><?= h(substr((string)($d['xml_firmado'] ?? ''),0,2500)) ?></div></div>
<script>window.print();</script>
</body></html>
