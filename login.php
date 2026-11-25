<?php
/**
 * Iniciar sesión — Responsable: Ander
 * Tablas: tblalumnos / tbltrabajadores / tblexterno / tblbibliotecario
 *
 * Esta pantalla SOLO inicia sesión (la creación de cuenta está aparte en registro.php).
 * El login es el mismo para todos: según el rol que regrese la BD se redirige y el menú
 * (includes/menu.php) cambia solo.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/usuarios.php';

if (usuario_logueado()) {
    header('Location: ' . pagina_inicio_por_rol(rol_actual()));
    exit;
}

$errorLogin = '';
$ident = '';

if (isset($_GET['error'])) {
    $errorLogin = $_GET['error'] === 'sin_permiso'
        ? 'Tu cuenta no tiene permiso para entrar a esa sección.'
        : 'Debes iniciar sesión para acceder a esa sección.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ident = trim($_POST['ident'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($ident === '' || $password === '') {
        $errorLogin = 'Por favor llena todos los campos.';
    } else {
        $u = buscar_usuario_por_identificador($ident);
        if ($u && verificar_password($password, $u['password'])) {
            iniciar_sesion_usuario((int) $u['id'], $u['nombre'], $u['rol'], $u['tipo']);
            header('Location: ' . pagina_inicio_por_rol($u['rol']));
            exit;
        }
        $errorLogin = 'Credenciales incorrectas.';
    }
}

$titulo = 'Iniciar sesión';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-5">
    <div class="container" style="max-width: 440px;">
        <section class="mh-card p-4 p-md-5">
            <h1 class="h4 mh-title">Iniciar sesión</h1>
            <p class="small mb-4">Accede para reservar y gestionar tus espacios.</p>

            <?php flash_show(); ?>
            <?php if ($errorLogin): ?><div class="alert alert-danger py-2 small"><?= e($errorLogin) ?></div><?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="ident">Correo electrónico, matrícula, RFC o usuario</label>
                    <input class="form-control" id="ident" name="ident" value="<?= e($ident) ?>" placeholder="nombre@uthh.edu.mx" autofocus required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-light border" type="button" data-toggle-password="#password" aria-label="Mostrar u ocultar contraseña"><?= icono('eye', 16) ?></button>
                    </div>
                </div>
                <button class="btn btn-mh-navy w-100 py-2" type="submit">Iniciar sesión</button>
            </form>
            <div class="text-center small mt-3">
                <a href="<?= url('recuperar.php') ?>">¿Olvidaste tu contraseña?</a>
                <span class="mx-1">·</span>
                <a href="<?= url('registro.php') ?>">Crear cuenta</a>
            </div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
