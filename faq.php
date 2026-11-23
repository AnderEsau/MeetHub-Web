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
        <p class="small text-center mb-4">Encuentra respuestas rápidas divididas por tema sobre el uso y reservación de salas en la Biblioteca UTHH.</p>

        <?php if (!$grupos): ?><div class="mh-card p-4 text-center small">Aún no hay preguntas publicadas.</div><?php endif; ?>
        <?php foreach ($grupos as $apartado => $items): $n++; ?>
            <section class="mh-card p-3 p-md-4 mb-3">
                <h2 class="h6 mh-title mb-3">Apartado <?= $n ?>: <?= e($apartado) ?></h2>
                <?php foreach ($items as $it): ?>
                    <div class="mh-tint p-3 mb-2">
                        <div class="fw-bold small mb-1"><?= e($it['titulo']) ?></div>
                        <div class="small"><?= nl2br(e($it['contenido'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
