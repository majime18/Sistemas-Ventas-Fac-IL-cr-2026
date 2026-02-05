<?php
// login.php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/config/db.php";

if (!empty($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

function ensure_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function audit(PDO $pdo, int $empresa_id, ?int $usuario_id, string $modulo, string $accion, string $descripcion, ?string $antes_json=null, ?string $despues_json=null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    try {
        $st = $pdo->prepare("INSERT INTO auditoria (empresa_id, usuario_id, modulo, accion, descripcion, antes_json, despues_json, ip, user_agent)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $st->execute([$empresa_id, $usuario_id, $modulo, $accion, $descripcion, $antes_json, $despues_json, $ip, $ua]);
    } catch (Throwable $e) {
        // No romper login si falta tabla o permisos
    }
}

$csrf = ensure_csrf();
$mensaje = "";
$tipo = "ok";

$MAX_INTENTOS = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $mensaje = "Solicitud inválida (CSRF).";
        $tipo = "err";
    } else {
        $ident = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        if ($ident === '' || $pass === '') {
            $mensaje = "Complete correo y contraseña.";
            $tipo = "warn";
        } else {
            $ident_lower = mb_strtolower($ident);
            // Permite iniciar sesión por email o por nombre de usuario (si no incluye @)
            if (strpos($ident_lower, '@') !== false) {
                $st = $pdo->prepare("SELECT id, empresa_id, sucursal_id, rol_id, nombre, email, password, estado, intentos_fallidos
                                     FROM usuarios WHERE LOWER(email) = ? LIMIT 1");
                $st->execute([$ident_lower]);
            } else {
                $st = $pdo->prepare("SELECT id, empresa_id, sucursal_id, rol_id, nombre, email, password, estado, intentos_fallidos
                                     FROM usuarios WHERE LOWER(email) = ? OR LOWER(nombre) = ? LIMIT 1");
                $st->execute([$ident_lower, $ident_lower]);
            }
            $u = $st->fetch();

            if (!$u) {
                audit($pdo, 1, null, "AUTH", "LOGIN_FAIL", "Usuario no existe: {$ident}");
                $mensaje = "Credenciales inválidas.";
                $tipo = "err";
            } else {
                $uid = (int)$u['id'];
                $empresa_id = (int)$u['empresa_id'];
                $estado = (int)$u['estado'];
                $intentos = (int)$u['intentos_fallidos'];

                if ($estado !== 1) {
                    audit($pdo, $empresa_id, $uid, "AUTH", "LOGIN_BLOCK", "Usuario inactivo");
                    $mensaje = "Usuario inactivo. Contacte al administrador.";
                    $tipo = "warn";
                } elseif ($intentos >= $MAX_INTENTOS) {
                    audit($pdo, $empresa_id, $uid, "AUTH", "LOGIN_BLOCK", "Bloqueado por intentos fallidos");
                    $mensaje = "Usuario bloqueado por seguridad. Contacte al administrador.";
                    $tipo = "err";
                } else {
                    if (password_verify($pass, (string)$u['password'])) {
                        // Reset intentos + último login
                        $upd = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, ultimo_login = NOW() WHERE id = ?");
                        $upd->execute([$uid]);

                        // Cargar permisos del rol (si existe tabla permisos)
                        $permisos = [];
                        try {
                            if (!empty($u['rol_id'])) {
                                $pr = $pdo->prepare("SELECT modulo, puede_ver, puede_crear, puede_editar, puede_eliminar
                                                     FROM permisos
                                                     WHERE empresa_id = ? AND rol_id = ?");
                                $pr->execute([$empresa_id, (int)$u['rol_id']]);
                                foreach ($pr->fetchAll() as $p) {
                                    $permisos[$p['modulo']] = [
                                        'ver' => (int)$p['puede_ver'],
                                        'crear' => (int)$p['puede_crear'],
                                        'editar' => (int)$p['puede_editar'],
                                        'eliminar' => (int)$p['puede_eliminar'],
                                    ];
                                }
                            }
                        } catch (Throwable $e) {
                            $permisos = [];
                        }

                        session_regenerate_id(true);

                        $_SESSION['usuario_id']   = $uid;

                        // ────────────────────────────────────────────────────────────────
                        // FALLBACK para empresa_id (evita error de null en ventas)
                        // Cambia el 1 por el ID real de tu empresa principal si es diferente
                        // ────────────────────────────────────────────────────────────────
                        if ($empresa_id <= 0) {
                            $empresa_id = 1;
                            // Opcional: actualizar el usuario en BD para evitar problemas futuros
                            $updEmp = $pdo->prepare("UPDATE usuarios SET empresa_id = ? WHERE id = ?");
                            $updEmp->execute([$empresa_id, $uid]);
                        }
                        $_SESSION['empresa_id'] = $empresa_id;
                        // ────────────────────────────────────────────────────────────────

                        // ────────────────────────────────────────────────────────────────
                        // FALLBACK para sucursal_id (evita error de foreign key en ventas)
                        // Cambia el 1 por el ID real de tu sucursal principal si es diferente
                        // ────────────────────────────────────────────────────────────────
                        $sucursal_id = (int)($u['sucursal_id'] ?? 0);
                        if ($sucursal_id <= 0) {
                            $sucursal_id = 1;
                            // Opcional: actualizar el usuario en BD para evitar problemas futuros
                            $updSuc = $pdo->prepare("UPDATE usuarios SET sucursal_id = ? WHERE id = ?");
                            $updSuc->execute([$sucursal_id, $uid]);
                        }
                        $_SESSION['sucursal_id'] = $sucursal_id;
                        // ────────────────────────────────────────────────────────────────

                        $_SESSION['rol_id']       = (int)($u['rol_id'] ?? 0);
                        $_SESSION['nombre']       = (string)$u['nombre'];
                        $_SESSION['email']        = (string)$u['email'];
                        $_SESSION['permisos']     = $permisos;

                        // Registrar login exitoso
                        audit($pdo, $empresa_id, $uid, "AUTH", "LOGIN_SUCCESS", "Inicio de sesión exitoso");

                        header("Location: dashboard.php");
                        exit;
                    } else {
                        // Incrementar intentos fallidos
                        $upd = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?");
                        $upd->execute([$uid]);

                        audit($pdo, $empresa_id, $uid, "AUTH", "LOGIN_FAIL", "Contraseña incorrecta");
                        $mensaje = "Credenciales inválidas.";
                        $tipo = "err";
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión | FAC-IL-CR</title>
<style>
:root {
  --azul:#0b5ed7; --azul-metal:#084298;
  --amarillo:#ffc107; --amarillo-metal:#ffca2c;
  --fondo:#071225; --card:rgba(17,24,39,.78);
  --borde:rgba(255,255,255,.12); --txt:#e5e7eb; --muted:#a7b0c2;
  --ok:#22c55e; --bad:#ef4444; --warn:#f59e0b; --err:#ef4444;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
body{
  margin:0;color:var(--txt);
  background: linear-gradient(180deg,#020617,var(--fondo));
  min-height:100vh;
}
.container{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
.wrap{width:min(980px,100%);display:grid;grid-template-columns:1.1fr .9fr;gap:16px}
.panel{
  border:1px solid var(--borde);
  background:linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.03));
  border-radius:18px;padding:22px;
  box-shadow:0 10px 30px rgba(0,0,0,.4);
}
.badge{display:inline-flex;align-items:center;gap:10px;padding:8px 12px;border-radius:999px;background:rgba(11,94,215,.15);border:1px solid rgba(11,94,215,.35);font-weight:1000}
.dot{width:10px;height:10px;border-radius:50%;background:var(--amarillo);box-shadow:0 0 0 5px rgba(255,193,7,.12)}
h1{margin:14px 0 8px;font-size:34px;line-height:1.1}
p{margin:0;color:var(--muted);line-height:1.55}
.kpis{margin-top:16px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.kpi{border:1px solid var(--borde);background:rgba(255,255,255,.04);border-radius:14px;padding:12px}
.kpi .t{font-size:12px;color:var(--muted);margin-bottom:6px}
.kpi .v{font-size:16px;font-weight:1000}
.btn{width:100%;padding:12px;border:none;border-radius:12px;font-weight:900;cursor:pointer}
.btn-primary{background:linear-gradient(180deg,var(--azul),var(--azul-metal));color:#fff}
.input{width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(10,15,28,.65);color:var(--txt)}
.alert{margin-top:12px;padding:10px;border-radius:12px}
.alert.ok{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4)}
.alert.err{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4)}
.alert.warn{background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.4)}
@media (max-width: 860px){.wrap{grid-template-columns:1fr}.kpis{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="container">
  <div class="wrap">
    <section class="panel">
      <span class="badge"><span class="dot"></span> FAC-IL-CR • ERP + Facturación CR</span>
      <h1>Control empresarial con auditoría y rentabilidad.</h1>
      <p>Acceso seguro por roles y permisos, listo para vender como SaaS o licencia empresarial.</p>
      <div class="kpis">
        <div class="kpi"><div class="t">Enfoque</div><div class="v">Rentabilidad</div></div>
        <div class="kpi"><div class="t">Cumplimiento</div><div class="v">Hacienda CR</div></div>
        <div class="kpi"><div class="t">Control</div><div class="v">Auditoría total</div></div>
      </div>
      <div class="alert warn" style="margin-top:14px">
        Usuario demo: <b>admin@facilcr.local</b> • Pass: <b>Admin1234!</b> (cambiar al instalar)
      </div>
    </section>

    <section class="panel">
      <div style="font-size:18px;font-weight:900">Iniciar sesión</div>
      <div style="color:var(--muted);margin-top:4px">Sesiones seguras + prepared statements + bloqueo por intentos.</div>

      <?php if ($mensaje): ?>
        <div class="alert <?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <form method="POST" style="margin-top:12px">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div style="margin-top:12px">
          <div style="font-weight:900;color:var(--muted)">Correo</div>
          <input class="input" type="email" name="email" placeholder="correo@empresa.com" required>
        </div>

        <div style="margin-top:12px">
          <div style="font-weight:900;color:var(--muted)">Contraseña</div>
          <input class="input" type="password" name="password" placeholder="••••••••" required>
        </div>

        <button class="btn btn-primary" type="submit" style="margin-top:14px">Entrar</button>

        <div class="small" style="margin-top:10px;color:var(--muted)">
          Si fallas muchos intentos, el usuario queda bloqueado por seguridad.
        </div>
      </form>
    </section>
  </div>
</div>
</body>
</html>