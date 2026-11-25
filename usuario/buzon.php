<?php
/**
 * Buzón de sugerencias e historial — Responsable: Lary
 * Tablas: tblsugerencias (una opinión por reserva Realizada) y tblreserva (historial solo del usuario en sesión).
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
require_once __DIR__ . '/../includes/db.php';

[$col, $uid] = columna_reserva_usuario();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idr = (int) ($_POST['reserva'] ?? 0);
    $coment = trim($_POST['comentario'] ?? '');
    $r = db_una("SELECT id_reserva FROM tblreserva WHERE id_reserva = ? AND $col = ? AND estado_reserva = 'Realizada'", 'ii', $idr, $uid);
    if (!$r) {
        $error = 'Selecciona una reservación concluida de tu cuenta.';
    } elseif ($coment === '') {
        $error = 'Escribe tu comentario o sugerencia.';
    } elseif (db_una("SELECT 1 AS x FROM tblsugerencias WHERE id_reserva = ?", 'i', $idr)) {
        $error = 'Ya enviaste una opinión para esa reservación.';
    } else {
        db_exec("INSERT INTO tblsugerencias (id_reserva, comentario) VALUES (?, ?)", 'is', $idr, $coment);
        flash_set('success', 'Gracias, tu opinión fue enviada al buzón.');
        header('Location: ' . url('usuario/buzon.php'));
        exit;
    }
}

$concluidas = db_todos("SELECT r.id_reserva, r.fecha_reserva, s.nombre_sala FROM tblreserva r JOIN tblsalas s ON s.id_sala = r.id_sala
                         WHERE r.$col = ? AND r.estado_reserva = 'Realizada'
                           AND NOT EXISTS (SELECT 1 FROM tblsugerencias g WHERE g.id_reserva = r.id_reserva)
                         ORDER BY r.fecha_reserva DESC", 'i', $uid);
$historial = db_todos("SELECT r.*, s.nombre_sala FROM tblreserva r JOIN tblsalas s ON s.id_sala = r.id_sala
                        WHERE r.$col = ? AND r.estado_reserva IN ('Rechazada','Cancelada','Realizada')
                        ORDER BY r.fecha_reserva DESC, r.hora_inicio DESC", 'i', $uid);

$titulo = 'Buzón e historial';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo-sm">
        <?php flash_show(); ?>
        <section class="mh-card p-4 mb-3">
            <h1 class="h5 mh-title mb-1">Buzón de sugerencias sobre el uso de salas</h1>
            <p class="small mb-3">Comparte tu experiencia o reporta detalles del mobiliario/equipamiento de la sala utilizada.</p>
            <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
            <form method="POST" novalidate>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4"><label class="form-label" for="reserva"><?= icono('door', 14) ?> Reservación concluida</label>
                        <select class="form-select" id="reserva" name="reserva" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($concluidas as $c): ?><option value="<?= (int) $c['id_reserva'] ?>"><?= folio((int) $c['id_reserva']) ?> · <?= e($c['nombre_sala']) ?> (<?= fecha_mx($c['fecha_reserva']) ?>)</option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-8"><label class="form-label" for="comentario"><?= icono('chat', 14) ?> Comentario o sugerencia</label>
                        <input class="form-control" id="comentario" name="comentario" maxlength="500" required></div>
                    <div class="col-12 text-end"><button class="btn btn-mh px-4" type="submit" <?= $concluidas ? '' : 'disabled' ?>>Enviar opinión al buzón</button>
                        <?php if (!$concluidas): ?><div class="form-text">Aún no tienes reservaciones concluidas sin opinión.</div><?php endif; ?></div>
                </div>
            </form>
        </section>

        <section class="mh-card p-4">
            <h2 class="h5 mh-title mb-3">Historial de reservaciones</h2>
            <div class="table-responsive"><table class="table mh-tabla oscura align-middle mb-0">
                <thead><tr><th>Folio</th><th>Sala</th><th>Fecha</th><th>Horario</th><th>Estatus</th><th>Detalles</th></tr></thead>
                <tbody>
                <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= folio((int) $h['id_reserva']) ?></td><td><?= e($h['nombre_sala']) ?></td>
                        <td><?= fecha_mx($h['fecha_reserva']) ?></td><td><?= hora_corta($h['hora_inicio']) ?>–<?= hora_corta($h['hora_fin']) ?></td>
                        <td><span class="<?= clase_estatus($h['estado_reserva']) ?>"><?= e($h['estado_reserva']) ?></span></td>
                        <td class="small"><?= $h['estado_reserva'] === 'Realizada' ? e($h['descripcion_evento']) . ' · Completada sin incidencias' : 'Motivo: ' . e($h['motivo_rechazo_cancelacion'] ?? 'No especificado') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$historial): ?><tr><td colspan="6" class="text-center py-4 small">Todavía no tienes historial.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>