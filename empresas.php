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

$q=trim($_GET['q'] ?? '');
$where=[]; $params=[];

// Mapeo compatible (si tu BD usa nombre/cedula_juridica/email)
$col_razon = pick_col($pdo,'empresas',['razon_social','nombre'], 'nombre');
$col_comercial = pick_col($pdo,'empresas',['nombre_comercial'], null);
$col_tipo_id = pick_col($pdo,'empresas',['tipo_identificacion'], null);
$col_ident = pick_col($pdo,'empresas',['identificacion','cedula_juridica'], 'cedula_juridica');
$col_email = pick_col($pdo,'empresas',['email_fe','email'], 'email');

$has_logo = col_exists($pdo,'empresas','logo');
$has_fe = col_exists($pdo,'empresas','fe_ambiente');
$has_atv_user = col_exists($pdo,'empresas','atv_usuario');
$has_cert_ruta = col_exists($pdo,'empresas','cert_ruta');

if($q!==''){
  $parts=[];
  $like="%$q%";
  $parts[]="$col_razon LIKE ?";
  $params[]=$like;
  if($col_comercial){ $parts[]="$col_comercial LIKE ?"; $params[]=$like; }
  if($col_ident){ $parts[]="$col_ident LIKE ?"; $params[]=$like; }
  $where[]="(".implode(" OR ",$parts).")";
}
$w=count($where)?("WHERE ".implode(" AND ",$where)):"";

$sel = "id, $col_razon AS razon_social";
if($col_comercial) $sel .= ", $col_comercial AS nombre_comercial";
if($col_tipo_id) $sel .= ", $col_tipo_id AS tipo_identificacion";
$sel .= ", $col_ident AS identificacion, $col_email AS email_fe";
if($has_fe) $sel.=", fe_ambiente";
if($has_atv_user) $sel.=", atv_usuario";
if($has_cert_ruta) $sel.=", cert_ruta";
if($has_logo) $sel.=", (logo IS NOT NULL) AS tiene_logo"; else $sel.=", 0 AS tiene_logo";

$st=$pdo->prepare("SELECT $sel FROM empresas $w ORDER BY id DESC LIMIT 2000");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Empresas | FAC-IL-CR</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
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
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">EMPRESAS</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn primary" href="empresas_nuevo.php">➕ Nueva</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Empresas (Emisores FE)</div>
        <div class="small">Configura datos para Hacienda: dirección fiscal, ATV, certificado y logo.</div>
      </div>
    </div>
    <div class="bd">
      <form class="grid" method="get">
        <div style="grid-column:span 10">
          <div class="label">Buscar</div>
          <input class="input" name="q" value="<?=h($q)?>" placeholder="Nombre/Razón social, comercial o identificación...">
        </div>
        <div style="grid-column:span 2;display:flex;justify-content:flex-end">
          <button class="btn primary" type="submit">🔎</button>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead>
            <tr>
              <th>Logo</th>
              <th>ID</th>
              <th>Empresa</th>
              <th>Identificación</th>
              <th>Email FE</th>
              <?php if($has_fe): ?><th>Ambiente</th><?php endif; ?>
              <th>ATV</th>
              <th>Certificado</th>
              <th class="right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $e):
              $amb = $has_fe ? ($e['fe_ambiente'] ?? 'SANDBOX') : '—';
              $tagA = ($amb==='PRODUCCION')?'warn':'ok';
              $hasAtv = $has_atv_user ? (!empty($e['atv_usuario'])) : false;
              $hasCert = $has_cert_ruta ? (!empty($e['cert_ruta'])) : false;
            ?>
            <tr>
              <td>
                <div class="preview">
                  <?php if((int)($e['tiene_logo'] ?? 0)===1): ?>
                    <img src="empresa_logo.php?id=<?= (int)$e['id'] ?>" alt="logo">
                  <?php else: ?>
                    <span class="small">—</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="mono"><b><?= (int)$e['id'] ?></b></td>
              <td>
                <b><?= h($e['razon_social'] ?? '') ?></b>
                <?php if(!empty($e['nombre_comercial'])): ?><div class="small"><?= h($e['nombre_comercial']) ?></div><?php endif; ?>
              </td>
              <td class="mono"><?php if(!empty($e['tipo_identificacion'])): ?><?= h($e['tipo_identificacion']).' ' ?><?php endif; ?><?= h($e['identificacion'] ?? '') ?></td>
              <td><?= h($e['email_fe'] ?? '') ?></td>
              <?php if($has_fe): ?><td><span class="tag <?=$tagA?>"><?= h($amb) ?></span></td><?php endif; ?>
              <td><span class="tag <?= $hasAtv?'ok':'bad' ?>"><?= $hasAtv?'CONFIG':'FALTA' ?></span></td>
              <td><span class="tag <?= $hasCert?'ok':'bad' ?>"><?= $hasCert?'CARGADO':'FALTA' ?></span></td>
              <td class="right">
                <div class="actions">
                  <a class="btn" href="empresas_editar.php?id=<?= (int)$e['id'] ?>">⚙️ Configurar</a>
                  <a class="btn danger" href="empresas_eliminar.php?id=<?= (int)$e['id'] ?>" onclick="return confirm('¿Eliminar esta empresa?');">🗑️</a>
                </div>
              </td>
            </tr>
            <?php endforeach; if(count($rows)===0): ?>
              <tr><td colspan="10" class="small">Sin empresas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="notice small" style="margin-top:12px">
        Tip: Los certificados se guardan en <span class="mono">/storage/certs/</span> (no público). Para PRODUCCIÓN se requiere ATV + certificado.
      </div>
    </div>
  </div>
</div>
</body></html>
