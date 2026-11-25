<?php
/**
 * Mi perfil — Responsable: Ander
 * El solicitante consulta sus datos, actualiza correo/teléfono/domicilio y, con un botón aparte, cambia su contraseña.
 *  - El domicilio es opcional al registrarse (para no pedir "un chingo de formularios"), pero se exige completarlo
 *    aquí antes de hacer la primera reserva (ver perfil_completo() en includes/usuarios.php).
 *  - Antes de guardar un correo nuevo se verifica que no exista en ninguna de las 3 tablas de solicitantes.
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
require_once __DIR__ . '/../includes/usuarios.php';

$tipo = $_SESSION['usuario_tipo'];
$uid = (int) $_SESSION['usuario_id'];
$tabla = ['alumno' => 'tblalumnos', 'docente' => 'tbltrabajadores', 'externo' => 'tblexterno'][$tipo] ?? 'tblalumnos';
$pk = ['alumno' => 'id_alumno', 'docente' => 'matricula', 'externo' => 'id_externo'][$tipo] ?? 'id_alumno';
$hayDomicilio = columna_existe($tabla, 'domicilio');
$errores = [];
$abrirPass = false;

$u = db_una("SELECT * FROM $tabla WHERE $pk = ?", 'i', $uid);
if (!$u) { cerrar_sesion(); header('Location: ' . url('login.php')); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'datos';

    if ($accion === 'datos') {
        $correo = trim($_POST['correo'] ?? '');
        $tel = trim($_POST['telefono'] ?? '');
        $dom = trim($_POST['domicilio'] ?? '');
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 100) { $errores[] = 'Escribe un correo electrónico válido.'; }
        elseif (strcasecmp($correo, $u['correo_electronico']) !== 0 && correo_ya_registrado_en_tablas($correo, [$tabla, $uid])) { $errores[] = 'Ese correo ya está registrado en otra cuenta.'; }
        if ($e = val_telefono($tel)) { $errores[] = $e; }
        if (mb_strlen($dom) > 255) { $errores[] = 'El domicilio puede tener máximo 255 caracteres.'; }
        if (!$errores) {
            db_exec("UPDATE $tabla SET correo_electronico = ?, telefono = ? WHERE $pk = ?", 'ssi', $correo, $tel, $uid);
            if ($hayDomicilio) { db_exec("UPDATE $tabla SET domicilio = ? WHERE $pk = ?", 'si', $dom === '' ? null : $dom, $uid); }
            flash_set('success', 'Tus datos se actualizaron correctamente.');
            header('Location: ' . url('usuario/mi_perfil.php'));
            exit;
        }
        $u['correo_electronico'] = $correo; $u['telefono'] = $tel; if ($hayDomicilio) { $u['domicilio'] = $dom; }
    }

    if ($accion === 'password') {
        $abrirPass = true;
        $actual = $_POST['actual'] ?? '';
        $nueva = $_POST['nueva'] ?? '';
        $conf = $_POST['confirmar'] ?? '';
        if (!verificar_password($actual, $u['psw'])) { $errores[] = 'La contraseña actual no es correcta.'; }
        elseif (!password_valida($nueva)) { $errores[] = MSG_PASSWORD; }
        elseif ($nueva !== $conf) { $errores[] = 'La confirmación de la contraseña no coincide.'; }
        if (!$errores) {
            cambiar_password($tabla, $pk, $uid, $nueva);
            flash_set('success', 'Tu contraseña se cambió correctamente.');
            header('Location: ' . url('usuario/mi_perfil.php'));
            exit;
        }
    }
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
                <div><strong><?= $tipo === 'externo' ? 'RFC' : 'Matrícula' ?>:</strong> <?= e($clave) ?></div>
                <div><strong>Tipo de usuario:</strong> <?= e(etiqueta_tipo_usuario()) ?></div>
            </div>
            <?php if ($hayDomicilio && trim((string) ($u['domicilio'] ?? '')) === ''): ?>
                <div class="alert alert-warning py-2 small">Para reservar una sala necesitas completar tu <strong>domicilio</strong>.</div>
            <?php endif; ?>
            <form method="POST" novalidate>
                <input type="hidden" name="accion" value="datos">
                <div class="mb-3"><label class="form-label" for="correo"><?= icono('mail', 14) ?> Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" value="<?= e($u['correo_electronico']) ?>" required></div>
                <div class="mb-3"><label class="form-label" for="telefono"><?= icono('phone', 14) ?> Teléfono (10 dígitos)</label>
                    <input class="form-control" id="telefono" name="telefono" maxlength="10" inputmode="numeric" value="<?= e($u['telefono']) ?>" required></div>
                <?php if ($hayDomicilio): ?>
                <div class="mb-3"><label class="form-label" for="domicilio"><?= icono('pin', 14) ?> Domicilio (se pide antes de reservar)</label>
                    <input class="form-control" id="domicilio" name="domicilio" maxlength="255" value="<?= e($u['domicilio'] ?? '') ?>" placeholder="Calle, número, colonia y municipio"></div>
                <?php endif; ?>
                <button class="btn btn-mh w-100 py-2" type="submit">Guardar cambios</button>
            </form>
            <hr class="my-4">
            <button class="btn btn-mh-outline w-100" type="button" data-bs-toggle="collapse" data-bs-target="#cambiarPass" aria-expanded="<?= $abrirPass ? 'true' : 'false' ?>">Cambiar contraseña</button>
            <div class="collapse<?= $abrirPass ? ' show' : '' ?> mt-3" id="cambiarPass">
                <form method="POST" novalidate autocomplete="off">
                    <input type="hidden" name="accion" value="password">
                    <div class="mb-3"><label class="form-label" for="actual">Contraseña actual</label>
                        <div class="input-group"><input type="password" class="form-control" id="actual" name="actual" required>
                        <button class="btn btn-light border" type="button" data-toggle-password="#actual"><?= icono('eye', 16) ?></button></div></div>
                    <div class="mb-3"><label class="form-label" for="nueva">Nueva contraseña</label>
                        <div class="input-group"><input type="password" class="form-control" id="nueva" name="nueva" autocomplete="new-password" required>
                        <button class="btn btn-light border" type="button" data-toggle-password="#nueva"><?= icono('eye', 16) ?></button></div>
                        <div class="form-text">Mínimo 8 caracteres con letra, número y símbolo especial.</div></div>
                    <div class="mb-3"><label class="form-label" for="confirmar">Confirmar nueva contraseña</label>
                        <div class="input-group"><input type="password" class="form-control" id="confirmar" name="confirmar" autocomplete="new-password" required>
                        <button class="btn btn-light border" type="button" data-toggle-password="#confirmar"><?= icono('eye', 16) ?></button></div></div>
                    <button class="btn btn-mh-navy w-100" type="submit">Guardar nueva contraseña</button>
                </form>
            </div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
