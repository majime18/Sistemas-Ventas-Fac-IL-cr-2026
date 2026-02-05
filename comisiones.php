<?php
declare(strict_types=1);
session_start();
require_once __DIR__."/config/db.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($n){ return number_format((float)$n,2,',','.'); }
function col_exists(PDO $pdo,string $t,string $c):bool{
  $st=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute([$t,$c]); return ((int)$st->fetchColumn())>0;
}

if(empty($_SESSION['usuario_id'])){ header("Location: login.php"); exit; }
$empresa_id=(int)($_SESSION['empresa_id'] ?? 1);

$tab = $_GET['tab'] ?? 'calculo'; // calculo | reglas
$msg = $_GET['msg'] ?? '';

$usuarios = $pdo->prepare("SELECT id,nombre,email FROM usuarios ".(col_exists($pdo,'usuarios','empresa_id')?"WHERE empresa_id=?":"")." ORDER BY nombre");
if(col_exists($pdo,'usuarios','empresa_id')) $usuarios->execute([$empresa_id]); else $usuarios->execute();
$usuarios = $usuarios->fetchAll(PDO::FETCH_ASSOC);

$productos = $pdo->prepare("SELECT id,codigo,descripcion FROM productos ".(col_exists($pdo,'productos','empresa_id')?"WHERE empresa_id=?":"")." ORDER BY descripcion LIMIT 5000");
if(col_exists($pdo,'productos','empresa_id')) $productos->execute([$empresa_id]); else $productos->execute();
$productos = $productos->fetchAll(PDO::FETCH_ASSOC);

// ---------- REGLAS ----------
$tipo = $_GET['tipo'] ?? 'TODOS'; // POR_VENTA | POR_COBRO
$estado = $_GET['estado'] ?? 'TODOS'; // 1 | 0
$q = trim($_GET['q'] ?? '');

$whereR=["r.empresa_id=?"]; $paramsR=[$empresa_id];
if($tipo!=='TODOS'){ $whereR[]="r.tipo=?"; $paramsR[]=$tipo; }
if($estado!=='TODOS'){ $whereR[]="r.estado=?"; $paramsR[]=(int)($estado==='ACTIVO'); }
if($q!==''){ $whereR[]="(u.nombre LIKE ? OR p.descripcion LIKE ?)"; $like="%$q%"; $paramsR[]=$like; $paramsR[]=$like; }

$sqlR="SELECT r.id,r.usuario_id,r.producto_id,r.porcentaje,r.tipo,r.estado,r.created_at,
              u.nombre AS usuario, p.descripcion AS producto, p.codigo AS codigo
       FROM comisiones_reglas r
       LEFT JOIN usuarios u ON u.id=r.usuario_id
       LEFT JOIN productos p ON p.id=r.producto_id
       WHERE ".implode(" AND ",$whereR)." ORDER BY r.id DESC";
$stR=$pdo->prepare($sqlR); $stR->execute($paramsR); $reglas=$stR->fetchAll(PDO::FETCH_ASSOC);

// ---------- CALCULADAS ----------
$estC = $_GET['estado_c'] ?? 'PENDIENTE'; // PENDIENTE|PAGADA|ANULADA|TODOS
$userC = (int)($_GET['usuario_id'] ?? 0);
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

$whereC=["c.empresa_id=?"]; $paramsC=[$empresa_id];
if($estC!=='TODOS'){ $whereC[]="c.estado=?"; $paramsC[]=$estC; }
if($userC>0){ $whereC[]="c.usuario_id=?"; $paramsC[]=$userC; }
if($desde!==''){ $whereC[]="DATE(c.created_at) >= ?"; $paramsC[]=$desde; }
if($hasta!==''){ $whereC[]="DATE(c.created_at) <= ?"; $paramsC[]=$hasta; }

$sqlC="SELECT c.id,c.usuario_id,c.venta_id,c.pago_id,c.porcentaje,c.monto,c.estado,c.created_at,
              u.nombre AS usuario
       FROM comisiones_calculadas c
       LEFT JOIN usuarios u ON u.id=c.usuario_id
       WHERE ".implode(" AND ",$whereC)."
       ORDER BY c.id DESC LIMIT 5000";
$stC=$pdo->prepare($sqlC); $stC->execute($paramsC); $calc=$stC->fetchAll(PDO::FETCH_ASSOC);

