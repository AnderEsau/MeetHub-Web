<?php
/**
 * Preguntas frecuentes — Responsable: Lary
 * Tabla: tblcontenido_publico (modulo = 'FAQ'). Cada "apartado" es un grupo; título = pregunta; contenido = respuesta.
 */
$titulo = 'Preguntas frecuentes';
require_once __DIR__ . '/includes/header.php';

$grupos = [];
foreach (contenido_activo('FAQ') as $f) { $grupos[$f['apartado']][] = $f; }
$n = 0;
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo-sm" style="max-width:860px">
        <h1 class="h3 mh-title text-center mb-1">Preguntas Frecuentes (FAQ)</h1>
        <p class="small text-center mb-4">Respuestas rápidas por tema. Toca una pregunta para ver la respuesta.</p>

        <?php if (!$grupos): ?><div class="mh-card p-4 text-center small">Aún no hay preguntas publicadas.</div><?php endif; ?>
        <?php foreach ($grupos as $apartado => $items): $n++; ?>
            <section class="mh-card p-3 p-md-4 mb-3">
                <h2 class="h6 mh-title mb-3"><?= e($apartado) ?></h2>
                <div class="accordion" id="faq<?= $n ?>">
                    <?php foreach ($items as $i => $it): $idc = 'faq' . $n . '_' . $i; ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $idc ?>" aria-expanded="false"><?= e($it['titulo']) ?></button>
                            </h3>
                            <div id="<?= $idc ?>" class="accordion-collapse collapse" data-bs-parent="#faq<?= $n ?>">
                                <div class="accordion-body small"><?= nl2br(e($it['contenido'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
