<?php
/**
 * Contacto y soporte — Responsable: Lary
 * El formulario guarda en tblcontacto (estado 'Pendiente'); el encargado lo revisa en admin/mensajes.php.
 */
require_once __DIR__ . '/includes/db.php';
$error = '';
$v = ['nombre' => '', 'correo' => '', 'asunto' => '', 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) { $v[$k] = trim($_POST[$k] ?? ''); }
    if (in_array('', $v, true)) {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($v['correo'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo electrónico válido.';
    } elseif (mb_strlen($v['asunto']) > 150 || mb_strlen($v['nombre']) > 150) {
        $error = 'El nombre y el asunto deben tener máximo 150 caracteres.';
    } else {
        db_exec("INSERT INTO tblcontacto (nombre_completo, correo_electronico, asunto, mensaje) VALUES (?,?,?,?)",
                'ssss', $v['nombre'], $v['correo'], $v['asunto'], $v['mensaje']);
        flash_set('success', 'Mensaje enviado. El encargado de biblioteca te responderá a tu correo.');
        header('Location: ' . url('contacto.php'));
        exit;
    }
}

$titulo = 'Contacto';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container" style="max-width:620px">
        <h1 class="h3 mh-title text-center mb-1">Contacto y Soporte</h1>
        <p class="small text-center mb-4">Envíanos un mensaje directo para dudas sobre reservaciones o atención de la Biblioteca UTHH.</p>
        <section class="mh-card p-4">
            <h2 class="h6 mh-title mb-1">Envíanos un mensaje</h2>
            <p class="small mh-muted-brown mb-3">Completa el formulario y el encargado de biblioteca te responderá a tu correo.</p>
            <?php flash_show(); ?>
            <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
            <form method="POST" novalidate>
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label" for="nombre"><?= icono('user', 14) ?> Nombre completo</label>
                        <input class="form-control" id="nombre" name="nombre" maxlength="150" value="<?= e($v['nombre']) ?>" placeholder="Escribe tu nombre completo" required></div>
                    <div class="col-sm-6"><label class="form-label" for="correo"><?= icono('mail', 14) ?> Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?= e($v['correo']) ?>" placeholder="nombre@ejemplo.com" required></div>
                    <div class="col-12"><label class="form-label" for="asunto"><?= icono('doc', 14) ?> Asunto</label>
                        <input class="form-control" id="asunto" name="asunto" maxlength="150" value="<?= e($v['asunto']) ?>" placeholder="¿En qué podemos ayudarte?" required></div>
                    <div class="col-12"><label class="form-label" for="mensaje"><?= icono('chat', 14) ?> Mensaje</label>
                        <textarea class="form-control" id="mensaje" name="mensaje" rows="5" placeholder="Escribe aquí tu consulta o sugerencia." required><?= e($v['mensaje']) ?></textarea></div>
                    <div class="col-12"><button class="btn btn-mh w-100 py-2" type="submit">Enviar mensaje</button></div>
                </div>
            </form>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
