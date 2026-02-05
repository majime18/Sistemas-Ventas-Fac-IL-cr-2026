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
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id<=0){ header("Location: empresas.php"); exit; }

$col_razon = pick_col($pdo,'empresas',['razon_social','nombre'], 'nombre');
$col_comercial = pick_col($pdo,'empresas',['nombre_comercial'], null);
$col_tipo_id = pick_col($pdo,'empresas',['tipo_identificacion'], null);
$col_ident = pick_col($pdo,'empresas',['identificacion','cedula_juridica'], 'cedula_juridica');
$col_email = pick_col($pdo,'empresas',['email_fe','email'], 'email');

$has = [];
$fields = [
  'provincia','canton','distrito','barrio','otras_senas',
  'atv_usuario','atv_password','fe_ambiente',
  'cert_ruta','cert_password','logo','logo_mime'
];
foreach($fields as $f) $has[$f] = col_exists($pdo,'empresas',$f);
$has_logo = $has['logo'];
$has_logo_mime = $has['logo_mime'];

$st=$pdo->prepare("SELECT * FROM empresas WHERE id=? LIMIT 1");
$st->execute([$id]);
$e=$st->fetch(PDO::FETCH_ASSOC);
if(!$e) die("Empresa no encontrada");

$tab=$_GET['tab'] ?? 'general';
$err=''; $ok='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $tab=$_POST['tab'] ?? $tab;

  $razon = trim($_POST['razon_social'] ?? (string)($e[$col_razon] ?? ''));
  $comercial = trim($_POST['nombre_comercial'] ?? (string)($col_comercial?($e[$col_comercial] ?? ''):''));
  $tipo = $_POST['tipo_identificacion'] ?? (string)($col_tipo_id?($e[$col_tipo_id] ?? 'JURIDICA'):'JURIDICA');
  $ident = trim($_POST['identificacion'] ?? (string)($e[$col_ident] ?? ''));
  $email = trim($_POST['email_fe'] ?? (string)($e[$col_email] ?? ''));

  $prov=trim($_POST['provincia'] ?? (string)($e['provincia'] ?? ''));
  $can=trim($_POST['canton'] ?? (string)($e['canton'] ?? ''));
  $dis=trim($_POST['distrito'] ?? (string)($e['distrito'] ?? ''));
  $bar=trim($_POST['barrio'] ?? (string)($e['barrio'] ?? ''));
  $sen=trim($_POST['otras_senas'] ?? (string)($e['otras_senas'] ?? ''));

  $amb=$_POST['fe_ambiente'] ?? (string)($e['fe_ambiente'] ?? 'SANDBOX');
  $atv_u=trim($_POST['atv_usuario'] ?? (string)($e['atv_usuario'] ?? ''));
  $atv_p=(string)($_POST['atv_password'] ?? '');

  $cert_p=(string)($_POST['cert_password'] ?? '');

  if($tab==='general'){ if($razon==='' || $ident==='' || $email==='') $err="Nombre/Razón social, identificación y Email FE son obligatorios."; }
  if($tab==='direccion'){ if(($has['provincia']||$has['canton']||$has['distrito']||$has['barrio']||$has['otras_senas']) && ($prov===''||$can===''||$dis===''||$bar===''||$sen==='')) $err="Dirección fiscal incompleta."; }
  if($tab==='fe'){ if($has['fe_ambiente'] && !in_array($amb,['SANDBOX','PRODUCCION'],true)) $amb='SANDBOX'; }

  if(!$err){
    $set=[]; $vals=[];

    if($tab==='general'){
      $set[]="$col_razon=?"; $vals[]=$razon;
      if($col_comercial){ $set[]="$col_comercial=?"; $vals[]=$comercial===''?null:$comercial; }
      if($col_tipo_id){ $set[]="$col_tipo_id=?"; $vals[]=$tipo; }
      $set[]="$col_ident=?"; $vals[]=$ident;
      $set[]="$col_email=?"; $vals[]=$email;

      if($has_logo && !empty($_FILES['logo']['tmp_name'])){
        $bytes=file_get_contents($_FILES['logo']['tmp_name']);
        $mime=$_FILES['logo']['type'] ?? 'image/png';
        if(strlen((string)$bytes) > 1024*1024*2) $err="Logo demasiado grande (máx 2MB).";
        else {
          $set[]="logo=?"; $vals[]=$bytes;
          if($has_logo_mime){ $set[]="logo_mime=?"; $vals[]=$mime; }
        }
      }
    }

    if(!$err && $tab==='direccion'){
      foreach(['provincia'=>$prov,'canton'=>$can,'distrito'=>$dis,'barrio'=>$bar,'otras_senas'=>$sen] as $k=>$v){
        if($has[$k]){ $set[]="$k=?"; $vals[]=$v; }
      }
    }

    if(!$err && $tab==='fe'){
      if($has['fe_ambiente']){ $set[]="fe_ambiente=?"; $vals[]=$amb; }
      if($has['atv_usuario']){ $set[]="atv_usuario=?"; $vals[]=$atv_u===''?null:$atv_u; }
      if($has['atv_password'] && $atv_p!==''){ $set[]="atv_password=?"; $vals[]=enc_secret($atv_p); }
    }

    if(!$err && $tab==='cert'){
      $base=storage_base(); ensure_dir($base); ensure_dir($base.DIRECTORY_SEPARATOR.'certs');
      if(!empty($_FILES['cert_p12']['tmp_name'])){
        $name=$_FILES['cert_p12']['name'] ?? 'cert.p12';
        $ext=strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if($ext!=='p12') $err="El certificado debe ser .p12";
        else {
          $destRel='certs'.DIRECTORY_SEPARATOR.'empresa_'.$id.'.p12';
          $destAbs=$base.DIRECTORY_SEPARATOR.$destRel;
          if(!@move_uploaded_file($_FILES['cert_p12']['tmp_name'],$destAbs)){
            $bytes=file_get_contents($_FILES['cert_p12']['tmp_name']);
            if($bytes===false) $err="No se pudo leer el certificado.";
            else file_put_contents($destAbs,$bytes);
          }
          @chmod($destAbs,0660);
          if($has['cert_ruta']){ $set[]="cert_ruta=?"; $vals[]=str_replace('\\','/',$destRel); }
        }
      }
      if(!$err && $has['cert_password'] && $cert_p!==''){ $set[]="cert_password=?"; $vals[]=enc_secret($cert_p); }
    }

    if(!$err){
      if(count($set)>0){
        $vals[]=$id;
        $pdo->prepare("UPDATE empresas SET ".implode(",", $set)." WHERE id=?")->execute($vals);
      }
      header("Location: empresas_editar.php?id=".$id."&tab=".$tab."&ok=1"); exit;
    }
  }
}

