<?php
// dashboard.php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/config/db.php";

if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = (int)($_SESSION['empresa_id'] ?? 0);
$nombre = (string)($_SESSION['nombre'] ?? 'Usuario');

// Permisos
function can(string $modulo, string $accion): bool {
    $perms = $_SESSION['permisos'] ?? [];
    if (!is_array($perms)) return true; // fallback
    if (!isset($perms[$modulo])) return true; // si no hay permisos cargados, no bloqueamos demo
    $map = ['ver'=>'ver','crear'=>'crear','editar'=>'editar','eliminar'=>'eliminar'];
    $a = $map[$accion] ?? $accion;
    return !empty($perms[$modulo][$a]);
}

if (!can('dashboard','ver')) {
    http_response_code(403);
    $forbidden = true;
} else {
    $forbidden = false;
}

function scalar(PDO $pdo, string $sql, array $params): float {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v ? (float)$v : 0.0;
    } catch (Throwable $e) {
        return 0.0;
    }
}

$ventas_hoy = $forbidden ? 0 : scalar($pdo,
    "SELECT COALESCE(SUM(total),0) FROM ventas WHERE empresa_id=? AND estado <> 'ANULADA' AND DATE(created_at)=CURDATE()",
    [$empresa_id]
);
$ventas_mes = $forbidden ? 0 : scalar($pdo,
    "SELECT COALESCE(SUM(total),0) FROM ventas WHERE empresa_id=? AND estado <> 'ANULADA' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())",
    [$empresa_id]
);

$cxc_pendiente = $forbidden ? 0 : scalar($pdo,
    "SELECT COALESCE(SUM(saldo),0) FROM cxc_documentos WHERE empresa_id=? AND estado IN ('PENDIENTE','VENCIDO')",
    [$empresa_id]
);

