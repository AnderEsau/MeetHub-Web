<?php
/**
 * Crear cuenta — Responsable: Ander
 * Pantalla separada del login (usabilidad: "o creas cuenta o inicias sesión").
 * Según el tipo de usuario el formulario pide SOLO una cosa: matrícula (Alumno/Docente) o RFC (Externo).
 * Todas las validaciones están en includes/validaciones.php y se repiten en el servidor.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/usuarios.php';

if (usuario_logueado()) {
    header('Location: ' . pagina_inicio_por_rol(rol_actual()));
    exit;
}

$errores = [];
$v = ['tipo' => '', 'matricula' => '', 'nombre' => '', 'correo' => '', 'telefono' => '', 'pregunta' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $c => $_) {
        $v[$c] = trim($_POST[$c] ?? '');
    }
    $datos = $v + ['password' => $_POST['password_nuevo'] ?? '', 'respuesta' => $_POST['respuesta'] ?? ''];
    $errores = registrar_solicitante($datos);
    if (!$errores) {
        flash_set('success', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

$titulo = 'Crear cuenta';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-5">
    <div class="container" style="max-width: 620px;">
        <section class="mh-card p-4 p-md-5">
            <h1 class="h4 mh-title">Crear cuenta</h1>
            <p class="small mb-4">Regístrate por primera vez para reservar salas en la Biblioteca UTHH.</p>

            <?php if ($errores): ?>
                <div class="alert alert-danger small py-2">
                    <strong>Revisa estos datos:</strong>
                    <ul class="mb-0 ps-3"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="registro.php" novalidate autocomplete="off">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="tipo">Tipo de usuario</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Selecciona...</option>
                            <option value="alumno"  <?= $v['tipo'] === 'alumno' ? 'selected' : '' ?>>Alumno</option>
                            <option value="docente" <?= $v['tipo'] === 'docente' ? 'selected' : '' ?>>Docente</option>
                            <option value="externo" <?= $v['tipo'] === 'externo' ? 'selected' : '' ?>>Externo</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="matricula" id="lblClave">Matrícula o RFC</label>
                        <input class="form-control" id="matricula" name="matricula" value="<?= e($v['matricula']) ?>" placeholder="Primero elige tu tipo de usuario" required>
                        <div class="form-text" id="ayudaClave"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="nombre">Nombre completo</label>
                        <input class="form-control" id="nombre" name="nombre" value="<?= e($v['nombre']) ?>" placeholder="Ej. Lary Baruc Nolasco Linarte" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="correo">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?= e($v['correo']) ?>" placeholder="20251041@uthh.edu.mx" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="telefono">Teléfono (10 dígitos)</label>
                        <input class="form-control" id="telefono" name="telefono" value="<?= e($v['telefono']) ?>" placeholder="7711234567" maxlength="10" inputmode="numeric" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="password_nuevo">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" autocomplete="new-password" required>
                            <button class="btn btn-light border" type="button" data-toggle-password="#password_nuevo" aria-label="Mostrar u ocultar contraseña"><?= icono('eye', 16) ?></button>
                        </div>
                        <div class="form-text mh-muted-brown">Mínimo 8 caracteres con una letra, un número y un símbolo especial.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="pregunta">Pregunta de seguridad</label>
                        <select class="form-select" id="pregunta" name="pregunta" required>
                            <option value="">Selecciona...</option>
                            <?php foreach (PREGUNTAS_SEGURIDAD as $q): ?>
                                <option <?= $v['pregunta'] === $q ? 'selected' : '' ?>><?= e($q) ?></option>
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
            <div class="text-center small mt-3">¿Ya tienes cuenta? <a href="<?= url('login.php') ?>">Iniciar sesión</a></div>
        </section>
    </div>
</main>
<script>
// Alumno/Docente -> solo matrícula (números). Externo -> solo RFC (mayúsculas, 13 máx.). El teléfono solo acepta números.
(function () {
  var tipo = document.getElementById('tipo'), clave = document.getElementById('matricula'),
      lbl = document.getElementById('lblClave'), ayuda = document.getElementById('ayudaClave'),
      tel = document.getElementById('telefono');
  function ajustar(limpiar) {
    var t = tipo.value;
    if (limpiar) { clave.value = ''; }
    if (t === 'alumno') {
      lbl.textContent = 'Matrícula'; clave.placeholder = '8 dígitos, ej. 20251041';
      clave.maxLength = 8; clave.inputMode = 'numeric'; ayuda.textContent = 'Exactamente 8 dígitos, solo números.';
    } else if (t === 'docente') {
      lbl.textContent = 'Matrícula'; clave.placeholder = 'Tu matrícula de docente (solo números)';
      clave.maxLength = 8; clave.inputMode = 'numeric'; ayuda.textContent = 'Solo números (de 3 a 8 dígitos).';
    } else if (t === 'externo') {
      lbl.textContent = 'RFC'; clave.placeholder = 'Ej. MERC850412H10';
      clave.maxLength = 13; clave.inputMode = 'text'; ayuda.textContent = '12 o 13 caracteres: letras, fecha (6 dígitos) y homoclave.';
    } else {
      lbl.textContent = 'Matrícula o RFC'; clave.placeholder = 'Primero elige tu tipo de usuario';
      clave.maxLength = 13; ayuda.textContent = '';
    }
  }
  tipo.addEventListener('change', function () { ajustar(true); });
  clave.addEventListener('input', function () {
    if (tipo.value === 'alumno' || tipo.value === 'docente') { clave.value = clave.value.replace(/\D/g, ''); }
    if (tipo.value === 'externo') { clave.value = clave.value.toUpperCase().replace(/[^A-ZÑ&0-9]/g, ''); }
  });
  tel.addEventListener('input', function () { tel.value = tel.value.replace(/\D/g, ''); });
  ajustar(false);
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
