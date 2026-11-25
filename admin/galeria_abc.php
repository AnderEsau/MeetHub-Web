<?php
/**
 * Galería de cada sala = imágenes del carrusel del detalle (Listado, Subir y Eliminar) — Responsable: Leonel.
 * Tabla: tblimagenes_sala (id_imagen, id_sala, url_imagen). Es la misma tabla que usa la app móvil.
 * Misma lógica de la práctica del carrusel: listado con vista previa, subir con validaciones y eliminar con confirmación.
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_BIBLIOTECARIO);
require_once __DIR__ . '/../includes/db.php';

$hayTabla = tabla_existe('tblimagenes_sala');
$idSala = (int) ($_GET['sala'] ?? $_POST['id_sala'] ?? 0);
$errores = [];

if ($hayTabla && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $sala = db_una("SELECT id_sala FROM tblsalas WHERE id_sala = ?", 'i', $idSala);
    if (!$sala) {
        $errores[] = 'La sala no existe.';
    } elseif ($accion === 'agregar') {
        $rutas = guardar_imagenes_multiples('fotos', 'salas/galeria', $errores);
        if (!$rutas && !$errores) { $errores[] = 'Selecciona al menos una imagen.'; }
        foreach ($rutas as $r) { db_exec("INSERT INTO tblimagenes_sala (id_sala, url_imagen) VALUES (?,?)", 'is', $idSala, $r); }
        if ($rutas && !$errores) {
            flash_set('success', count($rutas) === 1 ? 'Imagen agregada al carrusel.' : count($rutas) . ' imágenes agregadas al carrusel.');
            header('Location: ' . url('admin/galeria_abc.php?sala=' . $idSala)); exit;
        }
    } elseif ($accion === 'eliminar') {
        $img = db_una("SELECT * FROM tblimagenes_sala WHERE id_imagen = ? AND id_sala = ?", 'ii', (int) ($_POST['id_imagen'] ?? 0), $idSala);
        if ($img) {
            db_exec("DELETE FROM tblimagenes_sala WHERE id_imagen = ?", 'i', (int) $img['id_imagen']);
            // Se borra también el archivo, solo si está dentro de upload/salas/ y ya nadie más lo usa
            $ruta = $img['url_imagen'];
            $usada = db_una("SELECT 1 AS x FROM tblimagenes_sala WHERE url_imagen = ? LIMIT 1", 's', $ruta)
                  || db_una("SELECT 1 AS x FROM tblsalas WHERE imagen_url = ? LIMIT 1", 's', $ruta);
            $base = realpath(__DIR__ . '/../upload/salas');
            $fisico = realpath(__DIR__ . '/../' . $ruta);
            if (!$usada && $base && $fisico && strpos($fisico, $base . DIRECTORY_SEPARATOR) === 0 && is_file($fisico)) { @unlink($fisico); }
            flash_set('success', 'Imagen eliminada del carrusel.');
        }
        header('Location: ' . url('admin/galeria_abc.php?sala=' . $idSala)); exit;
    }
}

$salas = db_todos("SELECT s.id_sala, s.nombre_sala, s.estado, s.imagen_url,
                          " . ($hayTabla ? "(SELECT COUNT(*) FROM tblimagenes_sala g WHERE g.id_sala = s.id_sala)" : "0") . " AS total
                     FROM tblsalas s ORDER BY s.id_sala");
$salaSel = null;
foreach ($salas as $s) { if ((int) $s['id_sala'] === $idSala) { $salaSel = $s; } }
$fotos = ($hayTabla && $salaSel) ? db_todos("SELECT * FROM tblimagenes_sala WHERE id_sala = ? ORDER BY id_imagen", 'i', $idSala) : [];

$titulo = 'Galería de salas';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Galería de las salas (carrusel)</h1>
        <p class="small mb-3">Administra las fotografías que se muestran en el carrusel del detalle de cada sala.</p>
        <?php if (!$hayTabla): ?>
            <div class="alert alert-warning small">La tabla <b>tblimagenes_sala</b> no existe en la base de datos. Importa la base oficial V3 para usar la galería.</div>
        <?php endif; ?>
        <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>

        <div class="row g-3">
            <div class="col-lg-5"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Salas</h2>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>Sala</th><th>Fotos en galería</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($salas as $s): ?>
                        <tr<?= (int) $s['id_sala'] === $idSala ? ' class="table-active"' : '' ?>>
                            <td class="fw-semibold"><?= e($s['nombre_sala']) ?></td>
                            <td><?= (int) $s['total'] ?></td>
                            <td><a class="btn btn-mh-outline btn-sm" href="?sala=<?= (int) $s['id_sala'] ?>">Gestionar</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$salas): ?><tr><td colspan="3" class="text-center py-4 small">Sin salas registradas.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </section></div>

            <div class="col-lg-7"><section class="mh-card p-4">
                <?php if (!$salaSel): ?>
                    <p class="small mb-0">Elige una sala de la lista para ver y administrar las imágenes de su carrusel.</p>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h2 class="h6 mh-title mb-0">Carrusel de «<?= e($salaSel['nombre_sala']) ?>»</h2>
                        <a class="btn btn-mh-outline btn-sm" href="<?= url('sala.php?id=' . (int) $salaSel['id_sala']) ?>" target="_blank" rel="noopener">Ver detalle en el sitio</a>
                    </div>
                    <p class="small text-muted">Las fotos salen en el carrusel en el orden en que se subieron. La foto principal se cambia en <a href="<?= url('admin/salas_abc.php?editar=' . (int) $salaSel['id_sala']) ?>">Salas (ABC)</a>.</p>
                    <div class="table-responsive mb-4"><table class="table mh-tabla align-middle mb-0">
                        <thead><tr><th>#</th><th>Vista previa</th><th>Archivo</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php if ($salaSel['imagen_url']): ?>
                            <tr><td>—</td><td><img src="<?= e(url_imagen($salaSel['imagen_url'])) ?>" alt="" class="mh-miniatura" style="cursor:default"></td>
                                <td class="small"><?= e(basename($salaSel['imagen_url'])) ?></td><td><span class="mh-badge mh-badge-activo">Principal</span></td></tr>
                        <?php endif; ?>
                        <?php foreach ($fotos as $i => $g): ?>
                            <tr><td><?= $i + 1 ?></td>
                                <td><img src="<?= e(url_imagen($g['url_imagen'])) ?>" alt="" class="mh-miniatura" style="cursor:default"></td>
                                <td class="small"><?= e(basename($g['url_imagen'])) ?></td>
                                <td><form method="POST" class="d-inline"><input type="hidden" name="id_sala" value="<?= (int) $salaSel['id_sala'] ?>"><input type="hidden" name="id_imagen" value="<?= (int) $g['id_imagen'] ?>">
                                    <button class="btn btn-mh-danger btn-sm" name="accion" value="eliminar" data-confirm="¿Eliminar esta imagen del carrusel?">Eliminar</button></form></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$fotos): ?><tr><td colspan="4" class="text-center py-3 small">Esta sala no tiene imágenes en la galería todavía.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                    <?php if ($hayTabla): ?>
                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="id_sala" value="<?= (int) $salaSel['id_sala'] ?>">
                        <label class="form-label" for="fotos">Subir imágenes al carrusel (JPG, PNG o WEBP, máx. 3 MB cada una)</label>
                        <input type="file" class="form-control mb-2" id="fotos" name="fotos[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
                        <button class="btn btn-mh" name="accion" value="agregar" type="submit">+ Subir imágenes</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
