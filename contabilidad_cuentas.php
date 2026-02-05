<?php
session_start(); require_once "config/db.php";
if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);
require_once "contabilidad_helpers.php";

$q = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? 'TODOS';
$estado = $_GET['estado'] ?? '1';

$where="empresa_id=?";
$params=[$empresa_id];
if($q!==''){ $where.=" AND (codigo LIKE ? OR nombre LIKE ?)"; $like="%$q%"; $params=array_merge($params,[$like,$like]); }
if($tipo!=='TODOS'){ $where.=" AND tipo=?"; $params[]=$tipo; }
if($estado!=='TODOS'){ $where.=" AND estado=?"; $params[]=(int)$estado; }

$st=$pdo->prepare("SELECT * FROM cont_cuentas WHERE $where ORDER BY codigo ASC LIMIT 2000");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Catálogo de Cuentas</title>
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
.btn.bad{background:linear-gradient(180deg,rgba(239,68,68,.9),rgba(239,68,68,.55));border-color:rgba(239,68,68,.55);color:#450a0a}
.wrap{max-width:1500px;margin:auto;padding:14px}
.card{background:var(--card);border:1px solid var(--borde);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.45);overflow:hidden}
.card .hd{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center}
.card .bd{padding:14px}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr));}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
.input, select, textarea{
  width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);
  background:rgba(2,6,23,.45);color:var(--txt);outline:none;
}
textarea{min-height:90px;resize:vertical}
.input:focus, select:focus, textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
@media(max-width:1100px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:640px){.kpis{grid-template-columns:1fr;}}
.kpi{padding:14px;border-radius:18px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05)}
.kpi .t{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.10em}
.kpi .n{font-size:24px;font-weight:1000;margin-top:6px}
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
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.suggest{position:relative}
.slist{
  position:absolute;left:0;right:0;top:100%;margin-top:6px;z-index:80;
  border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden;
  background:rgba(2,6,23,.92);backdrop-filter: blur(12px);display:none;max-height:320px;overflow:auto;
}
.sitem{padding:10px 12px;display:flex;justify-content:space-between;gap:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.08)}
.sitem:hover{background:rgba(255,255,255,.06)}
</style>
</head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">Catálogo</span></div>
  <div class="actions">
    <a class="btn" href="contabilidad.php">← Contabilidad</a>
    <a class="btn warn" href="contabilidad_cuentas_nuevo.php">➕ Nueva cuenta</a>
  </div>
</div>

<div class="wrap">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:1000;font-size:18px">Catálogo de cuentas</div>
        <div class="small">Solo cuentas activas y con “permite movimientos” se usan en asientos.</div>
      </div>
      <div class="small">Cuentas: <?=count($rows)?></div>
    </div>
    <div class="bd">
      <form method="get" class="grid">
        <div style="grid-column:span 4"><div class="label">Buscar</div><input class="input" name="q" value="<?=h($q)?>" placeholder="Código o nombre..."></div>
        <div style="grid-column:span 3"><div class="label">Tipo</div>
          <select class="input" name="tipo">
            <option value="TODOS" <?=$tipo==='TODOS'?'selected':''?>>Todos</option>
            <?php foreach(['ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO'] as $t): ?>
              <option value="<?=$t?>" <?=$tipo===$t?'selected':''?>><?=$t?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="grid-column:span 2"><div class="label">Estado</div>
          <select class="input" name="estado">
            <option value="TODOS" <?=$estado==='TODOS'?'selected':''?>>Todos</option>
            <option value="1" <?=$estado==='1'?'selected':''?>>Activas</option>
            <option value="0" <?=$estado==='0'?'selected':''?>>Inactivas</option>
          </select>
        </div>
        <div style="grid-column:span 3;display:flex;gap:10px;align-items:end;justify-content:flex-end">
          <button class="btn primary" type="submit">🔎 Aplicar</button>
          <a class="btn" href="contabilidad_cuentas.php">Limpiar</a>
        </div>
      </form>

      <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
        <table class="table">
          <thead><tr>
            <th>Código</th><th>Nombre</th><th>Tipo</th><th>Mov.</th><th>Estado</th><th class="right">Acciones</th>
          </tr></thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td class="mono"><b><?=h($r['codigo'])?></b></td>
                <td><?=h($r['nombre'])?></td>
                <td><span class="tag"><?=h($r['tipo'])?></span></td>
                <td><?= ((int)$r['permite_mov']===1) ? '<span class="tag ok">Sí</span>' : '<span class="tag warn">No</span>' ?></td>
                <td><?= ((int)$r['estado']===1) ? '<span class="tag ok">Activa</span>' : '<span class="tag bad">Inactiva</span>' ?></td>
                <td class="right">
                  <div class="actions">
                    <a class="btn" href="contabilidad_cuentas_editar.php?id=<?=$r['id']?>">✏️ Editar</a>
                    <a class="btn bad" href="contabilidad_cuentas_eliminar.php?id=<?=$r['id']?>" onclick="return confirm('¿Inactivar cuenta?');">🗑️ Inactivar</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(count($rows)===0): ?><tr><td colspan="6" class="small">Sin resultados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body></html>
