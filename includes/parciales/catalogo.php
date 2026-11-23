<?php
/**
 * Catálogo de salas (lo comparten salas.php y usuario/salas.php).
 * Variables esperadas: $pagina_catalogo ('salas.php' | 'usuario/salas.php'), $es_solicitante (bool)
 */
require_once __DIR__ . '/../reservas.php';

$fCap = (int) ($_GET['cap'] ?? 0);
$fUbi = trim($_GET['ubi'] ?? '');
$fEq = trim($_GET['eq'] ?? '');
$fDisp = ($_GET['disp'] ?? '') === 'hoy';
$q = trim($_GET['q'] ?? '');
$idSel = (int) ($_GET['sala'] ?? 0);

$sql = "SELECT s.*, t.nombre_tipo FROM tblsalas s LEFT JOIN tbltipo_sala t ON t.id_tipo_sala = s.id_tipo_sala WHERE s.estado = 'Activa'";
$tipos = ''; $par = [];
if ($q !== '') { $sql .= " AND (s.nombre_sala LIKE ? OR s.ubicacion LIKE ?)"; $tipos .= 'ss'; $par[] = "%$q%"; $par[] = "%$q%"; }
if ($fCap > 0) { $sql .= " AND s.capacidad <= ?"; $tipos .= 'i'; $par[] = $fCap; }
if ($fUbi !== '') { $sql .= " AND s.ubicacion LIKE ?"; $tipos .= 's'; $par[] = "%$fUbi%"; }
if ($fEq !== '') { $sql .= " AND EXISTS (SELECT 1 FROM tblequipamiento e WHERE e.id_sala = s.id_sala AND e.estado = 'Activo' AND e.tipo_equipamiento = ?)"; $tipos .= 's'; $par[] = $fEq; }
$sql .= " ORDER BY s.nombre_sala";
$lista = db_todos($sql, $tipos, ...$par);

if ($fDisp) {
    $hoyHabil = es_dia_habil(date('Y-m-d')) ? date('Y-m-d') : date('Y-m-d', strtotime('next monday'));
    $disp = disponibilidad_dia($hoyHabil, $lista, horarios_activos());
    $lista = array_values(array_filter($lista, fn($s) => in_array('Libre', $disp[(int) $s['id_sala']] ?? [], true)));
}

// Opciones de los selects (salen de la BD, no escritas a mano)
$ubicaciones = array_column(db_todos("SELECT DISTINCT ubicacion FROM tblsalas WHERE estado = 'Activa' ORDER BY ubicacion"), 'ubicacion');
$tiposEq = array_column(db_todos("SELECT DISTINCT tipo_equipamiento FROM tblequipamiento WHERE estado = 'Activo' ORDER BY tipo_equipamiento"), 'tipo_equipamiento');
$capacidades = [20 => 'Hasta 20 personas', 40 => 'Hasta 40 personas', 80 => 'Hasta 80 personas'];

$sel = null;
foreach ($lista as $s) { if ((int) $s['id_sala'] === $idSel) { $sel = $s; } }
if (!$sel && $lista) { $sel = $lista[0]; }
$equipoSel = $sel ? db_todos("SELECT * FROM tblequipamiento WHERE id_sala = ? AND estado = 'Activo' ORDER BY nombre_equipamiento", 'i', (int) $sel['id_sala']) : [];

