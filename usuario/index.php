<?php
/** Inicio (Dashboard) del solicitante — Responsable: Ander. Tablas: tblreserva (solo las del usuario en sesión). */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
$titulo = 'Inicio';
require_once __DIR__ . '/../includes/header.php';

[$col, $id] = columna_reserva_usuario();
$pend = db_todos("SELECT id_reserva FROM tblreserva WHERE $col = ? AND estado_reserva = 'Pendiente' ORDER BY fecha_reserva", 'i', $id);
$aprob = db_una("SELECT COUNT(*) AS n FROM tblreserva WHERE $col = ? AND estado_reserva = 'Aprobada' AND fecha_reserva >= CURDATE()", 'i', $id)['n'] ?? 0;
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo-sm">
        <?php flash_show(); ?>
        <section class="mh-card p-4 mb-3">
            <h1 class="h4 mh-title mb-2">Bienvenido, <?= e($_SESSION['usuario_nombre']) ?></h1>
            <div class="mb-2"><span class="mh-rol mh-rol-<?= e($_SESSION['usuario_tipo']) ?>" style="margin-left:0">Sesión de <?= e(etiqueta_tipo_usuario()) ?></span></div>
            <p class="small mb-0">Desde tu panel puedes consultar disponibilidad, explorar el catálogo, agendar espacios y gestionar tus reservaciones en la Biblioteca UTHH.</p>
        </section>
        <div class="row g-3">
            <div class="col-md-4"><section class="mh-card p-4 h-100 d-flex flex-column">
                <div class="mh-icon mb-3"><?= icono('calendar', 26) ?></div>
                <h2 class="h6 mh-title">Agendar sala</h2>
                <p class="small flex-grow-1">Consulta disponibilidad y reserva un espacio para tus actividades.</p>
                <a href="<?= url('usuario/nueva_solicitud.php') ?>" class="btn btn-mh">Ir a Nueva solicitud</a>
            </section></div>
            <div class="col-md-4"><section class="mh-card p-4 h-100 d-flex flex-column">
                <div class="mh-icon mb-3"><?= icono('check', 26) ?></div>
                <h2 class="h6 mh-title">Mis solicitudes activas</h2>
                <div class="small flex-grow-1">
                    <?php if ($pend): ?>
                        Tienes <?= count($pend) ?> solicitud<?= count($pend) > 1 ? 'es pendientes' : ' pendiente' ?><br>
                        <span class="mh-badge mh-badge-pendiente mt-2">Pendiente</span>
                    <?php else: ?>
                        No tienes solicitudes pendientes.
                    <?php endif; ?>
                    <?php if ($aprob): ?><div class="mt-2"><?= (int) $aprob ?> reserva<?= $aprob > 1 ? 's aprobadas próximas' : ' aprobada próxima' ?>.</div><?php endif; ?>
                </div>
                <a href="<?= url('usuario/mis_solicitudes.php') ?>" class="btn btn-mh-outline mt-3">Ver mis solicitudes</a>
            </section></div>
            <div class="col-md-4"><section class="mh-card p-4 h-100">
                <div class="mh-icon mb-3"><?= icono('info', 26) ?></div>
                <h2 class="h6 mh-title">Reglas de Biblioteca</h2>
                <div class="mh-tint p-3"><ul class="mh-lista">
                    <li>Solicitar con mínimo <?= DIAS_ANTICIPACION ?> días de anticipación.</li>
                    <li>Respetar el aforo mínimo de cada sala.</li>
                    <li>Horario Lunes a Viernes 08:00 a 16:30.</li>
                </ul></div>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