$totPend=0; $totPag=0; $totAnu=0;
foreach($calc as $c){
  if($c['estado']==='PENDIENTE') $totPend += (float)$c['monto'];
  else if($c['estado']==='PAGADA') $totPag += (float)$c['monto'];
  else if($c['estado']==='ANULADA') $totAnu += (float)$c['monto'];
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comisiones | FAC-IL-CR</title><style>:root{--azul:#0b5ed7;--azul-metal:#084298;--amarillo:#ffc107;--amarillo-metal:#ffca2c;--fondo:#071225;--card:rgba(17,24,39,.78);--borde:rgba(255,255,255,.12);--txt:#e5e7eb;--muted:#a7b0c2;--ok:#22c55e;--bad:#ef4444;--info:#38bdf8}
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
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(6,minmax(0,1fr))}}
@media(max-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.input,select,textarea{width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(2,6,23,.45);color:var(--txt);outline:none}
.input:focus,select:focus,textarea:focus{border-color:rgba(255,193,7,.55);box-shadow:0 0 0 4px rgba(255,193,7,.12)}
.label{font-size:12px;color:var(--muted);margin:8px 0 6px}
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
hr.sep{border:0;border-top:1px solid rgba(255,255,255,.10);margin:14px 0}
</style></head><body>
<div class="header">
  <div class="brand"><span class="dot"></span> FAC-IL-CR <span class="pill">COMISIONES</span></div>
  <div class="actions">
    <a class="btn" href="dashboard.php">🏠 Dashboard</a>
    <a class="btn <?= $tab==='calculo'?'primary':''?>" href="comisiones.php?tab=calculo">📌 Calculadas</a>
    <a class="btn <?= $tab==='reglas'?'primary':''?>" href="comisiones.php?tab=reglas">⚙️ Reglas</a>
    <a class="btn warn" href="comisiones_nuevo.php">➕ Nueva regla</a>
  </div>
</div>

<div class="wrap">
  <?php if($msg): ?><div class="notice"><?= h($msg) ?></div><?php endif; ?>

  <?php if($tab==='calculo'): ?>
    <div class="card">
      <div class="hd">
        <div>
          <div style="font-weight:1000;font-size:18px">Comisiones calculadas</div>
          <div class="small">Pendientes de pago, pagadas y anuladas.</div>
        </div>
        <div class="actions">
          <span class="tag warn">Pend: ₡<?= nf($totPend) ?></span>
          <span class="tag ok">Pag: ₡<?= nf($totPag) ?></span>
          <span class="tag bad">Anu: ₡<?= nf($totAnu) ?></span>
        </div>
      </div>
      <div class="bd">
        <form class="grid" method="get">
          <input type="hidden" name="tab" value="calculo">
          <div style="grid-column:span 3">
            <div class="label">Estado</div>
            <select class="input" name="estado_c">
              <?php foreach(['PENDIENTE','PAGADA','ANULADA','TODOS'] as $e): ?>
                <option value="<?=$e?>" <?=$estC===$e?'selected':''?>><?=$e?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="grid-column:span 4">
            <div class="label">Usuario</div>
            <select class="input" name="usuario_id">
              <option value="0">Todos</option>
              <?php foreach($usuarios as $u): ?>
                <option value="<?=$u['id']?>" <?=$userC===(int)$u['id']?'selected':''?>><?=h($u['nombre'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="grid-column:span 2">
            <div class="label">Desde</div><input class="input" type="date" name="desde" value="<?=h($desde)?>">
          </div>
          <div style="grid-column:span 2">
            <div class="label">Hasta</div><input class="input" type="date" name="hasta" value="<?=h($hasta)?>">
          </div>
          <div style="grid-column:span 1;display:flex;align-items:end;justify-content:flex-end">
            <button class="btn primary" type="submit">🔎</button>
          </div>
        </form>

        <form method="post" action="comisiones_pagar.php" onsubmit="return confirm('¿Marcar como PAGADA las comisiones seleccionadas?');">
          <div class="actions" style="margin-top:12px">
            <input type="hidden" name="back" value="comisiones.php?tab=calculo&estado_c=<?=h($estC)?>&usuario_id=<?= (int)$userC ?>&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>">
            <button class="btn warn" type="submit" name="accion" value="pagar">💰 Pagar seleccionadas</button>
            <button class="btn danger" type="submit" name="accion" value="anular">🧾 Anular seleccionadas</button>
          </div>

          <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
            <table class="table">
              <thead>
                <tr>
                  <th><input type="checkbox" onclick="document.querySelectorAll('.ck').forEach(x=>x.checked=this.checked)"></th>
                  <th>ID</th>
                  <th>Usuario</th>
                  <th>Origen</th>
                  <th class="right">%</th>
                  <th class="right">Monto</th>
                  <th>Estado</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($calc as $c):
                  $tag = $c['estado']==='PAGADA'?'ok':($c['estado']==='ANULADA'?'bad':'warn');
                  $origen = $c['venta_id'] ? ("Venta #".$c['venta_id']) : ($c['pago_id']?("Cobro #".$c['pago_id']):"—");
                ?>
                  <tr>
                    <td>
                      <?php if($c['estado']==='PENDIENTE'): ?>
                        <input class="ck" type="checkbox" name="ids[]" value="<?= (int)$c['id'] ?>">
                      <?php endif; ?>
                    </td>
                    <td class="mono"><b><?= (int)$c['id'] ?></b></td>
                    <td><?= h($c['usuario'] ?? '—') ?></td>
                    <td class="small mono"><?= h($origen) ?></td>
                    <td class="right mono"><?= nf($c['porcentaje']) ?></td>
                    <td class="right mono"><b>₡<?= nf($c['monto']) ?></b></td>
                    <td><span class="tag <?=$tag?>"><?= h($c['estado']) ?></span></td>
                    <td class="small mono"><?= h($c['created_at']) ?></td>
                  </tr>
                <?php endforeach; if(count($calc)===0): ?>
                  <tr><td colspan="8" class="small">Sin registros.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="hd">
        <div>
          <div style="font-weight:1000;font-size:18px">Reglas de comisión</div>
          <div class="small">Define porcentajes por usuario / producto. Tipo por venta o por cobro.</div>
        </div>
        <div class="actions">
          <a class="btn warn" href="comisiones_nuevo.php">➕ Nueva regla</a>
        </div>
      </div>
      <div class="bd">
        <form class="grid" method="get">
          <input type="hidden" name="tab" value="reglas">
          <div style="grid-column:span 6">
            <div class="label">Buscar</div>
            <input class="input" name="q" value="<?=h($q)?>" placeholder="Vendedor o producto...">
          </div>
          <div style="grid-column:span 3">
            <div class="label">Tipo</div>
            <select class="input" name="tipo">
              <option value="TODOS" <?=$tipo==='TODOS'?'selected':''?>>Todos</option>
              <option value="POR_VENTA" <?=$tipo==='POR_VENTA'?'selected':''?>>POR_VENTA</option>
              <option value="POR_COBRO" <?=$tipo==='POR_COBRO'?'selected':''?>>POR_COBRO</option>
            </select>
          </div>
          <div style="grid-column:span 2">
            <div class="label">Estado</div>
            <select class="input" name="estado">
              <option value="TODOS" <?=$estado==='TODOS'?'selected':''?>>Todos</option>
              <option value="ACTIVO" <?=$estado==='ACTIVO'?'selected':''?>>Activas</option>
              <option value="INACTIVO" <?=$estado==='INACTIVO'?'selected':''?>>Inactivas</option>
            </select>
          </div>
          <div style="grid-column:span 1;display:flex;align-items:end;justify-content:flex-end">
            <button class="btn primary" type="submit">🔎</button>
          </div>
        </form>

        <div style="margin-top:12px;overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:16px">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th><th>Usuario</th><th>Producto</th><th>Tipo</th><th class="right">%</th><th>Estado</th><th>Creada</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($reglas as $r):
                $tag=((int)$r['estado']===1)?'ok':'bad';
                $u = $r['usuario_id'] ? ($r['usuario'] ?? '—') : 'CUALQUIER USUARIO';
                $p = $r['producto_id'] ? (($r['codigo']?($r['codigo'].' - '):'').($r['producto'] ?? '—')) : 'CUALQUIER PRODUCTO';
              ?>
                <tr>
                  <td class="mono"><b><?= (int)$r['id'] ?></b></td>
                  <td><?= h($u) ?></td>
                  <td><?= h($p) ?></td>
                  <td class="mono small"><?= h($r['tipo']) ?></td>
                  <td class="right mono"><b><?= nf($r['porcentaje']) ?></b></td>
                  <td><span class="tag <?=$tag?>"><?= ((int)$r['estado']===1)?'ACTIVA':'INACTIVA' ?></span></td>
                  <td class="small mono"><?= h($r['created_at']) ?></td>
                </tr>
              <?php endforeach; if(count($reglas)===0): ?>
                <tr><td colspan="7" class="small">Sin reglas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  <?php endif; ?>
</div>
</body></html>
