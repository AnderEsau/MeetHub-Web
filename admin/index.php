<?php
/** Dashboard de gestión (Bibliotecario y Administrador) — Responsable: Ander. Contadores reales de la BD. */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol([ROL_BIBLIOTECARIO, ROL_ADMIN]);
$titulo = 'Panel de gestión';
require_once __DIR__ . '/../includes/header.php';
$esAdmin = rol_actual() === ROL_ADMIN;

$nPend = (int) db_una("SELECT COUNT(*) n FROM tblreserva WHERE estado_reserva = 'Pendiente'")['n'];
$nAprob = (int) db_una("SELECT COUNT(*) n FROM tblreserva WHERE estado_reserva = 'Aprobada' AND fecha_reserva >= CURDATE()")['n'];
$nSalas = (int) db_una("SELECT COUNT(*) n FROM tblsalas WHERE estado = 'Activa'")['n'];
$nEquipo = (int) db_una("SELECT COALESCE(SUM(cantidad),0) n FROM tblequipamiento WHERE estado = 'Activo'")['n'];
$nMsj = (int) db_una("SELECT COUNT(*) n FROM tblcontacto WHERE estado = 'Pendiente'")['n'];
$nUsr = (int) db_una("SELECT (SELECT COUNT(*) FROM tblalumnos WHERE activo=1)+(SELECT COUNT(*) FROM tbltrabajadores WHERE activo=1)+(SELECT COUNT(*) FROM tblexterno WHERE activo=1) n")['n'];
$pendientes = db_todos(SQL_RESERVAS . " WHERE r.estado_reserva = 'Pendiente' ORDER BY r.fecha_reserva, r.hora_inicio LIMIT 4");
$opiniones = db_todos("SELECT g.*, s.nombre_sala FROM tblsugerencias g JOIN tblreserva r ON r.id_reserva = g.id_reserva JOIN tblsalas s ON s.id_sala = r.id_sala ORDER BY g.fecha_envio DESC LIMIT 3");
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <section class="mh-card p-4 mb-3">
            <h1 class="h4 mh-title mb-1">Panel de Gestión — Biblioteca UTHH</h1>
            <p class="small mb-0"><?= $esAdmin ? 'Resumen del portal institucional, usuarios, reportes y mensajes de contacto.' : 'Resumen operativo de solicitudes, salas, equipamiento y mensajes.' ?></p>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="mh-card p-3 h-100"><div class="small fw-semibold mb-2"><?= icono('doc', 16) ?> Solicitudes Pendientes</div>
                <div class="mh-stat"><?= $nPend ?> folio<?= $nPend === 1 ? '' : 's' ?></div><span class="mh-badge mh-badge-pendiente mt-2">Por aprobar/rechazar</span></div></div>
            <div class="col-md-3"><div class="mh-card p-3 h-100"><div class="small fw-semibold mb-2"><?= icono('check', 16) ?> Reservas Confirmadas</div>
                <div class="mh-stat" style="color:var(--mh-green)"><?= $nAprob ?> activa<?= $nAprob === 1 ? '' : 's' ?></div><div class="small mt-2">Próximas por realizar</div></div></div>
            <div class="col-md-3"><div class="mh-card p-3 h-100"><div class="small fw-semibold mb-2"><?= icono('door', 16) ?> Salas y Mobiliario</div>
                <div class="mh-stat"><?= $nSalas ?> salas · <?= $nEquipo ?> equipos</div><div class="small mt-2">Catálogo operativo</div></div></div>
            <div class="col-md-3"><div class="mh-card p-3 h-100">
                <?php if ($esAdmin): ?><div class="small fw-semibold mb-2"><?= icono('people', 16) ?> Usuarios activos</div><div class="mh-stat"><?= $nUsr ?></div><div class="small mt-2">Solicitantes registrados</div>
                <?php else: ?><div class="small fw-semibold mb-2"><?= icono('globe', 16) ?> Contenido Público (ABC)</div><div class="mh-stat" style="color:var(--mh-brown-text);font-size:1.1rem">Preguntas frecuentes</div><div class="small mt-2"><?= $nMsj ?> mensaje<?= $nMsj === 1 ? '' : 's' ?> de contacto pendiente<?= $nMsj === 1 ? '' : 's' ?></div><?php endif; ?></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7"><section class="mh-card p-4 h-100">
                <h2 class="h6 mh-title mb-1">Solicitudes Recientes por Resolver</h2>
                <p class="small mb-3"><?= $nPend ?> solicitud<?= $nPend === 1 ? '' : 'es' ?> requiere<?= $nPend === 1 ? '' : 'n' ?> tu revisión para continuar.</p>
                <?php foreach ($pendientes as $p): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div><strong><?= folio((int) $p['id_reserva']) ?></strong> &nbsp; <strong><?= e($p['solicitante']) ?></strong> <span class="text-muted">(<?= e($p['tipo_usuario']) ?>)</span>
                            <div class="text-muted"><?= e($p['nombre_sala']) ?> · <?= fecha_mx($p['fecha_reserva']) ?></div></div>
                        <span class="mh-badge mh-badge-pendiente">Pendiente</span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$pendientes): ?><div class="small text-muted py-3">No hay solicitudes pendientes.</div><?php endif; ?>
                <div class="mt-3 d-flex gap-2">
                    <a href="<?= url('admin/solicitudes.php') ?>" class="btn btn-mh btn-sm">Ir a Solicitudes pendientes</a>
                    <?php if ($esAdmin): ?><a href="<?= url('admin/usuarios_abc.php') ?>" class="btn btn-mh-outline btn-sm">Usuarios / Roles</a><?php endif; ?>
                </div>
            </section></div>
            <div class="col-lg-5"><section class="mh-card p-4 h-100">
                <h2 class="h6 mh-title mb-1">Opiniones Recientes del Buzón</h2>
                <p class="small mb-3">Últimas opiniones de la comunidad.</p>
                <?php foreach ($opiniones as $o): ?>
                    <div class="mh-tint p-3 mb-2 small"><strong><?= folio((int) $o['id_reserva']) ?></strong> · <?= e($o['nombre_sala']) ?><div><?= e(mb_strimwidth($o['comentario'], 0, 90, '…')) ?></div></div>
                <?php endforeach; ?>
                <?php if (!$opiniones): ?><div class="small text-muted">Aún no hay opiniones.</div><?php endif; ?>
                <div class="mt-3 d-flex gap-2">
                    <a href="<?= url('admin/reportes.php?tab=sugerencias') ?>" class="btn btn-mh-outline btn-sm">Ir a Reportes e historial</a>
                    <a href="<?= url('admin/mensajes.php') ?>" class="btn btn-mh btn-sm">Mensajes (<?= $nMsj ?>)</a>
                </div>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