if(isset($_GET['ok'])) $ok="Guardado correctamente.";
$ambActual = $has['fe_ambiente'] ? (string)($e['fe_ambiente'] ?? 'SANDBOX') : 'SANDBOX';
$certRuta = $has['cert_ruta'] ? (string)($e['cert_ruta'] ?? '') : '';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar Empresa</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">EMPRESAS • CONFIG</span></div>
  <div class="actions"><a class="btn" href="empresas.php">← Volver</a></div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px"><?= h($e[$col_razon] ?? '') ?></div>
        <div class="small">ID: <span class="mono"><?= (int)$id ?></span></div>
      </div>
      <div class="tabs">
        <a class="tab <?= $tab==='general'?'on':'' ?>" href="empresas_editar.php?id=<?= (int)$id ?>&tab=general">🏢 General</a>
        <a class="tab <?= $tab==='direccion'?'on':'' ?>" href="empresas_editar.php?id=<?= (int)$id ?>&tab=direccion">📍 Dirección</a>
        <a class="tab <?= $tab==='fe'?'on':'' ?>" href="empresas_editar.php?id=<?= (int)$id ?>&tab=fe">🧾 FE / ATV</a>
        <a class="tab <?= $tab==='cert'?'on':'' ?>" href="empresas_editar.php?id=<?= (int)$id ?>&tab=cert">🔐 Certificado</a>
      </div>
    </div>
    <div class="bd">
      <?php if($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>
      <?php if($ok): ?><div class="notice"><?= h($ok) ?></div><?php endif; ?>

      <?php if($tab==='general'): ?>
      <form method="post" enctype="multipart/form-data" class="grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="tab" value="general">
        <div style="grid-column:span 6"><div class="label">Nombre / Razón social *</div><input class="input" name="razon_social" value="<?= h($e[$col_razon] ?? '') ?>" required></div>
        <div style="grid-column:span 6"><div class="label">Nombre comercial</div><input class="input" name="nombre_comercial" value="<?= h($col_comercial?($e[$col_comercial] ?? ''):'') ?>"></div>
        <?php if($col_tipo_id): ?>
        <div style="grid-column:span 3"><div class="label">Tipo identificación</div>
          <select class="input" name="tipo_identificacion">
            <?php foreach(['FISICA','JURIDICA','DIMEX','NITE'] as $t): ?>
              <option value="<?=$t?>" <?= (($e[$col_tipo_id] ?? 'JURIDICA')===$t)?'selected':'' ?>><?=$t?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div style="grid-column:span 3"><div class="label">Identificación *</div><input class="input mono" name="identificacion" value="<?= h($e[$col_ident] ?? '') ?>" required></div>
        <div style="grid-column:span 6"><div class="label">Email FE *</div><input class="input" type="email" name="email_fe" value="<?= h($e[$col_email] ?? '') ?>" required></div>

        <?php if($has_logo): ?>
        <div style="grid-column:span 6">
          <div class="label">Logo</div>
          <div style="display:flex;gap:10px;align-items:center">
            <div class="preview"><?php if(!empty($e['logo'])): ?><img src="empresa_logo.php?id=<?= (int)$id ?>" alt="logo"><?php else: ?><span class="small">—</span><?php endif; ?></div>
            <input class="input" type="file" name="logo" accept="image/*">
          </div>
        </div>
        <?php endif; ?>

        <div style="grid-column:span 12;display:flex;justify-content:flex-end">
          <button class="btn primary" type="submit">💾 Guardar</button>
        </div>
      </form>
      <?php endif; ?>

      <?php if($tab==='direccion'): ?>
      <form method="post" class="grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="tab" value="direccion">
        <div style="grid-column:span 2"><div class="label">Provincia *</div><input class="input mono" name="provincia" value="<?= h($e['provincia'] ?? '') ?>"></div>
        <div style="grid-column:span 2"><div class="label">Cantón *</div><input class="input mono" name="canton" value="<?= h($e['canton'] ?? '') ?>"></div>
        <div style="grid-column:span 2"><div class="label">Distrito *</div><input class="input mono" name="distrito" value="<?= h($e['distrito'] ?? '') ?>"></div>
        <div style="grid-column:span 2"><div class="label">Barrio *</div><input class="input mono" name="barrio" value="<?= h($e['barrio'] ?? '') ?>"></div>
        <div style="grid-column:span 4"><div class="label">Otras señas *</div><input class="input" name="otras_senas" value="<?= h($e['otras_senas'] ?? '') ?>"></div>
        <div style="grid-column:span 12;display:flex;justify-content:flex-end">
          <button class="btn primary" type="submit">💾 Guardar</button>
        </div>
      </form>
      <?php endif; ?>

      <?php if($tab==='fe'): ?>
      <form method="post" class="grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="tab" value="fe">
        <div style="grid-column:span 3"><div class="label">Ambiente FE</div>
          <select class="input" name="fe_ambiente">
            <option value="SANDBOX" <?= $ambActual==='SANDBOX'?'selected':'' ?>>SANDBOX</option>
            <option value="PRODUCCION" <?= $ambActual==='PRODUCCION'?'selected':'' ?>>PRODUCCION</option>
          </select>
        </div>
        <div style="grid-column:span 4"><div class="label">Usuario ATV</div><input class="input" name="atv_usuario" value="<?= h($e['atv_usuario'] ?? '') ?>"></div>
        <div style="grid-column:span 5"><div class="label">Contraseña ATV</div><input class="input" type="password" name="atv_password" value="" placeholder="(dejar vacío para no cambiar)"></div>
        <div style="grid-column:span 12;display:flex;justify-content:flex-end">
          <button class="btn primary" type="submit">💾 Guardar</button>
        </div>
      </form>
      <?php endif; ?>

      <?php if($tab==='cert'): ?>
      <form method="post" enctype="multipart/form-data" class="grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="tab" value="cert">
        <div style="grid-column:span 7"><div class="label">Certificado .p12</div><input class="input" type="file" name="cert_p12" accept=".p12"></div>
        <div style="grid-column:span 5"><div class="label">Password certificado</div><input class="input" type="password" name="cert_password" value="" placeholder="(dejar vacío para no cambiar)"></div>
        <div style="grid-column:span 12" class="notice small">Ruta actual: <span class="mono"><?= h($certRuta ?: '—') ?></span></div>
        <div style="grid-column:span 12;display:flex;justify-content:flex-end">
          <button class="btn primary" type="submit">💾 Guardar</button>
        </div>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>
</body></html>
