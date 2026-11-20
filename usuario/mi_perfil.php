<?php
/**
 * Mi perfil — Responsable: Ander
 * El solicitante consulta sus datos, actualiza correo/teléfono y cambia su contraseña.
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
require_once __DIR__ . '/../includes/usuarios.php';

$tipo = $_SESSION['usuario_tipo'];
$uid = (int) $_SESSION['usuario_id'];
$tabla = ['alumno' => 'tblalumnos', 'docente' => 'tbltrabajadores', 'externo' => 'tblexterno'][$tipo] ?? 'tblalumnos';
$pk = ['alumno' => 'id_alumno', 'docente' => 'matricula', 'externo' => 'id_externo'][$tipo] ?? 'id_alumno';
$errores = [];

$u = db_una("SELECT * FROM $tabla WHERE $pk = ?", 'i', $uid);
if (!$u) { cerrar_sesion(); header('Location: ' . url('login.php')); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $tel = trim($_POST['telefono'] ?? '');
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    $conf = $_POST['confirmar'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Escribe un correo electrónico válido.'; }
    if ($tel !== '' && !preg_match('/^\d{10}$/', $tel)) { $errores[] = 'El teléfono debe tener 10 dígitos.'; }
    if (!$errores && strcasecmp($correo, $u['correo_electronico']) !== 0) {
        foreach (['tblalumnos', 'tbltrabajadores', 'tblexterno'] as $tb) {
            if (db_una("SELECT 1 AS x FROM $tb WHERE correo_electronico = ? LIMIT 1", 's', $correo)) { $errores[] = 'Ese correo ya está registrado en otra cuenta.'; break; }
        }
    }

    $cambiarPass = ($actual !== '' || $nueva !== '' || $conf !== '');
    if ($cambiarPass && !$errores) {
        if (!verificar_password($actual, $u['psw'])) { $errores[] = 'La contraseña actual no es correcta.'; }
        elseif (!password_valida($nueva)) { $errores[] = 'La nueva contraseña debe tener mínimo 8 caracteres con letras y números.'; }
        elseif ($nueva !== $conf) { $errores[] = 'La confirmación de la contraseña no coincide.'; }
    }
    if (!$errores) {
        db_exec("UPDATE $tabla SET correo_electronico = ?, telefono = ? WHERE $pk = ?", 'ssi', $correo, $tel, $uid);
        if ($cambiarPass) { cambiar_password($tabla, $pk, $uid, $nueva); }
        flash_set('success', 'Tus datos se actualizaron correctamente.');
        header('Location: ' . url('usuario/mi_perfil.php'));
        exit;
    }
    $u['correo_electronico'] = $correo; $u['telefono'] = $tel;
}

$clave = $tipo === 'externo' ? $u['rfc'] : $u[$pk];
$titulo = 'Mi perfil';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-5">
    <div class="container" style="max-width:520px">
        <?php flash_show(); ?>
        <section class="mh-card p-4">
            <h1 class="h5 mh-title mb-3">Datos de mi perfil</h1>
            <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>
            <div class="mh-tint p-3 small mb-3">
                <div><strong>Nombre:</strong> <?= e(nombre_completo($u)) ?></div>
                <div><strong>Matrícula o RFC:</strong> <?= e($clave) ?></div>
                <div><strong>Tipo de usuario:</strong> <?= e(ucfirst($tipo)) ?></div>
            </div>
            <form method="POST" novalidate>
                <div class="mb-3"><label class="form-label" for="correo"><?= icono('mail', 14) ?> Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" value="<?= e($u['correo_electronico']) ?>" required></div>
                <div class="mb-3"><label class="form-label" for="telefono"><?= icono('phone', 14) ?> Teléfono</label>
                    <input class="form-control" id="telefono" name="telefono" maxlength="10" value="<?= e($u['telefono']) ?>"></div>
                <hr>
                <h2 class="h6 mh-title">Cambiar contraseña</h2>
                <p class="form-text mt-0">Déjalo en blanco si no quieres cambiarla.</p>
                <div class="mb-3"><label class="form-label" for="actual">Contraseña actual</label>
                    <div class="input-group"><input type="password" class="form-control" id="actual" name="actual">
                    <button class="btn btn-light border" type="button" data-toggle-password="#actual"><?= icono('eye', 16) ?></button></div></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label" for="nueva">Nueva contraseña</label>
                        <div class="input-group"><input type="password" class="form-control" id="nueva" name="nueva">
                        <button class="btn btn-light border" type="button" data-toggle-password="#nueva"><?= icono('eye', 16) ?></button></div></div>
                    <div class="col-6"><label class="form-label" for="confirmar">Confirmar nueva contraseña</label>
                        <div class="input-group"><input type="password" class="form-control" id="confirmar" name="confirmar">
                        <button class="btn btn-light border" type="button" data-toggle-password="#confirmar"><?= icono('eye', 16) ?></button></div></div>
                </div>
                <button class="btn btn-mh w-100 py-2" type="submit">Guardar cambios</button>
            </form>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
