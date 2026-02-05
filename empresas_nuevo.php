<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function col_exists(PDO $pdo,string $t,string $c):bool{
  $st=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$t,$c]); return ((int)$st->fetchColumn())>0;
}
function pick_col(PDO $pdo,string $t,array $cands,?string $default=null): ?string {
  foreach($cands as $c){ if(col_exists($pdo,$t,$c)) return $c; }
  return $default;
}
function app_key(): string {
  $k = getenv('FACILCR_APP_KEY');
  if($k && strlen($k) >= 16) return $k;
  return 'FAC-IL-CR-DEV-KEY-CAMBIAR-EN-PRODUCCION-2026';
}
function enc_secret(?string $plain): ?string {
  if($plain===null) return null;
  $plain=(string)$plain;
  if($plain==='') return '';
  $key=hash('sha256', app_key(), true);
  $iv=random_bytes(16);
  $cipher=openssl_encrypt($plain,'AES-256-CBC',$key,OPENSSL_RAW_DATA,$iv);
  if($cipher===false) return null;
  return base64_encode($iv.$cipher);
}
function storage_base(): string { return __DIR__ . DIRECTORY_SEPARATOR . 'storage'; }
function ensure_dir(string $dir): void { if(!is_dir($dir)) { @mkdir($dir, 0775, true); } }

if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }

$col_razon = pick_col($pdo,'empresas',['razon_social','nombre'], 'nombre');
$col_comercial = pick_col($pdo,'empresas',['nombre_comercial'], null);
$col_tipo_id = pick_col($pdo,'empresas',['tipo_identificacion'], null);
$col_ident = pick_col($pdo,'empresas',['identificacion','cedula_juridica'], 'cedula_juridica');
$col_email = pick_col($pdo,'empresas',['email_fe','email'], 'email');

$err='';
$razon=trim($_POST['razon_social'] ?? '');
$comercial=trim($_POST['nombre_comercial'] ?? '');
$tipo=$_POST['tipo_identificacion'] ?? 'JURIDICA';
$ident=trim($_POST['identificacion'] ?? '');
$email=trim($_POST['email_fe'] ?? '');

$has_logo = col_exists($pdo,'empresas','logo');
$has_logo_mime = col_exists($pdo,'empresas','logo_mime');

if($_SERVER['REQUEST_METHOD']==='POST'){
  if($razon==='' || $ident==='' || $email==='') $err="Nombre/Razón social, identificación y Email FE son obligatorios.";
  if(!$err){
    $cols=[]; $vals=[];
    $cols[]=$col_razon; $vals[]=$razon;
    if($col_comercial){ $cols[]=$col_comercial; $vals[]=$comercial===''?null:$comercial; }
    if($col_tipo_id){ $cols[]=$col_tipo_id; $vals[]=$tipo; }
    $cols[]=$col_ident; $vals[]=$ident;
    $cols[]=$col_email; $vals[]=$email;

    if($has_logo && !empty($_FILES['logo']['tmp_name'])){
      $bytes=file_get_contents($_FILES['logo']['tmp_name']);
      $mime=$_FILES['logo']['type'] ?? 'image/png';
      if(strlen((string)$bytes) > 1024*1024*2) $err="Logo demasiado grande (máx 2MB).";
      else {
        $cols[]="logo"; $vals[]=$bytes;
        if($has_logo_mime){ $cols[]="logo_mime"; $vals[]=$mime; }
      }
    }

    if(!$err){
      $ph=implode(",", array_fill(0,count($cols),"?"));
      $pdo->prepare("INSERT INTO empresas (".implode(",",$cols).") VALUES ($ph)")->execute($vals);
      header("Location: empresas.php"); exit;
    }
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva Empresa</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{margin:0;color:var(--txt);background:radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),linear-gradient(180deg,#020617,var(--fondo));min-height:100vh}
.header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08);background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));position:sticky;top:0;backdrop-filter: blur(12px);z-index:60}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.btn.danger{background:linear-gradient(180deg,#ef4444,#b91c1c);border-color:rgba(239,68,68,.55)}
.wrap{max-width:1600px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px;align-items:end}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr))}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.input,select,textarea{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.input:focus,select:focus,textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:0 0 6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.tabs{display:flex;gap:8px;flex-wrap:wrap}
.tab{padding:9px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:1000;text-decoration:none;color:var(--txt)}
.tab.on{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.preview{width:64px;height:64px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;overflow:hidden}
.preview img{width:100%;height:100%;object-fit:cover}
</style></head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">EMPRESAS • NUEVA</span></div>
  <div class="actions"><a class="btn" href="empresas.php">← Volver</a><button class="btn primary" form="f" type="submit">💾 Guardar</button></div>
</div>

<div class="wrap"><div class="card">
  <div class="hd"><div><div style="font-weight:1000;font-size:18px">Crear empresa</div><div class="small">Luego configurás Dirección fiscal, ATV y Certificado en “Configurar”.</div></div></div>
  <div class="bd">
    <?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
    <form id="f" method="post" enctype="multipart/form-data" class="grid">
      <div style="grid-column:span 6"><div class="label">Nombre / Razón social *</div><input class="input" name="razon_social" value="<?=h($razon)?>" required></div>
      <div style="grid-column:span 6"><div class="label">Nombre comercial</div><input class="input" name="nombre_comercial" value="<?=h($comercial)?>"></div>

      <?php if($col_tipo_id): ?>
      <div style="grid-column:span 3"><div class="label">Tipo identificación *</div>
        <select class="input" name="tipo_identificacion">
          <?php foreach(['FISICA','JURIDICA','DIMEX','NITE'] as $t): ?>
            <option value="<?=$t?>" <?=$tipo===$t?'selected':''?>><?=$t?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div style="grid-column:span 3"><div class="label">Identificación *</div><input class="input mono" name="identificacion" value="<?=h($ident)?>" required></div>
      <div style="grid-column:span 6"><div class="label">Email FE *</div><input class="input" type="email" name="email_fe" value="<?=h($email)?>" required></div>

      <?php if($has_logo): ?>
      <div style="grid-column:span 6">
        <div class="label">Logo (opcional)</div>
        <input class="input" type="file" name="logo" accept="image/*">
        <div class="small">Se usará en PDF de factura.</div>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div></div>
</body></html>
