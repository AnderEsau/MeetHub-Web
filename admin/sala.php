<?php
/**
 * Detalle de una sala (una página por sala, con varias fotos) — Responsable: Leonel
 * Tablas: tblsalas / tbltipo_sala / tblequipamiento / tblimagenes_sala (galería; ver database/v5_correcciones.sql)
 * Lo ven visitantes y solicitantes. Muestra las fotos en un carrusel (la principal + la galería de tblimagenes_sala), y solo lo esencial.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/db.php';

$id = (int) ($_GET['id'] ?? 0);
$s = db_una("SELECT s.*, t.nombre_tipo FROM tblsalas s LEFT JOIN tbltipo_sala t ON t.id_tipo_sala = s.id_tipo_sala WHERE s.id_sala = ? AND s.estado = 'Activa'", 'i', $id);
if (!$s) {
    http_response_code(404);
    flash_set('danger', 'Esa sala no existe o ya no está disponible.');
    header('Location: ' . url(rol_actual() === ROL_SOLICITANTE ? 'usuario/salas.php' : 'salas.php'));
    exit;
}

// Galería: primero la imagen principal (tblsalas.imagen_url) y luego las adicionales ordenadas.
$fotos = [];
if ($s['imagen_url']) { $fotos[] = $s['imagen_url']; }
if (tabla_existe('tblimagenes_sala')) {
    $yaEsta = $s['imagen_url'] ? [basename($s['imagen_url'])] : [];
    foreach (db_todos("SELECT url_imagen FROM tblimagenes_sala WHERE id_sala = ? ORDER BY id_imagen", 'i', $id) as $f) {
        if (!in_array(basename($f['url_imagen']), $yaEsta, true)) { $fotos[] = $f['url_imagen']; }   // sin repetir la principal
    }
}
$equipo = db_todos("SELECT * FROM tblequipamiento WHERE id_sala = ? AND estado = 'Activo' ORDER BY nombre_equipamiento", 'i', $id);
$esSolicitante = rol_actual() === ROL_SOLICITANTE;
$volver = $esSolicitante ? 'usuario/salas.php' : 'salas.php';

$titulo = $s['nombre_sala'];
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <a href="<?= url($volver) ?>" class="small text-decoration-none">← Volver al catálogo</a>
        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <?php if ($fotos): ?>
                    <!-- Carrusel de Bootstrap (misma lógica de la práctica del profe): una diapositiva por foto -->
                    <div id="carruselSala" class="carousel slide mh-carrusel" data-bs-ride="carousel">
                        <?php if (count($fotos) > 1): ?>
                            <div class="carousel-indicators">
                                <?php foreach ($fotos as $i => $f): ?>
                                    <button type="button" data-bs-target="#carruselSala" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Foto <?= $i + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="carousel-inner">
                            <?php foreach ($fotos as $i => $f): ?>
                                <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                                    <img src="<?= e(url_imagen($f)) ?>" class="d-block w-100 mh-galeria-principal" alt="Foto <?= $i + 1 ?> de <?= e($s['nombre_sala']) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($fotos) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carruselSala" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span><span class="visually-hidden">Anterior</span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carruselSala" data-bs-slide="next"><span class="carousel-control-next-icon"></span><span class="visually-hidden">Siguiente</span></button>
                        <?php endif; ?>
                    </div>
                    <?php if (count($fotos) > 1): ?><p class="small text-muted mt-2 mb-0"><?= count($fotos) ?> fotografías · usa las flechas para ver más.</p><?php endif; ?>
                <?php else: ?>
                    <div class="mh-placeholder-img" style="min-height:320px"><?= icono('image', 32) ?><span>Esta sala aún no tiene fotografías</span></div>
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <section class="mh-card p-4">
                    <h1 class="h4 mh-title mb-1"><?= e($s['nombre_sala']) ?></h1>
                    <div class="small mh-muted-brown mb-3"><?= e($s['ubicacion']) ?><?= $s['nombre_tipo'] ? ' · ' . e($s['nombre_tipo']) : '' ?></div>
                    <p class="small"><?= e($s['descripcion']) ?></p>
                    <div class="mh-tint p-3 small mb-3">
                        <div>Capacidad: hasta <?= (int) $s['capacidad'] ?> personas (mínimo <?= (int) $s['capacidad_minima'] ?>)</div>
                        <div class="mt-1">Horario: <?= hora_corta($s['hora_apertura']) ?> – <?= hora_corta($s['hora_cierre']) ?></div>
                    </div>
                    <div class="fw-bold small mb-2">Equipamiento</div>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ($equipo as $eq): ?>
                            <span class="mh-badge"><?= e($eq['nombre_equipamiento']) ?><?= (int) $eq['cantidad'] > 1 ? ' (' . (int) $eq['cantidad'] . ')' : '' ?></span>
                        <?php endforeach; ?>
                        <?php if (!$equipo): ?><span class="small text-muted">Sin equipamiento registrado.</span><?php endif; ?>
                    </div>
                    <?php if ($esSolicitante): ?>
                        <a href="<?= url('usuario/nueva_solicitud.php?sala=' . $id) ?>" class="btn btn-mh w-100">Reservar esta sala</a>
                    <?php else: ?>
                        <div class="mh-tint p-3">
                            <div class="fw-bold small mb-1"><?= icono('lock', 15) ?> Inicia sesión para reservar esta sala</div>
                            <div class="d-flex gap-2 mt-2">
                                <a href="<?= url('registro.php') ?>" class="btn btn-mh btn-sm flex-fill">Crear cuenta</a>
                                <a href="<?= url('login.php') ?>" class="btn btn-mh-outline btn-sm flex-fill">Iniciar sesión</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
