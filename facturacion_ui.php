
<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function facil_wrap_page($title, $pill, $content_html, $active='facturacion') {
  $css = <<<'CSS'


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


CSS;
  ob_start();
  ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($title) ?></title>
  <style><?= $css ?></style>
</head>
<body>
  <div class="container">
    <div class="shell">
      <div class="sidebar">
        <div class="brand"><span class="dot"></span> FAC-IL-CR</div>
        <div class="sub">ERP + Facturación CR • Multiempresa</div>
        <div style="height:12px"></div>
        <div class="nav">
          <a class="btn" href="dashboard.php">← Volver <span class="k">Dashboard</span></a>
        </div>
        <div style="height:14px"></div>
        <div class="small">Factura Electronica</div>
        <div style="font-weight:900"><?= h($_SESSION['usuario_nombre'] ?? 'Usuario') ?></div>
        <div class="small"><?= h($_SESSION['usuario_email'] ?? '') ?></div>
      </div>

      <div class="main">
        <div class="topbar">
          <div class="pill"><?= h($pill) ?></div>
          <div class="actions">
            <a class="btn" href="facturacion.php">← Lista</a>
            <a class="btn btn-primary" href="facturacion_nuevo.php">➕ Nuevo</a>
          </div>
        </div>
        <?= $content_html ?>
      </div>
    </div>
  </div>
</body>
</html>
  <?php
  return ob_get_clean();
}
?>
