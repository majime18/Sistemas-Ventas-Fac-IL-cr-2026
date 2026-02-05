<?php
declare(strict_types=1);
require_once __DIR__."/config/db.php";
$id=(int)($_GET['id'] ?? 0);
if($id<=0){ http_response_code(404); exit; }
$hasLogo=false; $hasMime=false;
$st=$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='logo'");
$hasLogo=((int)$st->fetchColumn())>0;
$st=$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='logo_mime'");
$hasMime=((int)$st->fetchColumn())>0;
if(!$hasLogo){ http_response_code(404); exit; }
$sel = $hasMime ? "logo, logo_mime" : "logo";
$q=$pdo->prepare("SELECT $sel FROM empresas WHERE id=? LIMIT 1");
$q->execute([$id]);
$row=$q->fetch(PDO::FETCH_ASSOC);
if(!$row || empty($row['logo'])){ http_response_code(404); exit; }
$mime = $hasMime ? ($row['logo_mime'] ?? 'image/png') : 'image/png';
header("Content-Type: ".$mime);
header("Cache-Control: private, max-age=86400");
echo $row['logo'];
?>