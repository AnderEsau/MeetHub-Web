<?php
/**
 * Login único + Crear cuenta (vista dividida) — Responsable: Ander
 * Tablas: tblalumnos / tbltrabajadores / tblexterno / tblbibliotecario
 * Mockup: "Crear cuenta e Iniciar sesión (Vista dividida)"
 *
 * El login es el mismo para todos: según el rol que regrese la BD se
 * redirige y el menú (includes/menu.php) cambia solo.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/usuarios.php';

if (usuario_logueado()) {
    header('Location: ' . pagina_inicio_por_rol(rol_actual()));
    exit;
}

$errorLogin = '';
$errorRegistro = '';
$exito = '';
$ok = ['ident' => '', 'tipo' => '', 'matricula' => '', 'nombre' => '', 'correo' => '', 'telefono' => '', 'pregunta' => ''];

if (isset($_GET['error'])) {
    $errorLogin = $_GET['error'] === 'sin_permiso'
        ? 'Tu cuenta no tiene permiso para entrar a esa sección.'
        : 'Debes iniciar sesión para acceder a esa sección.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ---------- Iniciar sesión ----------
    if ($accion === 'login') {
        $ident = trim($_POST['ident'] ?? '');
        $password = $_POST['password'] ?? '';
        $ok['ident'] = $ident;

        if ($ident === '' || $password === '') {
            $errorLogin = 'Por favor llena todos los campos.';
        } else {
            $conexion = conectar_bd();
            $u = buscar_usuario_por_identificador($conexion, $ident);
            if ($u && verificar_password($password, $u['password'])) {
                iniciar_sesion_usuario((int) $u['id'], $u['nombre'], $u['rol'], $u['tipo']);
                header('Location: ' . pagina_inicio_por_rol($u['rol']));
                exit;
            }
            $errorLogin = 'Credenciales incorrectas.';
        }
    }

    // ---------- Crear cuenta ----------
    if ($accion === 'registro') {
        foreach (['tipo', 'matricula', 'nombre', 'correo', 'telefono', 'pregunta'] as $c) {
            $ok[$c] = trim($_POST[$c] ?? '');
        }
        $password = $_POST['password_nuevo'] ?? '';
        $respuesta = trim($_POST['respuesta'] ?? '');

        if (!in_array($ok['tipo'], ['alumno', 'docente', 'externo'], true)) {
            $errorRegistro = 'Selecciona el tipo de usuario.';
        } elseif ($ok['matricula'] === '' || $ok['nombre'] === '' || $ok['pregunta'] === '' || $respuesta === '') {
            $errorRegistro = 'Completa todos los campos obligatorios.';
        } elseif (!filter_var($ok['correo'], FILTER_VALIDATE_EMAIL)) {
            $errorRegistro = 'Escribe un correo electrónico válido.';
        } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
            $errorRegistro = 'La contraseña debe tener mínimo 8 caracteres con letras y números.';
        } else {
            $conexion = conectar_bd();
            $err = registrar_solicitante($conexion, $ok + ['password' => $password, 'respuesta' => $respuesta]);
            if ($err === null) {
                $exito = 'Cuenta creada. Ya puedes iniciar sesión.';
                $ok = array_map(fn($v) => '', $ok);
            } else {
                $errorRegistro = $err;
            }
        }
    }
}

$titulo = 'Iniciar sesión';
$preguntas = ['¿Nombre de tu primera mascota?', '¿Ciudad donde naciste?', '¿Nombre de tu mejor amigo de la infancia?',
              '¿Cuál es tu comida favorita?', '¿Nombre de tu primera escuela?'];
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-5">
    <div class="container" style="max-width: 900px;">
        <section class="mh-card p-4 p-md-5">
            <div class="row g-4 g-lg-5">

                <!-- Crear cuenta -->
                <div class="col-lg-7" id="crear-cuenta">
                    <h1 class="h4 mh-title">Crear Cuenta</h1>
                    <p class="small mb-4">Regístrate por primera vez para comenzar a reservar salas en la Biblioteca UTHH.</p>

                    <?php if ($errorRegistro): ?><div class="alert alert-danger py-2 small"><?= e($errorRegistro) ?></div><?php endif; ?>
                    <?php if ($exito): ?><div class="alert alert-success py-2 small"><?= e($exito) ?></div><?php endif; ?>

                    <form method="POST" action="login.php#crear-cuenta" novalidate>
                        <input type="hidden" name="accion" value="registro">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="tipo">Tipo de usuario</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Alumno / Docente / Externo</option>
                                    <option value="alumno"  <?= $ok['tipo'] === 'alumno' ? 'selected' : '' ?>>Alumno</option>
                                    <option value="docente" <?= $ok['tipo'] === 'docente' ? 'selected' : '' ?>>Docente</option>
                                    <option value="externo" <?= $ok['tipo'] === 'externo' ? 'selected' : '' ?>>Externo</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="matricula">Matrícula o RFC</label>
                                <input class="form-control" id="matricula" name="matricula" value="<?= e($ok['matricula']) ?>" placeholder="Ingresa tu matrícula o RFC" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="nombre">Nombre completo</label>
                                <input class="form-control" id="nombre" name="nombre" value="<?= e($ok['nombre']) ?>" placeholder="Ej. Lary Baruc Nolasco Linarte" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="correo">Correo electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" value="<?= e($ok['correo']) ?>" placeholder="20251041@uthh.edu.mx" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="telefono">Teléfono</label>
                                <input class="form-control" id="telefono" name="telefono" value="<?= e($ok['telefono']) ?>" placeholder="7711234567" maxlength="10" pattern="[0-9]{10}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="password_nuevo">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" required>
                                    <button class="btn btn-light border" type="button" data-toggle-password="#password_nuevo" aria-label="Mostrar u ocultar contraseña"><?= icono('eye', 16) ?></button>
                                </div>
                                <div class="form-text text-end mh-muted-brown">Mín. 8 caracteres, letras y números</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="pregunta">Pregunta de seguridad</label>
                                <select class="form-select" id="pregunta" name="pregunta" required>
                                    <option value="">Selecciona...</option>
                                    <?php foreach ($preguntas as $q): ?>
                                        <option <?= $ok['pregunta'] === $q ? 'selected' : '' ?>><?= e($q) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="respuesta">Respuesta secreta</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="respuesta" name="respuesta" required>
                                    <button class="btn btn-light border" type="button" data-toggle-password="#respuesta" aria-label="Mostrar u ocultar respuesta"><?= icono('eye', 16) ?></button>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-mh w-100 py-2" type="submit">Crear cuenta</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Iniciar sesión -->
                <div class="col-lg-5 mh-divisor ps-lg-5">
                    <h2 class="h4 mh-title">Iniciar Sesión</h2>
                    <p class="small mb-4">Si ya tienes cuenta, accede para reservar y gestionar tus espacios.</p>

                    <?php if ($errorLogin): ?><div class="alert alert-danger py-2 small"><?= e($errorLogin) ?></div><?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <input type="hidden" name="accion" value="login">
                        <div class="mb-3">
                            <label class="form-label" for="ident">Correo electrónico, matrícula o RFC</label>
                            <input class="form-control" id="ident" name="ident" value="<?= e($ok['ident']) ?>" placeholder="nombre@uthh.edu.mx" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="password">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-light border" type="button" data-toggle-password="#password" aria-label="Mostrar u ocultar contraseña"><?= icono('eye', 16) ?></button>
                            </div>
                        </div>
                        <div class="mb-3 text-end small"><a href="<?= url('recuperar.php') ?>">¿Olvidaste tu contraseña?</a></div>
                        <button class="btn btn-mh-navy w-100 py-2" type="submit">Iniciar sesión</button>
                    </form>
                    <hr class="my-4">
                    <div class="mh-tint p-3 small">
                        ¿Es tu primera vez en MeetHub? Crea tu cuenta en el formulario de la izquierda seleccionando tu tipo de usuario (Alumno, Docente o Externo).
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
