<?php
/**
 * Una política por página (préstamo, cancelación, devolución) — Responsable: Samantha
 * Lo incluyen politica_prestamo.php, politica_cancelacion.php y politica_devolucion.php con:
 *   $apartado  = nombre EXACTO del apartado en tblcontenido_publico (modulo 'Politicas')
 *   $rotulo    = título que ve el usuario
 *   $icono     = nombre del ícono
 *   $resumen   = frase corta bajo el título
 * Cada fila activa de ese apartado es un punto de la política (título = regla, contenido = detalle).
 */
require_once __DIR__ . '/../header.php';
$filas = contenido_activo('Politicas', $apartado);
?>
<main class="flex-grow-1 py-4">
    <div class="container" style="max-width:820px">
        <h1 class="h3 mh-title text-center mb-1"><?= e($rotulo) ?></h1>
        <p class="small text-center mb-4"><?= e($resumen) ?></p>

        <section class="mh-card p-4">
            <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                <span class="mh-tint d-inline-flex align-items-center justify-content-center mh-icon" style="width:44px;height:44px"><?= icono($icono, 22) ?></span>
                <h2 class="h6 mh-title mb-0">Términos y condiciones</h2>
            </div>
            <?php if ($filas): $n = 0; foreach ($filas as $f): $n++; ?>
                <div class="mb-3">
                    <div class="fw-bold small mb-1"><?= $n ?>. <?= e($f['titulo']) ?></div>
                    <div class="small"><?= nl2br(e($f['contenido'])) ?></div>
                </div>
            <?php endforeach; else: ?>
                <p class="small text-muted mb-0">Aún no hay puntos publicados en esta política.</p>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../footer.php'; ?>
