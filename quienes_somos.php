<?php
/**
 * Quiénes somos — Responsable: Ander (Datos de la empresa)
 * Misión, Visión y Política de calidad salen de tblcontenido_publico (modulo = 'Datos'); se editan en admin/datos_empresa_abc.php.
 * Es una página aparte (no va todo mezclado en el inicio).
 */
$titulo = 'Quiénes somos';
require_once __DIR__ . '/includes/header.php';

$fachada = dato_publico('Fachada');
$bloques = [
    ['Misión', 'Misión', 'check'],
    ['Visión', 'Visión', 'globe'],
    ['Política de calidad', 'Política de Calidad', 'doc'],
];
?>
<main class="flex-grow-1 py-4">
    <div class="container" style="max-width:860px">
        <h1 class="h3 mh-title text-center mb-1">Quiénes somos</h1>
        <p class="small text-center mb-4">Biblioteca UTHH · Gestión y reserva de espacios académicos con MeetHub.</p>

        <?php if ($fachada && $fachada['contenido'] !== ''): ?>
            <img src="<?= e(url_imagen($fachada['contenido'])) ?>" alt="Fachada de la Biblioteca UTHH" class="mh-img mb-4" style="max-height:260px">
        <?php endif; ?>

        <?php foreach ($bloques as [$apartado, $rotulo, $ico]): $d = dato_publico($apartado); ?>
            <section class="mh-card p-4 mb-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="mh-icon"><?= icono($ico, 22) ?></span>
                    <h2 class="h5 mh-title mb-0"><?= e($rotulo) ?></h2>
                </div>
                <p class="mb-0"><?= nl2br(e($d['contenido'] ?? 'Pendiente de capturar por el administrador.')) ?></p>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
