<?php
/**
 * Reportes e historial de reservas + listado de sugerencias — Responsable: Lary
 * Filtros: fechas, tipo de usuario, usuario/matrícula y estatus. "Exportar PDF" abre el diálogo de impresión (Guardar como PDF).
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol([ROL_BIBLIOTECARIO, ROL_ADMIN]);
require_once __DIR__ . '/../includes/db.php';

$tab = ($_GET['tab'] ?? '') === 'sugerencias' ? 'sugerencias' : 'reservas';
$ini = $_GET['ini'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-t');
foreach (['ini', 'fin'] as $k) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $$k)) { $$k = $k === 'ini' ? date('Y-m-01') : date('Y-m-t'); } }
$tipo = $_GET['tipo'] ?? '';
$clave = trim($_GET['usuario'] ?? '');
$est = $_GET['estado'] ?? '';
$estados = ['Pendiente', 'Aprobada', 'Rechazada', 'Cancelada', 'Realizada'];
$error = '';
if ($ini > $fin) { $error = 'La fecha inicial no puede ser mayor que la final.'; }

$filas = [];
if ($tab === 'reservas' && !$error) {
    $sql = SQL_RESERVAS . " WHERE r.fecha_reserva BETWEEN ? AND ?"; $t = 'ss'; $p = [$ini, $fin];
    if ($tipo === 'Alumno') { $sql .= " AND r.id_alumno IS NOT NULL"; }
    elseif ($tipo === 'Docente') { $sql .= " AND r.matricula IS NOT NULL"; }
    elseif ($tipo === 'Externo') { $sql .= " AND r.id_externo IS NOT NULL"; }
    if ($clave !== '') {
        $sql .= " AND (CAST(COALESCE(r.id_alumno, r.matricula, r.id_externo) AS CHAR) = ? OR CONCAT(a.nombre,' ',a.apellido_paterno) LIKE ? OR CONCAT(t.nombre,' ',t.apellido_paterno) LIKE ? OR CONCAT(x.nombre,' ',x.apellido_paterno) LIKE ?)";
        $t .= 'ssss'; array_push($p, $clave, "%$clave%", "%$clave%", "%$clave%");
    }
    if (in_array($est, $estados, true)) { $sql .= " AND r.estado_reserva = ?"; $t .= 's'; $p[] = $est; }
    $sql .= " ORDER BY r.fecha_reserva DESC, r.hora_inicio DESC";
    $filas = db_todos($sql, $t, ...$p);
}
$sugs = [];
if ($tab === 'sugerencias') {
    $sugs = db_todos("SELECT g.*, s.nombre_sala, r.fecha_reserva, r.id_alumno, r.matricula, r.id_externo,
                             TRIM(COALESCE(CONCAT(a.nombre,' ',a.apellido_paterno), CONCAT(t.nombre,' ',t.apellido_paterno), CONCAT(x.nombre,' ',x.apellido_paterno))) AS solicitante
                        FROM tblsugerencias g JOIN tblreserva r ON r.id_reserva = g.id_reserva JOIN tblsalas s ON s.id_sala = r.id_sala
                        LEFT JOIN tblalumnos a ON a.id_alumno = r.id_alumno LEFT JOIN tbltrabajadores t ON t.matricula = r.matricula LEFT JOIN tblexterno x ON x.id_externo = r.id_externo
                       ORDER BY g.fecha_envio DESC");
}

$titulo = 'Reportes e historial';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div><h1 class="h3 mh-title mb-1">Reportes Periódicos e Historial de Reservas</h1>
                <p class="small mb-0">Consulta todas las reservas y fíltralas por fechas, por tipo de usuario y por usuario/matrícula.</p></div>
            <button class="btn btn-mh-navy no-print" type="button" onclick="window.print()"><?= icono('pdf', 16) ?> Exportar Reporte PDF</button>
        </div>
        <div class="mh-pestanas mb-3 no-print">
            <a class="<?= $tab === 'reservas' ? 'active' : '' ?>" href="?tab=reservas">Historial de reservas</a>
            <a class="<?= $tab === 'sugerencias' ? 'active' : '' ?>" href="?tab=sugerencias">Sugerencias del buzón</a>
        </div>

        <?php if ($tab === 'reservas'): ?>
        <form method="GET" class="mh-card p-3 mb-3 no-print">
            <input type="hidden" name="tab" value="reservas">
            <div class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label">Fecha inicial</label><input type="date" class="form-control" name="ini" value="<?= e($ini) ?>" required></div>
                <div class="col-md-2"><label class="form-label">Fecha final</label><input type="date" class="form-control" name="fin" value="<?= e($fin) ?>" required></div>
                <div class="col-md-2"><label class="form-label">Tipo de usuario</label><select class="form-select" name="tipo"><option value="">Todos</option><?php foreach (['Alumno', 'Docente', 'Externo'] as $o): ?><option <?= $tipo === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Usuario / Matrícula</label><input class="form-control" name="usuario" value="<?= e($clave) ?>" placeholder="Matrícula, ID o nombre"></div>
                <div class="col-md-1"><label class="form-label">Estatus</label><select class="form-select" name="estado"><option value="">Todos</option><?php foreach ($estados as $o): ?><option <?= $est === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><button class="btn btn-mh w-100">Generar Reporte</button></div>
            </div>
        </form>
        <?php if ($error): ?><div class="alert alert-danger small"><?= e($error) ?></div><?php endif; ?>
        <section class="mh-card p-4">
            <h2 class="h6 mh-title mb-3">Historial y Reporte del Periodo Seleccionado (<?= fecha_mx($ini) ?> al <?= fecha_mx($fin) ?>)</h2>
            <div class="table-responsive"><table class="table mh-tabla align-middle mb-3">
                <thead><tr><th>Folio</th><th>Fecha y Horario</th><th>Solicitante (Matrícula / ID)</th><th>Tipo Usuario</th><th>Sala</th><th>Asistentes</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr><td class="fw-bold"><?= folio((int) $f['id_reserva']) ?></td>
                        <td><?= fecha_mx($f['fecha_reserva']) ?> · <?= hora_corta($f['hora_inicio']) ?>–<?= hora_corta($f['hora_fin']) ?></td>
                        <td><strong><?= e($f['solicitante']) ?></strong> (<?= (int) $f['clave_usuario'] ?>)</td>
                        <td><?= e($f['tipo_usuario']) ?></td><td><?= e($f['nombre_sala']) ?></td><td><?= (int) $f['total_asistentes'] ?> pers.</td>
                        <td><span class="<?= clase_estatus($f['estado_reserva']) ?>"><?= $f['estado_reserva'] === 'Realizada' ? 'Realizada / Aprobada' : e($f['estado_reserva']) ?></span></td></tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="7" class="text-center py-4 small">No hay reservas con esos filtros.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
            <div class="mh-tabla-pie">Total del periodo: <?= count($filas) ?> registros mostrados.</div>
        </section>
        <?php else: ?>
        <section class="mh-card p-4">
            <h2 class="h6 mh-title mb-3">Sugerencias recibidas en el buzón</h2>
            <div class="table-responsive"><table class="table mh-tabla align-middle mb-3">
                <thead><tr><th>Fecha de envío</th><th>Folio</th><th>Sala</th><th>Solicitante</th><th>Comentario</th></tr></thead>
                <tbody>
                <?php foreach ($sugs as $g): ?>
                    <tr><td><?= date('d/m/Y H:i', strtotime($g['fecha_envio'])) ?></td><td class="fw-bold"><?= folio((int) $g['id_reserva']) ?></td><td><?= e($g['nombre_sala']) ?></td><td><?= e($g['solicitante']) ?></td><td><?= e($g['comentario']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$sugs): ?><tr><td colspan="5" class="text-center py-4 small">Aún no hay sugerencias.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
            <div class="mh-tabla-pie"><?= count($sugs) ?> sugerencia(s).</div>
        </section>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>