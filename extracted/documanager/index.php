<?php
require_once 'includes/config.php';
if (isset($_SESSION['token'])) {
    header('Location: /documanager/dashboard.php'); exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email && $password) {
        $res = api('POST', '/auth/login', ['email' => $email, 'password' => $password]);
        if ($res['code'] === 200 && isset($res['data']['token'])) {
            $_SESSION['token']  = $res['data']['token'];
            $_SESSION['nombre'] = $res['data']['nombre'];
            $_SESSION['email']  = $res['data']['email'];
            $_SESSION['rol']    = $res['data']['rol'];
            header('Location: /documanager/dashboard.php'); exit;
        } else {
            $error = $res['data']['message'] ?? 'Credenciales incorrectas. Verifica tu email y contraseña.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocuManager — Iniciar sesión</title>
    <link rel="stylesheet" href="/documanager/css/style.css">
    <style>
        body {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
            background: var(--bg);
            background-image:
                radial-gradient(ellipse at 15% 50%, rgba(99,102,241,0.07) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 20%, rgba(99,102,241,0.04) 0%, transparent 45%);
        }
        .login-wrap { width: 100%; max-width: 400px; padding: 1rem; }

        .logo {
            text-align: center; margin-bottom: 2rem;
        }
        .logo-icon {
            width: 60px; height: 60px; border-radius: 16px;
            background: var(--accent-soft); border: 1px solid rgba(99,102,241,0.3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.75rem; font-weight: 800;
        }
        .logo h1 span { color: var(--accent); }
        .logo p { color: var(--text-2); font-size: 0.85rem; margin-top: 4px; }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
        }
        .login-card h2 {
            font-size: 1rem; font-weight: 500;
            color: var(--text-2); margin-bottom: 1.5rem;
        }
        .btn-login {
            width: 100%; padding: 11px;
            background: var(--accent); color: #fff;
            border: none; border-radius: var(--radius-sm);
            font-family: 'Syne', sans-serif;
            font-size: 0.9rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s;
            letter-spacing: 0.02em;
        }
        .btn-login:hover {
            background: var(--accent-dark);
            box-shadow: 0 0 30px var(--accent-glow);
        }
        .login-footer {
            text-align: center; margin-top: 1.5rem;
            font-size: 0.75rem; color: var(--text-3);
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="logo">
        <div class="logo-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <rect x="3" y="1" width="14" height="19" rx="3" fill="#6366f1"/>
                <rect x="11" y="8" width="14" height="19" rx="3" fill="#818cf8" opacity="0.6"/>
                <path d="M6 8h8M6 12h6M6 16h4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <h1>Docu<span>Manager</span></h1>
        <p>Sistema de Control Documental</p>
    </div>

    <div class="login-card">
        <h2>Inicia sesión para continuar</h2>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="email" class="form-control"
                       placeholder="tu@correo.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group" style="margin-bottom:1.5rem">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Entrar al sistema</button>
        </form>
    </div>

    <div class="login-footer">
        DocuManager &copy; <?= date('Y') ?> &mdash; Todos los derechos reservados
    </div>
</div>
</body>
</html>
