<?php
/**
 * Inicio público — Responsable: Ander
 * Página sencilla ("menos es más"): invitación a reservar y accesos a lo principal.
 * Misión, Visión y Calidad están en quienes_somos.php; la dirección en ubicacion.php; las políticas y preguntas en el pie de página.
 */
require_once __DIR__ . '/includes/auth.php';
if (usuario_logueado()) {
    header('Location: ' . pagina_inicio_por_rol(rol_actual()));
    exit;
}
$titulo = 'Inicio';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <section class="mh-card p-4 p-md-5 mb-4 text-center">
            <div class="mh-eyebrow mb-2">Biblioteca institucional UTHH</div>
            <h1 class="h3 mh-title mb-2">MeetHub: Gestión y reserva de espacios académicos</h1>
            <p class="mb-4 mx-auto" style="max-width:640px">Consulta la disponibilidad, conoce el catálogo de salas y reserva con al menos <?= DIAS_ANTICIPACION ?> días de anticipación.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= url('login.php') ?>" class="btn btn-mh px-4">Reservar Sala</a>
                <a href="<?= url('salas.php') ?>" class="btn btn-mh-outline px-4">Ver Catálogo de Salas</a>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-md-4"><a class="text-decoration-none" href="<?= url('disponibilidad.php') ?>"><div class="mh-card p-3 h-100"><div class="mh-icon mb-2"><?= icono('clock', 22) ?></div><div class="fw-bold mb-1">Horarios</div><div class="small">Lunes a Viernes de 8:00 a 16:30. Consulta la disponibilidad.</div></div></a></div>
            <div class="col-md-4"><a class="text-decoration-none" href="<?= url('ubicacion.php') ?>"><div class="mh-card p-3 h-100"><div class="mh-icon mb-2"><?= icono('pin', 22) ?></div><div class="fw-bold mb-1">Ubicación</div><div class="small">Edificio de Biblioteca UTHH.</div></div></a></div>
            <div class="col-md-4"><a class="text-decoration-none" href="<?= url('salas.php') ?>"><div class="mh-card p-3 h-100"><div class="mh-icon mb-2"><?= icono('wifi', 22) ?></div><div class="fw-bold mb-1">Servicios y Equipamiento</div><div class="small">Conoce qué mobiliario y equipo incluye cada espacio.</div></div></a></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
