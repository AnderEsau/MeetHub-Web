<footer class="mh-footer mt-auto">
    <div class="container-xl d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3 small">
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
</footer>

<script src="<?= url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/meethub.js') ?>"></script>
</body>
</html>
