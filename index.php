<?php
/**
 * Inicio público (Nosotros) — Responsable: Ander (Datos de la empresa)
 * Misión, Visión y Política de calidad salen de tblcontenido_publico (modulo = 'Datos'); se editan en admin/datos_empresa_abc.php.
 */
require_once __DIR__ . '/includes/auth.php';
if (usuario_logueado()) {
    header('Location: ' . pagina_inicio_por_rol(rol_actual()));
    exit;
}
$titulo = 'Inicio';
require_once __DIR__ . '/includes/header.php';

$mision = dato_publico('Misión');
$vision = dato_publico('Visión');
$calidad = dato_publico('Política de calidad');
$fachada = dato_publico('Fachada');
$cajas = [['Misión', $mision], ['Visión', $vision], ['Política de Calidad', $calidad]];
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <section class="mh-card p-4 mb-3">
            <h1 class="h6 mh-title mb-3">Nosotros / Biblioteca UTHH</h1>
            <div class="row g-4">
                <div class="col-md-4">
                    <?php if ($fachada && $fachada['contenido'] !== ''): ?>
                        <img src="<?= e(url_imagen($fachada['contenido'])) ?>" alt="<?= e($fachada['titulo']) ?>" class="mh-img" style="height:200px">
                    <?php else: ?>
                        <div class="mh-placeholder-img h-100"><?= icono('image', 28) ?><span>Fotografía de la entrada de la<br>Biblioteca UTHH</span></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="mh-eyebrow mb-1">Biblioteca institucional UTHH · Nosotros</div>
                    <h2 class="h4 mh-title mb-2">MeetHub: Gestión y reserva de espacios académicos</h2>
                    <p class="small mb-3">La Biblioteca UTHH ofrece espacios pensados para estudio, colaboración y reuniones académicas. MeetHub centraliza la disponibilidad, el catálogo de salas y la reserva para que la comunidad encuentre el espacio adecuado con claridad y rapidez.</p>
                    <div class="row g-2">
                        <?php foreach ($cajas as [$nombre, $d]): ?>
                            <div class="col-sm-4">
                                <div class="mh-tint p-3 h-100">
                                    <div class="fw-bold small mb-1"><?= e($nombre) ?></div>
                                    <div style="font-size:.75rem"><?= e($d['contenido'] ?? 'Pendiente de capturar por el administrador.') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="mh-card p-3 px-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="h6 mh-title mb-1">Tu próxima reunión comienza aquí</h2>
                <div class="small">Consulta la disponibilidad en tiempo real y agenda con al menos <?= DIAS_ANTICIPACION ?> días de anticipación.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('login.php') ?>" class="btn btn-mh px-4">Reservar Sala</a>
                <a href="<?= url('salas.php') ?>" class="btn btn-mh-outline px-4">Ver Catálogo de Salas</a>
            </div>
        </section>

        <h2 class="h6 mh-title mb-3">Todo lo que necesitas para reunirte</h2>
        <div class="row g-3">
            <div class="col-md-4"><div class="mh-card p-3 h-100"><div class="mh-icon mb-2"><?= icono('clock', 22) ?></div><div class="fw-bold mb-1">Horarios</div><div class="small">Consulta horarios y disponibilidad (Lunes a Viernes 8:00 a 16:30).</div></div></div>
            <div class="col-md-4"><div class="mh-card p-3 h-100"><div class="mh-icon mb-2"><?= icono('pin', 22) ?></div><div class="fw-bold mb-1">Ubicación</div><div class="small">Edificio de Biblioteca UTHH.</div></div></div>
            <div class="col-md-4"><div class="mh-card p-3 h-100"><div class="mh-icon mb-2"><?= icono('wifi', 22) ?></div><div class="fw-bold mb-1">Servicios y Equipamiento</div><div class="small">Conoce qué mobiliario y equipo incluye cada espacio.</div></div></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
