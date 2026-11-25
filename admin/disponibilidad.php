<?php
/**
 * Disponibilidad (calendario en tiempo real) — Responsable: Leonel
 * Tablas: tblsalas, tblhorarios, tblreserva. Vista "Tabla de horarios" (un día, todas las salas) y "Vista semanal" (una sala, lunes a viernes).
 */
require_once __DIR__ . '/includes/reservas.php';

$salas = salas_activas();
$horarios = horarios_activos();

$vista = ($_GET['vista'] ?? 'tabla') === 'semanal' ? 'semanal' : 'tabla';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) { $fecha = date('Y-m-d'); }
$idSala = (int) ($_GET['sala'] ?? 0);

$ids = array_column($salas, 'id_sala');
$salaSel = in_array($idSala, array_map('intval', $ids), true) ? $idSala : 0;
$mostrar = $salaSel ? array_values(array_filter($salas, function ($s) use ($salaSel) { return (int) $s['id_sala'] === $salaSel; })) : $salas;

// Destino del botón "Reservar": solicitante -> nueva solicitud; los demás -> login
require_once __DIR__ . '/includes/auth.php';
$destino = rol_actual() === ROL_SOLICITANTE ? 'usuario/nueva_solicitud.php' : 'login.php';

$titulo = 'Disponibilidad';
require_once __DIR__ . '/includes/header.php';

function enlace_reserva(string $destino, int $sala, string $fecha, string $celda): string
{
    return '<a href="' . e(url($destino) . '?sala=' . $sala . '&fecha=' . $fecha) . '" class="text-decoration-none">' . $celda . '</a>';
}
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <h1 class="h3 mh-title mb-1">Calendario de Disponibilidad en Tiempo Real</h1>
        <p class="small mb-3">Consulta qué salas y horarios están libres, pendientes por confirmar u ocupados antes de realizar tu solicitud (Lunes a Viernes de 08:00 a 16:30).</p>

        <section class="mh-card p-3 mb-3">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="vista" value="<?= e($vista) ?>" id="campoVista">
                <div class="col-md-3">
                    <label class="form-label" for="sala">Espacio</label>
                    <select class="form-select" id="sala" name="sala">
                        <option value="0">Todas las salas</option>
                        <?php foreach ($salas as $s): ?>
                            <option value="<?= (int) $s['id_sala'] ?>" <?= $salaSel === (int) $s['id_sala'] ? 'selected' : '' ?>><?= e($s['nombre_sala']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?= e($fecha) ?>" required>
                </div>
                <div class="col-md-4">
                    <div class="btn-group w-100" role="group">
                        <a class="btn <?= $vista === 'semanal' ? 'btn-mh' : 'btn-mh-outline' ?>" href="?vista=semanal&sala=<?= $salaSel ?>&fecha=<?= e($fecha) ?>">Vista Semanal</a>
                        <a class="btn <?= $vista === 'tabla' ? 'btn-mh' : 'btn-mh-outline' ?>" href="?vista=tabla&sala=<?= $salaSel ?>&fecha=<?= e($fecha) ?>">Tabla de Horarios</a>
                    </div>
                </div>
                <div class="col-md-2"><button class="btn btn-mh w-100" type="submit">Consultar</button></div>
            </form>
        </section>

        <?php if (!es_dia_habil($fecha) && $vista === 'tabla'): ?>
            <div class="alert alert-warning small">La fecha elegida cae en fin de semana: la biblioteca solo atiende de lunes a viernes.</div>
        <?php endif; ?>

        <section class="mh-card mb-3 overflow-hidden">
            <div class="table-responsive">
            <?php if ($vista === 'tabla'): $disp = disponibilidad_dia($fecha, $mostrar, $horarios); ?>
                <table class="table table-bordered mh-tabla text-center mb-0 align-middle">
                    <thead><tr>
                        <th class="py-3">Horario</th>
                        <?php foreach ($mostrar as $s): ?>
                            <th class="py-3"><?= e($s['nombre_sala']) ?><br><small class="fw-normal">(Cap. <?= (int) $s['capacidad'] ?> · Mín. <?= (int) $s['capacidad_minima'] ?>)</small></th>
                        <?php endforeach; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($horarios as $h): ?>
                        <tr>
                            <td class="fw-bold py-3"><?= hora_corta($h['hora_inicio']) ?> – <?= hora_corta($h['hora_fin']) ?></td>
                            <?php foreach ($mostrar as $s): $est = $disp[(int) $s['id_sala']][(int) $h['id_horario']]; ?>
                                <td><?= $est === 'Libre' || $est === 'Pendiente' ? enlace_reserva($destino, (int) $s['id_sala'], $fecha, celda_disp($est, true)) : celda_disp($est, true) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else:
                $sala = $mostrar[0] ?? null;
                $lunes = strtotime('monday this week', strtotime($fecha));
                $dias = []; for ($i = 0; $i < 5; $i++) { $dias[] = date('Y-m-d', strtotime("+$i day", $lunes)); }
                $semana = [];
                foreach ($dias as $d) { $semana[$d] = $sala ? disponibilidad_dia($d, [$sala], $horarios) : []; }
                $nombresDia = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
            ?>
                <div class="p-3 pb-0 small fw-semibold"><?= $sala ? e($sala['nombre_sala']) : 'Sin salas activas' ?> · Semana del <?= fecha_mx($dias[0]) ?> al <?= fecha_mx($dias[4]) ?>
                    <?php if (!$salaSel): ?><span class="text-muted fw-normal">(elige una sala en "Espacio" para ver otra)</span><?php endif; ?></div>
                <table class="table table-bordered mh-tabla oscura text-center mb-0 align-middle mt-3">
                    <thead><tr><th>Hora</th><?php foreach ($dias as $i => $d): ?><th><?= $nombresDia[$i] ?> <?= date('d/m', strtotime($d)) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php foreach ($horarios as $h): ?>
                        <tr>
                            <td class="fw-bold"><?= hora_corta($h['hora_inicio']) ?> – <?= hora_corta($h['hora_fin']) ?></td>
                            <?php foreach ($dias as $d): $est = $sala ? $semana[$d][(int) $sala['id_sala']][(int) $h['id_horario']] : 'Cerrado'; ?>
                                <td><?= $sala && ($est === 'Libre' || $est === 'Pendiente') ? enlace_reserva($destino, (int) $sala['id_sala'], $d, celda_disp($est)) : celda_disp($est) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            </div>
        </section>

        <section class="mh-card p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="small fw-semibold">¿Encontraste un horario disponible o sin confirmar? Recuerda solicitarlo con al menos <?= DIAS_ANTICIPACION ?> días de anticipación.</div>
            <a href="<?= url($destino) ?>" class="btn btn-mh px-4">Reservar horario seleccionado</a>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
