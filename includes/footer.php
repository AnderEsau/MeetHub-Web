<?php /* Pie de página: enlaces informativos (FAQ y políticas) o, para el personal, los menús del contenido público. */
$__rolPie = rol_actual();
$__personal = in_array($__rolPie, [ROL_BIBLIOTECARIO, ROL_ADMIN], true);
$__enlaces = $__personal ? enlaces_pie_contenido_publico($__rolPie) : enlaces_pie_publico();
?>
<footer class="mh-footer mt-auto">
    <div class="container-xl py-3 small">
        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 pb-3 mb-3 border-bottom mh-footer-links">
            <strong class="mh-title"><?= $__personal ? 'Contenido público (Alta, Baja y Cambios):' : 'Información:' ?></strong>
            <?php foreach ($__enlaces as $__l): ?>
                <a href="<?= url($__l['url']) ?>"><?= e($__l['etiqueta']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= url('assets/img/logo-dark.svg') ?>" alt="" width="24" height="24">
                <strong class="mh-title"><?= APP_NOMBRE ?></strong>
                <span class="mh-muted-brown">MeetHub © <?= APP_ANIO ?> - Sistema de Reserva de Salas UTHH</span>
            </div>
            <div class="d-flex flex-wrap gap-2 gap-md-3">
                <span><?= e(APP_CORREO) ?></span>
                <span class="d-none d-md-inline">|</span>
                <span><?= e(APP_WEB) ?></span>
                <span class="d-none d-md-inline">|</span>
                <span><?= e(APP_EDIFICIO) ?></span>
            </div>
        </div>
    </div>
</footer>

<script src="<?= url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/meethub.js') ?>"></script>
</body>
</html>
