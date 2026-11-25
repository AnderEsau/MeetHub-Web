<?php
/**
 * Ubicación — Responsable (una sola cosa por página: el contacto está en contacto.php): Samantha
 * Tabla: tblcontenido_publico (modulo = 'Datos'). Apartados usados: 'Dirección física', 'Edificio y área',
 * 'Horario y contacto', 'Portal institucional', 'Fachada' (contenido = ruta de la imagen).
 */
$titulo = 'Ubicación';
require_once __DIR__ . '/includes/header.php';

$dir = dato_publico('Dirección física');
$edif = dato_publico('Edificio y área');
$hor = dato_publico('Horario y contacto');
$portal = dato_publico('Portal institucional');
$fachada = dato_publico('Fachada');
?>
<main class="flex-grow-1 py-4">
    <div class="container" style="max-width:860px">
        <h1 class="h3 mh-title text-center mb-1">Ubicación</h1>
        <p class="small text-center mb-4">Dirección y horario de atención del Edificio de Biblioteca UTHH.</p>

        <section class="mh-card p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <h2 class="h5 mh-title mb-1">Datos de Ubicación</h2>
                    <p class="small mh-muted-brown mb-3">Visítanos dentro del campus universitario.</p>
                    <?php foreach ([['pin', 'Dirección física oficial', $dir['contenido'] ?? null], ['building', 'Edificio y Área', $edif['contenido'] ?? null], ['clock', 'Horario de servicio', 'Lunes a Viernes de 08:00 a 16:30 hrs.']] as [$ic, $t, $c]): ?>
                        <div class="d-flex gap-3 py-3 border-bottom">
                            <span class="mh-icon"><?= icono($ic, 18) ?></span>
                            <div><div class="fw-bold small"><?= e($t) ?></div><div class="small"><?= e($c ?? 'Pendiente de capturar.') ?></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-6">
                    <?php if ($fachada && $fachada['contenido'] !== ''): ?>
                        <img src="<?= e(url_imagen($fachada['contenido'])) ?>" alt="Fachada Biblioteca UTHH" class="mh-img mb-3" style="height:220px">
                    <?php else: ?>
                        <div class="mh-placeholder-img mb-3" style="min-height:220px"><?= icono('image', 28) ?><span>Fachada de la Biblioteca UTHH</span></div>
                    <?php endif; ?>
                    <a href="<?= url('contacto.php') ?>" class="btn btn-mh-outline w-100">¿Dudas? Ir a Contacto →</a>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
