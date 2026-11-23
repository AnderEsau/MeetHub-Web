<?php
/**
 * Nueva solicitud (registrar reserva) — Responsable: Leonel
 * Tablas: tblreserva, tblsalas, tblhorarios. Valida anticipación, aforo, horario y traslapes (includes/reservas.php).
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
require_once __DIR__ . '/../includes/reservas.php';

$salas = salas_activas();
$horarios = horarios_activos();
$eventos = ['Práctica', 'Reunión', 'Taller', 'Exposición', 'Estudio en grupo', 'Videoconferencia', 'Otro'];
$errores = [];

$minFecha = date('Y-m-d', strtotime('+' . DIAS_ANTICIPACION . ' days'));
while (!es_dia_habil($minFecha)) { $minFecha = date('Y-m-d', strtotime($minFecha . ' +1 day')); }

$v = [
    'sala' => (int) ($_GET['sala'] ?? $_POST['sala'] ?? ($salas[0]['id_sala'] ?? 0)),
    'fecha' => $_POST['fecha'] ?? $_GET['fecha'] ?? $minFecha,
    'hi' => $_POST['hi'] ?? '09:00',
    'hf' => $_POST['hf'] ?? '11:00',
    'evento' => $_POST['evento'] ?? '',
    'asistentes' => $_POST['asistentes'] ?? '',
    'actividad' => trim($_POST['actividad'] ?? ''),
];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['fecha'])) { $v['fecha'] = $minFecha; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($v['actividad'] === '' || !in_array($v['evento'], $eventos, true) || $v['asistentes'] === '' || !ctype_digit((string) $v['asistentes'])) {
        $errores[] = 'Completa todos los campos (tipo de evento, asistentes y actividad).';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $v['hi']) || !preg_match('/^\d{2}:\d{2}$/', $v['hf'])) {
        $errores[] = 'Selecciona hora de inicio y fin.';
    } else {
        $errores = validar_solicitud(['id_sala' => $v['sala'], 'fecha' => $v['fecha'], 'hora_inicio' => $v['hi'], 'hora_fin' => $v['hf'], 'asistentes' => $v['asistentes']]);
    }
    if (!$errores) {
        [$col, $uid] = columna_reserva_usuario();
        $hi = $v['hi'] . ':00'; $hf = $v['hf'] . ':00'; $n = (int) $v['asistentes'];
        $motivo = mb_substr($v['actividad'], 0, 150);
        $idNuevo = db_exec("INSERT INTO tblreserva (id_sala, $col, fecha_solicitud, fecha_reserva, hora_inicio, hora_fin, tipo_evento, descripcion_evento, motivo_evento, total_asistentes, estado_reserva)
                            VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'Pendiente')",
                           'iissssssi', $v['sala'], $uid, $v['fecha'], $hi, $hf, $v['evento'], $v['actividad'], $motivo, $n);
        flash_set('success', 'Solicitud ' . folio($idNuevo) . ' registrada. Queda pendiente de aprobación del encargado.');
        header('Location: ' . url('usuario/mis_solicitudes.php'));
        exit;
    }
}

// Pre-seleccionar horas si viene ?h= desde disponibilidad (id de bloque)
if (isset($_GET['h']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    foreach ($horarios as $h) { if ((int) $h['id_horario'] === (int) $_GET['h']) { $v['hi'] = hora_corta($h['hora_inicio']); $v['hf'] = hora_corta($h['hora_fin']); } }
}

$salaSel = null;
foreach ($salas as $s) { if ((int) $s['id_sala'] === $v['sala']) { $salaSel = $s; } }
$salaSel = $salaSel ?? ($salas[0] ?? null);

// Semana de la fecha elegida (lunes a viernes) para la sala elegida
$lunes = strtotime('monday this week', strtotime($v['fecha']));
$dias = []; for ($i = 0; $i < 5; $i++) { $dias[] = date('Y-m-d', strtotime("+$i day", $lunes)); }
$semana = [];
foreach ($dias as $d) { $semana[$d] = $salaSel ? disponibilidad_dia($d, [$salaSel], $horarios) : []; }

$opcionesHora = [];
for ($m = 8 * 60; $m <= 16 * 60 + 30; $m += 30) { $opcionesHora[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60); }

$titulo = 'Nueva solicitud';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <div class="row g-4">
            <div class="col-lg-5">
                <section class="mh-card p-4">
                    <h1 class="h5 mh-title mb-3">Registrar Solicitud</h1>
                    <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>
                    <div class="mh-tint p-2 px-3 small fw-semibold mb-3"><?= icono('user', 14) ?> Solicitante: <?= e($_SESSION['usuario_nombre']) ?> (<?= e(ucfirst($_SESSION['usuario_tipo'])) ?> · <?= (int) $_SESSION['usuario_id'] ?>)</div>
                    <form method="POST" id="formSolicitud" novalidate>
                        <div class="mb-3"><label class="form-label" for="sala"><?= icono('door', 14) ?> Sala</label>
                            <select class="form-select" id="sala" name="sala" required>
                                <?php foreach ($salas as $s): ?><option value="<?= (int) $s['id_sala'] ?>" <?= $v['sala'] === (int) $s['id_sala'] ? 'selected' : '' ?>><?= e($s['nombre_sala']) ?> (<?= (int) $s['capacidad_minima'] ?>–<?= (int) $s['capacidad'] ?> pers.)</option><?php endforeach; ?>
                            </select></div>
                        <div class="mb-3"><label class="form-label" for="fecha"><?= icono('calendar', 14) ?> Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" min="<?= e($minFecha) ?>" value="<?= e($v['fecha']) ?>" required></div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label" for="hi"><?= icono('clock', 14) ?> Hora inicio</label>
                                <select class="form-select" id="hi" name="hi"><?php foreach ($opcionesHora as $o): ?><option <?= $v['hi'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                            <div class="col-6"><label class="form-label" for="hf"><?= icono('clock', 14) ?> Hora fin</label>
                                <select class="form-select" id="hf" name="hf"><?php foreach ($opcionesHora as $o): ?><option <?= $v['hf'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-7"><label class="form-label" for="evento">Tipo de evento</label>
                                <select class="form-select" id="evento" name="evento" required><option value="">Selecciona...</option>
                                    <?php foreach ($eventos as $ev): ?><option <?= $v['evento'] === $ev ? 'selected' : '' ?>><?= e($ev) ?></option><?php endforeach; ?></select></div>
                            <div class="col-5"><label class="form-label" for="asistentes"><?= icono('people', 14) ?> Asistentes</label>
                                <input type="number" class="form-control" id="asistentes" name="asistentes" min="1" value="<?= e($v['asistentes']) ?>" required></div>
                            <?php if ($salaSel): ?><div class="col-12 form-text mh-muted-brown">Aforo mínimo requerido: <?= (int) $salaSel['capacidad_minima'] ?> personas · Máx: <?= (int) $salaSel['capacidad'] ?></div><?php endif; ?>
                        </div>
                        <div class="mb-3"><label class="form-label" for="actividad"><?= icono('doc', 14) ?> Actividad</label>
                            <textarea class="form-control" id="actividad" name="actividad" rows="3" maxlength="255" placeholder="Describe la actividad a realizar" required><?= e($v['actividad']) ?></textarea></div>
                        <button class="btn btn-mh w-100 py-2" type="submit">Confirmar reservación</button>
                        <p class="small text-center mh-muted-brown mt-3 mb-0">Recuerda solicitar con <?= DIAS_ANTICIPACION ?> días de anticipación.</p>
                    </form>
                </section>
            </div>
            <div class="col-lg-7">
                <section class="mh-card p-4">
                    <div class="d-flex justify-content-between align-items-start mb-1"><h2 class="h5 mh-title mb-0">Disponibilidad Semanal</h2>
                        <span class="small mh-muted-brown"><?= e($salaSel['nombre_sala'] ?? '') ?></span></div>
                    <div class="small mb-3"><?= date('d', $lunes) ?> – <?= date('d/m/Y', strtotime($dias[4])) ?></div>
                    <div class="table-responsive"><table class="table mh-tabla oscura text-center align-middle mb-3">
                        <thead><tr><th>Hora</th><?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie'] as $i => $n): ?><th><?= $n ?> <?= date('d', strtotime($dias[$i])) ?></th><?php endforeach; ?></tr></thead>
                        <tbody><?php foreach ($horarios as $h): ?><tr>
                            <td class="fw-bold" style="font-size:.72rem"><?= hora_corta($h['hora_inicio']) ?> – <?= hora_corta($h['hora_fin']) ?></td>
                            <?php foreach ($dias as $d): ?><td><?= celda_disp($salaSel ? $semana[$d][(int) $salaSel['id_sala']][(int) $h['id_horario']] : 'Cerrado') ?></td><?php endforeach; ?>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                    <div class="small mh-muted-brown">Consulta los horarios antes de confirmar tu reservación.<br>Las solicitudes pendientes están sujetas a aprobación.</div>
                </section>
            </div>
        </div>
    </div>
</main>
<script>
// Al cambiar sala o fecha se recarga la tabla de disponibilidad (conserva lo ya escrito en la URL)
['sala', 'fecha'].forEach(function (id) {
  document.getElementById(id).addEventListener('change', function () {
    var s = document.getElementById('sala').value, f = document.getElementById('fecha').value;
    if (s && f) window.location.href = '?sala=' + encodeURIComponent(s) + '&fecha=' + encodeURIComponent(f);
  });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
