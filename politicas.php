<?php
/**
 * Políticas (préstamo, cancelación y devolución) — Responsable: Samantha
 * Tabla: tblcontenido_publico (modulo = 'Politicas'). Los apartados se llaman exactamente:
 *   'Políticas de préstamo' | 'Políticas de cancelación' | 'Políticas de devolución'
 */
$titulo = 'Políticas';
require_once __DIR__ . '/includes/header.php';

$apartados = [
    1 => ['Políticas de préstamo', 'Políticas de Préstamo', 'check'],
    2 => ['Políticas de cancelación', 'Políticas de Cancelación', 'calendar'],
    3 => ['Políticas de devolución', 'Políticas de Devolución y Entrega', 'doc'],
];
$activo = (int) ($_GET['ap'] ?? 1);
if (!isset($apartados[$activo])) { $activo = 1; }
$rotulos = [1 => '1. Políticas de préstamo', 2 => '2. Políticas de cancelación', 3 => '3. Políticas de devolución / entrega'];
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo-sm">
        <h1 class="h3 mh-title text-center mb-1">Políticas y Reglas Institucionales</h1>
        <p class="small text-center mb-4">Consulta los lineamientos oficiales de préstamo, cancelación y devolución de espacios de la Biblioteca UTHH.</p>

        <div class="row g-3 mb-3 mh-tabs">
            <?php foreach ($rotulos as $n => $t): ?>
                <div class="col-md-4"><a href="?ap=<?= $n ?>" class="btn w-100 <?= $n === $activo ? 'btn-mh' : 'btn-mh-outline' ?>"><?= e($t) ?></a></div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3">
            <?php foreach ($apartados as $n => [$apartado, $rotulo, $ico]): $filas = contenido_activo('Politicas', $apartado); ?>
                <div class="col-md-4">
                    <section class="mh-card p-4 h-100" style="<?= $n === $activo ? 'border:2px solid var(--mh-navy)' : '' ?>">
                        <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                            <span class="mh-tint d-inline-flex align-items-center justify-content-center mh-icon" style="width:40px;height:40px"><?= icono($ico, 20) ?></span>
                            <h2 class="h6 mh-title mb-0"><?= e($rotulo) ?></h2>
                        </div>
                        <?php if ($filas): ?>
                            <ul class="mh-lista">
                                <?php foreach ($filas as $f): ?>
                                    <li class="mb-3"><?= e($f['contenido']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="small text-muted">Aún no hay políticas publicadas en este apartado.</p>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