$fe_pendiente = $forbidden ? 0 : scalar($pdo,
    "SELECT COALESCE(COUNT(*),0) FROM fe_documentos WHERE empresa_id=? AND estado='PENDIENTE'",
    [$empresa_id]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FAC-IL-CR • Dashboard</title>
<style>

:root{
  --azul:#0b5ed7;
  --azul-metal:#084298;
  --amarillo:#ffc107;
  --amarillo-metal:#ffca2c;
  --fondo:#0b1220;
  --panel:rgba(255,255,255,.06);
  --borde:rgba(255,255,255,.10);
  --texto:#e5e7eb;
  --muted:rgba(229,231,235,.70);
  --shadow:0 18px 60px rgba(0,0,0,.55);
  --ok:rgba(34,197,94,.22);
  --warn:rgba(255,193,7,.16);
  --err:rgba(239,68,68,.16);
}
*{box-sizing:border-box}
body{
  margin:0; min-height:100vh;
  font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
  color:var(--texto);
  background:
    radial-gradient(1200px 600px at 15% 15%, rgba(11,94,215,.35), transparent 60%),
    radial-gradient(900px 500px at 85% 20%, rgba(255,193,7,.18), transparent 60%),
    linear-gradient(180deg, #070b14, var(--fondo));
}
a{color:inherit}
.container{width:min(1150px,100%); margin:0 auto; padding:22px}
.shell{display:grid; grid-template-columns: 265px 1fr; gap:16px; align-items:start}
.sidebar{
  position:sticky; top:16px;
  border:1px solid var(--borde);
  background:linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.03));
  border-radius:18px;
  padding:16px;
  box-shadow:var(--shadow);
}
.brand{display:flex; align-items:center; gap:10px; font-weight:1000; letter-spacing:.4px}
.dot{width:10px;height:10px;border-radius:999px;background:linear-gradient(180deg,var(--amarillo),#ffe08a);box-shadow:0 0 0 4px rgba(255,193,7,.15)}
.sub{margin-top:6px; color:var(--muted); font-size:12px}
.nav{margin-top:14px; display:flex; flex-direction:column; gap:8px}
.nav a{
  text-decoration:none;
  padding:10px 12px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.05);
  display:flex; justify-content:space-between; align-items:center;
  font-weight:900;
}
.nav a:hover{filter:brightness(1.06)}
.pill{
  font-size:12px; font-weight:1000;
  padding:4px 8px; border-radius:999px;
  background:rgba(255,193,7,.14);
  border:1px solid rgba(255,193,7,.35);
}
.main{
  border:1px solid var(--borde);
  background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  border-radius:18px;
  padding:18px;
  box-shadow:var(--shadow);
  overflow:hidden;
}
.topbar{display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap}
.h1{margin:0; font-size:22px; font-weight:1000}
.p{margin:6px 0 0; color:var(--muted); line-height:1.5}
.actions{display:flex; gap:10px; flex-wrap:wrap}
.btn{
  border:0; cursor:pointer;
  border-radius:14px;
  padding:10px 12px;
  font-weight:1000;
  text-decoration:none;
  display:inline-flex; align-items:center; gap:8px;
}
.btn-primary{color:#fff;background:linear-gradient(180deg,var(--azul),var(--azul-metal));box-shadow:0 10px 24px rgba(11,94,215,.25)}
.btn-warning{color:#111827;background:linear-gradient(180deg,var(--amarillo),var(--amarillo-metal));box-shadow:0 10px 24px rgba(255,193,7,.22)}
.grid{margin-top:14px; display:grid; grid-template-columns: repeat(3,1fr); gap:12px}
.card{
  border:1px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.05);
  border-radius:16px;
  padding:14px;
}
.k{font-size:12px; color:var(--muted); font-weight:1000; letter-spacing:.3px; text-transform:uppercase}
.v{margin-top:8px; font-size:26px; font-weight:1000}
.small{margin-top:6px; color:var(--muted); font-size:12px}
.table{
  width:100%;
  border-collapse:separate; border-spacing:0;
  border:1px solid rgba(255,255,255,.10);
  border-radius:16px; overflow:hidden;
  margin-top:12px;
  background:rgba(10,15,28,.55);
}
.table th,.table td{padding:12px; border-bottom:1px solid rgba(255,255,255,.10); text-align:left}
.table th{
  background:rgba(255,255,255,.06);
  font-size:12px; color:rgba(229,231,235,.85);
  letter-spacing:.35px; text-transform:uppercase;
}
.table tr:last-child td{border-bottom:0}
.input, .select{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.12);
  outline:none;
  background:rgba(10,15,28,.65);
  color:var(--texto);
}
.input:focus, .select:focus{border-color:rgba(11,94,215,.70); box-shadow:0 0 0 4px rgba(11,94,215,.18)}
.alert{
  margin-top:12px;
  border-radius:14px;
  padding:10px 12px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.05);
}
.alert.ok{background:var(--ok); border-color:rgba(34,197,94,.35)}
.alert.warn{background:var(--warn); border-color:rgba(255,193,7,.45)}
.alert.err{background:var(--err); border-color:rgba(239,68,68,.45)}
hr{border:0; border-top:1px solid rgba(255,255,255,.10); margin:14px 0}
@media (max-width: 980px){
  .shell{grid-template-columns:1fr}
  .sidebar{position:relative; top:auto}
  .grid{grid-template-columns:1fr}
}

