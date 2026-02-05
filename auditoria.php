<?php
session_start();
require_once "config/db.php";
if (empty($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$empresa_id = (int)($_SESSION['empresa_id'] ?? 1);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$hoy = new DateTime('now');
$desde = $_GET['desde'] ?? (new DateTime('now'))->modify('-7 days')->format('Y-m-d');
$hasta = $_GET['hasta'] ?? (new DateTime('now'))->format('Y-m-d');
$modulo = trim($_GET['modulo'] ?? '');
$accion = trim($_GET['accion'] ?? '');
$usuario = (int)($_GET['usuario_id'] ?? 0);
$texto = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 300);
if ($limit < 50) $limit = 50;
if ($limit > 1000) $limit = 1000;

// combos
$uStmt = $pdo->prepare("SELECT id,nombre,email FROM usuarios WHERE empresa_id=? ORDER BY nombre");
$uStmt->execute([$empresa_id]);
$usuarios = $uStmt->fetchAll(PDO::FETCH_ASSOC);

$mStmt = $pdo->prepare("SELECT DISTINCT modulo FROM auditoria WHERE empresa_id=? ORDER BY modulo");
$mStmt->execute([$empresa_id]);
$modulos = $mStmt->fetchAll(PDO::FETCH_COLUMN);

$aStmt = $pdo->prepare("SELECT DISTINCT accion FROM auditoria WHERE empresa_id=? ORDER BY accion");
$aStmt->execute([$empresa_id]);
$acciones = $aStmt->fetchAll(PDO::FETCH_COLUMN);

// where
$where = "a.empresa_id=? AND a.created_at >= ? AND a.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
$params = [$empresa_id, $desde, $hasta];

if ($modulo !== '') { $where .= " AND a.modulo=?"; $params[] = $modulo; }
if ($accion !== '') { $where .= " AND a.accion=?"; $params[] = $accion; }
if ($usuario > 0) { $where .= " AND a.usuario_id=?"; $params[] = $usuario; }
if ($texto !== '') {
  $where .= " AND (a.tabla_nombre LIKE ? OR CAST(a.registro_id AS CHAR) LIKE ? OR a.ip LIKE ? OR a.user_agent LIKE ? OR a.antes_json LIKE ? OR a.despues_json LIKE ?)";
  $like = "%$texto%";
  $params = array_merge($params, [$like,$like,$like,$like,$like,$like]);
}

$rows = $pdo->prepare("
  SELECT
    a.id,
    a.created_at,
    a.modulo,
    a.accion,
    a.tabla_nombre,
    a.registro_id,
    a.usuario_id,
    u.nombre AS usuario,
    u.email AS email,
    a.ip,
    a.user_agent,
    a.antes_json,
    a.despues_json
  FROM auditoria a
  LEFT JOIN usuarios u ON u.id=a.usuario_id
  WHERE $where
  ORDER BY a.id DESC
  LIMIT $limit
");
$rows->execute($params);
$data = $rows->fetchAll(PDO::FETCH_ASSOC);

$total = count($data);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Auditoría | FAC-IL-CR</title>
<style>
:root{
  --azul:#0b5ed7; --azul-metal:#084298;
  --amarillo:#ffc107; --amarillo-metal:#ffca2c;
  --fondo:#071225; --card:rgba(17,24,39,.78);
  --borde:rgba(255,255,255,.12); --txt:#e5e7eb; --muted:#a7b0c2;
  --ok:#22c55e; --bad:#ef4444;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{
  margin:0;color:var(--txt);
  background:
    radial-gradient(1000px 680px at 12% 18%, rgba(11,94,215,.52), transparent 62%),
    radial-gradient(1000px 680px at 88% 24%, rgba(255,193,7,.22), transparent 60%),
    linear-gradient(180deg,#020617,var(--fondo));
  min-height:100vh;
}
.header{
  display:flex;align-items:center;justify-content:space-between;gap:14px;
  padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08);
  background:linear-gradient(180deg, rgba(8,66,152,.65), rgba(2,6,23,.25));
  position:sticky;top:0;backdrop-filter: blur(12px); z-index:60;
}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
.pill{padding:7px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-size:12px;font-weight:900;color:#fff}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04));
  color:var(--txt);font-weight:1000;cursor:pointer;text-decoration:none;
}
.btn.primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));border-color:rgba(11,94,215,.45)}
.btn.warn{background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));border-color:rgba(255,193,7,.55);color:#111827}
.wrap{max-width:1500px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr));}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
.input, select{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
.input:focus, select:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;position:sticky;top:0;background:rgba(2,6,23,.75);backdrop-filter: blur(10px)}
.right{text-align:right}
.tag{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);font-weight:900;font-size:12px;white-space:nowrap}
.tag.ok{border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.12)}
.tag.warn{border-color:rgba(255,193,7,.55);background:rgba(255,193,7,.14);color:#111827}
.tag.bad{border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12)}
.notice{padding:10px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.notice.err{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
details{border:1px solid rgba(255,255,255,.10);border-radius:14px;background:rgba(255,255,255,.04);padding:10px}
summary{cursor:pointer;font-weight:1000}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
</style>
</head>
<body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Auditoría</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn primary" href="reportes.php">📊 Reportes</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Logs & Cambios (solo lectura)</div>
        <div class="small">Se lee <b>directo de la tabla auditoria</b>. Filtrá por fecha/módulo/acción/usuario o texto (tabla, ID, IP).</div>
      </div>
      <div class="tag ok">● Registros: <?= (int)$total ?></div>
    </div>
    <div class="bd">
      <form method="get" class="grid">
        <div style="grid-column: span 2;">
          <div class="label">Desde</div>
          <input class="input" type="date" name="desde" value="<?=h($desde)?>">
        </div>
        <div style="grid-column: span 2;">
          <div class="label">Hasta</div>
          <input class="input" type="date" name="hasta" value="<?=h($hasta)?>">
        </div>

        <div style="grid-column: span 2;">
          <div class="label">Módulo</div>
          <select class="input" name="modulo">
            <option value="">Todos</option>
            <?php foreach($modulos as $m): ?>
              <option value="<?=h($m)?>" <?=$modulo===$m?'selected':''?>><?=h($m)?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: span 2;">
          <div class="label">Acción</div>
          <select class="input" name="accion">
            <option value="">Todas</option>
            <?php foreach($acciones as $a): ?>
              <option value="<?=h($a)?>" <?=$accion===$a?'selected':''?>><?=h($a)?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: span 3;">
          <div class="label">Usuario</div>
          <select class="input" name="usuario_id">
            <option value="0">Todos</option>
            <?php foreach($usuarios as $u): ?>
              <option value="<?=$u['id']?>" <?=$usuario===(int)$u['id']?'selected':''?>><?=h($u['nombre'])?> (<?=h($u['email'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: span 3;">
          <div class="label">Buscar (tabla, ID, IP, UA, JSON)</div>
          <input class="input" type="text" name="q" value="<?=h($texto)?>" placeholder="ej: ventas / 123 / 192.168...">
        </div>

        <div style="grid-column: span 2;">
          <div class="label">Límite</div>
          <select class="input" name="limit">
            <?php foreach([100,200,300,500,1000] as $l): ?>
              <option value="<?=$l?>" <?=$limit===$l?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: span 12; display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; margin-top:8px">
          <button class="btn primary" type="submit">🔎 Aplicar</button>
          <a class="btn" href="auditoria.php">Limpiar</a>
          <a class="btn warn" href="auditoria.php?desde=<?=date('Y-m-d')?>&hasta=<?=date('Y-m-d')?>">Hoy</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <div class="hd">
      <div style="font-weight:1000;font-size:18px">Últimos cambios</div>
      <div class="small">Click en “Antes / Después” para ver el JSON</div>
    </div>
    <div class="bd" style="overflow:auto; max-height:70vh">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Módulo</th>
            <th>Acción</th>
            <th>Tabla</th>
            <th>Registro</th>
            <th>Usuario</th>
            <th>IP</th>
            <th>Antes / Después</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($data as $r): ?>
          <tr>
            <td class="mono"><?= (int)$r['id'] ?></td>
            <td class="mono"><?= h($r['created_at']) ?></td>
            <td><span class="tag"><?= h($r['modulo']) ?></span></td>
            <td><span class="tag <?= $r['accion']==='ELIMINAR'?'bad':'ok' ?>"><?= h($r['accion']) ?></span></td>
            <td class="mono"><?= h($r['tabla_nombre'] ?? '') ?></td>
            <td class="mono"><?= h($r['registro_id'] ?? '') ?></td>
            <td>
              <b><?= h($r['usuario'] ?? 'Sistema') ?></b>
              <div class="small"><?= h($r['email'] ?? '') ?></div>
            </td>
            <td class="mono"><?= h($r['ip'] ?? '') ?></td>
            <td style="min-width:360px">
              <details>
                <summary>Antes</summary>
                <pre class="mono" style="white-space:pre-wrap;margin:10px 0 0"><?= h($r['antes_json'] ?? '') ?></pre>
              </details>
              <div style="height:8px"></div>
              <details>
                <summary>Después</summary>
                <pre class="mono" style="white-space:pre-wrap;margin:10px 0 0"><?= h($r['despues_json'] ?? '') ?></pre>
              </details>
              <div class="small" style="margin-top:8px">UA: <?= h($r['user_agent'] ?? '') ?></div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(count($data)===0): ?>
          <tr><td colspan="9" class="small">Sin registros para los filtros actuales.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="notice" style="margin-top:12px">
    <b>Desarollado por Sistemas03ilcr</b> Correo: Sistemas03il@outlook.com <b>Telefono: 6452-0450
  </div>

</div>
</body>
</html>
