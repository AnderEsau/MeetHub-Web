<?php
/**
 * Restablecer contraseña (2 pasos por pregunta secreta) — Responsable: Ander
 * Paso 1: se escribe correo/matrícula/RFC/usuario. Paso 2: se responde la pregunta y se define la nueva contraseña.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/usuarios.php';

$error = '';
$cuenta = $_SESSION['recuperar'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'buscar') {
        $cuenta = buscar_cuenta_recuperacion($_POST['ident'] ?? '');
        if ($cuenta && $cuenta['pregunta'] !== '') {
            $_SESSION['recuperar'] = $cuenta;
        } else {
            unset($_SESSION['recuperar']);
            $cuenta = null;
            $error = 'No encontramos una cuenta con una pregunta de seguridad registrada para ese dato.';
        }
    } elseif ($accion === 'cambiar' && $cuenta) {
        $resp = trim($_POST['respuesta'] ?? '');
        $n1 = $_POST['nueva'] ?? '';
        $n2 = $_POST['confirmar'] ?? '';
        if ($resp === '' || $n1 === '' || $n2 === '') {
            $error = 'Completa todos los campos.';
        } elseif (mb_strtolower($resp) !== mb_strtolower((string) $cuenta['respuesta'])) {
            $error = 'La respuesta secreta no coincide.';
        } elseif (!password_valida($n1)) {
            $error = 'La contraseña debe tener mínimo 8 caracteres con letras y números.';
        } elseif ($n1 !== $n2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            cambiar_password($cuenta['tabla'], $cuenta['pk'], $cuenta['id'], $n1);
            unset($_SESSION['recuperar']);
            flash_set('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
            header('Location: ' . url('login.php'));
            exit;
        }
    } elseif ($accion === 'reiniciar') {
        unset($_SESSION['recuperar']);
        header('Location: ' . url('recuperar.php'));
        exit;
    }
}

$titulo = 'Restablecer contraseña';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-5">
    <div class="container" style="max-width: 520px;">
        <section class="mh-card p-4 p-md-5">
            <h1 class="h4 mh-title">Restablecer contraseña</h1>
            <?php flash_show(); ?>
            <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>

            <?php if (!$cuenta): ?>
                <p class="small mb-4">Paso 1 de 2 · Escribe tu correo, matrícula, RFC o usuario para verificar tu pregunta secreta.</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="accion" value="buscar">
                    <label class="form-label" for="ident">Correo electrónico, matrícula, RFC o usuario</label>
                    <input class="form-control mb-3" id="ident" name="ident" required>
                    <button class="btn btn-mh w-100 py-2" type="submit">Continuar</button>
                </form>
            <?php else: ?>
                <p class="small mb-3">Paso 2 de 2 · Responde tu pregunta de seguridad y define tu nueva contraseña.</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="accion" value="cambiar">
                    <div class="mh-tint p-3 small mb-3"><strong>Pregunta:</strong> <?= e($cuenta['pregunta']) ?></div>
                    <div class="mb-3">
                        <label class="form-label" for="respuesta">Respuesta secreta</label>
                        <input class="form-control" id="respuesta" name="respuesta" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nueva">Nueva contraseña</label>
                        <input type="password" class="form-control" id="nueva" name="nueva" required>
                        <div class="form-text">Mín. 8 caracteres, letras y números.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirmar">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" id="confirmar" name="confirmar" required>
                    </div>
                    <button class="btn btn-mh w-100 py-2 mb-2" type="submit">Restablecer contraseña</button>
                </form>
                <form method="POST"><input type="hidden" name="accion" value="reiniciar">
                    <button class="btn btn-link btn-sm w-100" type="submit">Usar otra cuenta</button></form>
            <?php endif; ?>
            <div class="text-center mt-3 small"><a href="<?= url('login.php') ?>">Volver a iniciar sesión</a></div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