</style>
</head>
<body>
  <div class="container">
    <div class="shell">
      <aside class="sidebar">
        <div class="brand"><span class="dot"></span> FAC-IL-CR</div>
        <div class="sub">Desarollado por Sistemas03il , Telefono: 64520450</div>

        <nav class="nav">
          <a href="dashboard.php">Dashboard <span class="pill">KPIs</span></a>
          <a href="reportes.php">Reportes <span class="pill">BI</span></a>
          <a href="empresas.php">Empresas <span class="pill">CRUD</span></a>
          <a href="usuarios.php">Usuarios <span class="pill">RBAC</span></a>
          <a href="productos.php">Productos <span class="pill">CABYS</span></a>
          <a href="bodegas.php">Bodegas <span class="pill">CB</span></a>
          <a href="inventario.php">Inventario <span class="pill">KDX</span></a>
          <a href="clientes.php">Clientes <span class="pill">CRÉD</span></a>
          <a href="ventas.php">Ventas <span class="pill">POS</span></a>
          <a href="facturacion.php">Facturación <span class="pill">MH</span></a>
          <a href="cxc.php">CXC <span class="pill">₡</span></a>
          <a href="cxp.php">CXP <span class="pill">₡</span></a>
          <a href="proveedores.php">Proveedores <span class="pill">Prov</span></a>
          <a href="contabilidad.php">Contabilidad <span class="pill">NIIF</span></a>
          <a href="comisiones.php">Comisiones <span class="pill">%</span></a>
          <a href="auditoria.php">Auditoría <span class="pill">LOG</span></a>

          <a href="logout.php" style="border-color:rgba(255,193,7,.35);background:rgba(255,193,7,.10)">Salir <span class="pill">↩</span></a>
        </nav>

        <hr>
        <div class="small">Sesión</div>
        <div style="font-weight:1000;margin-top:6px"><?= htmlspecialchars($nombre) ?></div>
        <div class="small"><?= htmlspecialchars((string)($_SESSION['email'] ?? '')) ?></div>
      </aside>

      <main class="main">
        <div class="topbar">
          <div>
            <h1 class="h1">Dashboard</h1>
            <p class="p">Indicadores rápidos para control, auditoría y rentabilidad.</p>
          </div>
          <div class="actions">
            <a class="btn btn-primary" href="reportes.php">Ver reportes</a>
            <a class="btn btn-warning" href="#" onclick="alert('Acción crítica (demo).');return false;">Acción crítica</a>
          </div>
        </div>

        <?php if ($forbidden): ?>
          <div class="alert err">
            No tenés permiso para ver el dashboard. (Permiso: <b>dashboard.puede_ver</b>)
          </div>
        <?php else: ?>
          <section class="grid">
            <div class="card">
              <div class="k">Ventas hoy</div>
              <div class="v">₡<?= number_format($ventas_hoy, 2) ?></div>
              <div class="small">Total de ventas registradas hoy (sin anuladas).</div>
            </div>
            <div class="card">
              <div class="k">Ventas del mes</div>
              <div class="v">₡<?= number_format($ventas_mes, 2) ?></div>
              <div class="small">Acumulado del mes actual.</div>
            </div>
            <div class="card">
              <div class="k">CxC pendiente/vencida</div>
              <div class="v">₡<?= number_format($cxc_pendiente, 2) ?></div>
              <div class="small">Saldo abierto en cuentas por cobrar.</div>
            </div>
          </section>

          <section class="grid" style="margin-top:12px">
            <div class="card">
              <div class="k">FE pendientes</div>
              <div class="v"><?= number_format($fe_pendiente, 0) ?></div>
              <div class="small">Documentos en cola para Hacienda.</div>
            </div>
            <div class="card">
              <div class="k">Auditoría</div>
              <div class="v">Activa</div>
              <div class="small">Cambios y accesos quedan registrados.</div>
            </div>
            <div class="card">
              <div class="k">Estado</div>
              <div class="v">Operativo</div>
              <div class="small">Base lista para módulos CRUD.</div>
            </div>
          </section>

          <hr>

          <div class="k">Atajos</div>
          <table class="table">
            <thead>
              <tr><th>Módulo</th><th>Descripción</th><th>Acción</th></tr>
            </thead>
            <tbody>
              <tr>
                <td>Reportes</td>
                <td>Filtros por fechas, ventas y cuentas por cobrar.</td>
                <td><a class="btn btn-primary" href="reportes.php" style="padding:8px 10px;border-radius:12px">Abrir</a></td>
              </tr>
              <tr>
                <td>Seguridad</td>
                <td>Login con bloqueo por intentos + permisos por rol.</td>
                <td><span class="pill">Listo</span></td>
              </tr>
              <tr>
                <td>CRUD</td>
                <td>Empresas, usuarios, productos, inventario, etc.</td>
                <td><span class="pill">Siguiente</span></td>
              </tr>
            </tbody>
          </table>
        <?php endif; ?>
      </main>
    </div>
  </div>
    <div class="notice" style="margin-top:12px">
    <b>Desarollado por Sistemas03ilcr</b> Correo: Sistemas03il@outlook.com <b>Telefono: 6452-0450
  </div>
</body>
</html>
