<?php
require_once 'includes/config.php';
if (isset($_SESSION['token'])) {
    $dest = (($_SESSION['rol'] ?? '') === 'ADMIN') ? '/documanager/dashboard.php' : '/documanager/documentos.php';
    header('Location: ' . $dest);
    exit;
}

$msg = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    $tipoCuenta = $_POST['tipoCuenta'] ?? 'regular';

    if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
        $error = 'Completa todos los campos para registrarte.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener mínimo 6 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $nombreRegistro = $nombre;
        $emailRegistro = $email;

        if ($tipoCuenta === 'presidencia') {
            if (!str_contains(strtolower($nombreRegistro), 'president')) {
                $nombreRegistro = 'Presidente - ' . $nombreRegistro;
            }
            if (!str_contains(strtolower($emailRegistro), 'president')) {
                $partes = explode('@', $emailRegistro, 2);
                if (count($partes) === 2) {
                    $emailRegistro = $partes[0] . '.president@' . $partes[1];
                }
            }
        }

        $res = api('POST', '/auth/register', [
            'nombre' => $nombreRegistro,
            'email' => $emailRegistro,
            'password' => $password,
            'rol' => 'VIEWER'
        ]);

        if ($res['code'] === 200 || $res['code'] === 201) {
            $msg = 'Cuenta creada correctamente. Ahora puedes iniciar sesión.';
        } else {
            $error = $res['data']['message'] ?? 'No se pudo registrar la cuenta. Verifica si el correo ya existe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — DocuManager</title>
    <link rel="stylesheet" href="<?= assetUrl('/documanager/css/style.css') ?>">
    <style>
        body {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
            background: var(--bg);
            background-image:
                radial-gradient(ellipse at 15% 50%, rgba(99,102,241,0.07) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 20%, rgba(99,102,241,0.04) 0%, transparent 45%);
        }
        .register-wrap { width: 100%; max-width: 480px; padding: 1rem; }
        .register-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.8rem;
        }
        .login-link { text-align:center; margin-top:1rem; color:var(--text-2); font-size:0.82rem; }
        .segmented { display:flex; gap:8px; margin-bottom:1rem; }
        .segmented label {
            flex:1; border:1px solid var(--border); border-radius:8px; padding:8px;
            font-size:0.8rem; color:var(--text-2); cursor:pointer; text-align:center;
            background: var(--bg-surface);
        }
        .segmented input { display:none; }
        .segmented input:checked + span { color: var(--text); font-weight: 600; }
        .segmented label:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
    </style>
</head>
<body>
<div class="register-wrap">
    <div class="register-card">
        <h2 style="margin-bottom:4px">Crear cuenta</h2>
        <p style="color:var(--text-2);font-size:0.84rem;margin-bottom:1rem">Registra un nuevo usuario para ingresar al sistema.</p>

        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="segmented">
                <label>
                    <input type="radio" name="tipoCuenta" value="regular" <?= (($_POST['tipoCuenta'] ?? 'regular') === 'regular') ? 'checked' : '' ?>>
                    <span>Usuario regular</span>
                </label>
                <label>
                    <input type="radio" name="tipoCuenta" value="presidencia" <?= (($_POST['tipoCuenta'] ?? '') === 'presidencia') ? 'checked' : '' ?>>
                    <span>Cuenta presidencia</span>
                </label>
            </div>

            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password2" class="form-control" required minlength="6">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Registrar cuenta</button>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="/documanager/index.php" style="color:var(--accent)">Inicia sesión aquí</a>.
        </div>
    </div>
</div>
</body>
</html>