function qs(array $extra = []): string
{
    return '?' . http_build_query(array_filter(array_merge($_GET, $extra), fn($v) => $v !== '' && $v !== null && $v !== 0));
}
$accionReservar = $es_solicitante ? url('usuario/nueva_solicitud.php') : url('login.php');
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h3 mh-title m-0">Catálogo de salas</h1>
                    <span class="badge bg-success-subtle text-success px-3 py-2" style="background:#e3f1e4!important"><?= count($lista) ?> sala<?= count($lista) === 1 ? '' : 's' ?> disponible<?= count($lista) === 1 ? '' : 's' ?></span>
                </div>

                <form method="GET" action="<?= url($pagina_catalogo) ?>" class="mh-card p-3 mb-3">
                    <div class="row g-2 mb-2">
                        <div class="col-md-9"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Buscar por nombre de sala o ubicación..."></div>
                        <div class="col-md-3"><button class="btn btn-mh w-100" type="submit">Buscar</button></div>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-6 col-md-3"><select name="cap" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Capacidad</option>
                            <?php foreach ($capacidades as $v => $t): ?><option value="<?= $v ?>" <?= $fCap === $v ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-6 col-md-3"><select name="ubi" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Ubicación</option>
                            <?php foreach ($ubicaciones as $u): ?><option <?= $fUbi === $u ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-6 col-md-3"><select name="eq" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Equipamiento</option>
                            <?php foreach ($tiposEq as $t): ?><option <?= $fEq === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-6 col-md-3"><select name="disp" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Disponibilidad</option>
                            <option value="hoy" <?= $fDisp ? 'selected' : '' ?>>Con horarios libres hoy</option>
                        </select></div>
                        <div class="col-12 text-end"><a href="<?= url($pagina_catalogo) ?>" class="small text-decoration-underline">Limpiar filtros</a></div>
                    </div>
                </form>

                <?php if (!$lista): ?>
                    <div class="mh-card p-4 text-center small">No hay salas que coincidan con los filtros.</div>
                <?php endif; ?>
                <div class="row g-3">
                    <?php foreach ($lista as $s): $esSel = $sel && (int) $s['id_sala'] === (int) $sel['id_sala']; ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="mh-card mh-sala-card <?= $esSel ? 'sel' : '' ?> p-2 h-100 d-flex flex-column">
                                <?php if ($s['imagen_url']): ?>
                                    <img src="<?= e(url_imagen($s['imagen_url'])) ?>" alt="<?= e($s['nombre_sala']) ?>" class="mh-img" style="height:120px">
                                <?php else: ?><div class="mh-placeholder-img" style="min-height:120px"><?= icono('image', 24) ?></div><?php endif; ?>
                                <div class="p-2 d-flex flex-column flex-grow-1">
                                    <h2 class="h6 fw-bold mb-1"><?= e($s['nombre_sala']) ?></h2>
                                    <div class="small mh-muted-brown" style="font-size:.7rem"><?= e($s['ubicacion']) ?></div>
                                    <div class="small mb-2" style="font-size:.72rem"><?= (int) $s['capacidad'] ?> personas · Mín. <?= (int) $s['capacidad_minima'] ?></div>
                                    <div class="mt-auto d-flex gap-1">
                                        <a class="btn btn-mh-outline btn-sm flex-fill" style="font-size:.7rem" href="<?= e(qs(['sala' => $s['id_sala']])) ?>#detalle">Detalle de la sala</a>
                                        <a class="btn btn-mh btn-sm flex-fill" style="font-size:.7rem" href="<?= e($accionReservar . ($es_solicitante ? '?sala=' . (int) $s['id_sala'] : '')) ?>">Reservar</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-4" id="detalle">
                <?php if ($sel): ?>
                <aside class="mh-card p-3">
                    <h2 class="h6 mh-title mb-3">Detalle de la sala</h2>
                    <?php if ($sel['imagen_url']): ?><img src="<?= e(url_imagen($sel['imagen_url'])) ?>" alt="" class="mh-img mb-3" style="height:150px"><?php endif; ?>
                    <h3 class="h5 mh-title mb-1"><?= e($sel['nombre_sala']) ?></h3>
                    <div class="small mh-muted-brown mb-1"><?= e($sel['ubicacion']) ?><?= $sel['nombre_tipo'] ? ' · ' . e($sel['nombre_tipo']) : '' ?></div>
                    <p class="small"><?= e($sel['descripcion']) ?></p>
                    <div class="mh-tint p-3 small mb-3">
                        <div>Capacidad: Hasta <?= (int) $sel['capacidad'] ?> personas (Mín. <?= (int) $sel['capacidad_minima'] ?>)</div>
                        <div class="mt-2">Horario: <?= hora_corta($sel['hora_apertura']) ?> – <?= hora_corta($sel['hora_cierre']) ?></div>
                    </div>
                    <div class="fw-bold small mb-2">Equipamiento</div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($equipoSel as $eq): ?>
                            <span class="mh-badge"><?= e($eq['nombre_equipamiento']) ?><?= (int) $eq['cantidad'] > 1 ? ' (' . (int) $eq['cantidad'] . ')' : '' ?></span>
                        <?php endforeach; ?>
                        <?php if (!$equipoSel): ?><span class="small text-muted">Sin equipamiento registrado.</span><?php endif; ?>
                    </div>
                    <?php if ($es_solicitante): ?>
                        <a href="<?= e($accionReservar . '?sala=' . (int) $sel['id_sala']) ?>" class="btn btn-mh w-100">Reservar esta sala</a>
                    <?php else: ?>
                        <div class="mh-tint p-3">
                            <div class="fw-bold small mb-1"><?= icono('lock', 15) ?> Inicia sesión para reservar esta sala</div>
                            <div class="small mb-3" style="font-size:.72rem">Accede a tu cuenta o regístrate para continuar.</div>
                            <div class="d-flex gap-2">
                                <a href="<?= url('login.php#crear-cuenta') ?>" class="btn btn-mh btn-sm flex-fill">Crear cuenta</a>
                                <a href="<?= url('login.php') ?>" class="btn btn-mh-outline btn-sm flex-fill">Iniciar sesión</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
