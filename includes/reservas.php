<?php
/**
 * Lógica compartida de reservas: disponibilidad por bloque y validaciones de la solicitud.
 * Responsable: Leonel (la usan disponibilidad.php, catálogo y usuario/nueva_solicitud.php).
 */
require_once __DIR__ . '/db.php';

/** Bloques horarios activos (tblhorarios). */
function horarios_activos(): array
{
    return db_todos("SELECT * FROM tblhorarios WHERE estado = 'Activo' ORDER BY hora_inicio");
}

function salas_activas(): array
{
    return db_todos("SELECT s.*, t.nombre_tipo FROM tblsalas s LEFT JOIN tbltipo_sala t ON t.id_tipo_sala = s.id_tipo_sala
                      WHERE s.estado = 'Activa' ORDER BY s.nombre_sala");
}

function es_dia_habil(string $fecha): bool
{
    return (int) date('N', strtotime($fecha)) <= 5;
}

/**
 * Estado de cada bloque para una fecha: [id_sala][id_horario] => 'Libre' | 'Pendiente' | 'Ocupado' | 'Cerrado'
 * Ocupado = reserva Aprobada que se traslapa; Pendiente = solo hay solicitudes por confirmar.
 */
function disponibilidad_dia(string $fecha, array $salas, array $horarios): array
{
    $mapa = [];
    $habil = es_dia_habil($fecha);
    $reservas = db_todos("SELECT id_sala, hora_inicio, hora_fin, estado_reserva FROM tblreserva
                           WHERE fecha_reserva = ? AND estado_reserva IN ('Pendiente','Aprobada')", 's', $fecha);
    foreach ($salas as $s) {
        foreach ($horarios as $h) {
            $est = 'Libre';
            if (!$habil || $h['hora_inicio'] < $s['hora_apertura'] || $h['hora_fin'] > $s['hora_cierre']) {
                $est = 'Cerrado';
            } else {
                foreach ($reservas as $r) {
                    if ((int) $r['id_sala'] === (int) $s['id_sala'] && $r['hora_inicio'] < $h['hora_fin'] && $r['hora_fin'] > $h['hora_inicio']) {
                        if ($r['estado_reserva'] === 'Aprobada') { $est = 'Ocupado'; break; }
                        $est = 'Pendiente';
                    }
                }
            }
            $mapa[(int) $s['id_sala']][(int) $h['id_horario']] = $est;
        }
    }
    return $mapa;
}

/** Clase CSS + texto para una celda de disponibilidad. */
function celda_disp(string $estado, bool $largo = false): string
{
    $m = [
        'Libre'     => ['mh-disp-libre', 'Disponible'],
        'Ocupado'   => ['mh-disp-ocupado', $largo ? 'Ocupado (Confirmado)' : 'OCUPADO'],
        'Pendiente' => ['mh-disp-pendiente', $largo ? 'Pendiente (Sin confirmar)' : 'PENDIENTE'],
        'Cerrado'   => ['mh-disp-fuera', 'Sin servicio'],
    ];
    [$c, $t] = $m[$estado] ?? $m['Cerrado'];
    if (!$largo && $estado === 'Libre') { $t = 'LIBRE'; }
    return '<span class="mh-disp ' . $c . '">' . $t . '</span>';
}

/**
 * Valida una solicitud nueva. Devuelve lista de errores (vacía = válida).
 * $d: id_sala, fecha, hora_inicio, hora_fin, asistentes
 */
function validar_solicitud(array $d): array
{
    $err = [];
    $sala = db_una("SELECT * FROM tblsalas WHERE id_sala = ? AND estado = 'Activa'", 'i', (int) $d['id_sala']);
    if (!$sala) { return ['Selecciona una sala válida.']; }

    $f = $d['fecha'];
    $ts = strtotime($f);
    if (!$ts || date('Y-m-d', $ts) !== $f) { return ['Selecciona una fecha válida.']; }
    if (!es_dia_habil($f)) { $err[] = 'Solo se reserva de lunes a viernes.'; }
    $minimo = strtotime('+' . DIAS_ANTICIPACION . ' days', strtotime(date('Y-m-d')));
    if ($ts < $minimo) { $err[] = 'Debes solicitar con al menos ' . DIAS_ANTICIPACION . ' días de anticipación.'; }

    $hi = $d['hora_inicio'] . ':00';
    $hf = $d['hora_fin'] . ':00';
    if ($hf <= $hi) { $err[] = 'La hora de fin debe ser posterior a la hora de inicio.'; }
    if ($hi < $sala['hora_apertura'] || $hf > $sala['hora_cierre']) {
        $err[] = 'El horario debe estar dentro del servicio de la sala (' . hora_corta($sala['hora_apertura']) . ' a ' . hora_corta($sala['hora_cierre']) . ').';
    }
    $n = (int) $d['asistentes'];
    if ($n < (int) $sala['capacidad_minima']) { $err[] = 'La sala requiere un aforo mínimo de ' . $sala['capacidad_minima'] . ' personas.'; }
    if ($n > (int) $sala['capacidad']) { $err[] = 'La sala permite máximo ' . $sala['capacidad'] . ' personas.'; }

    if (!$err) {
        $choque = db_una("SELECT 1 AS x FROM tblreserva WHERE id_sala = ? AND fecha_reserva = ? AND estado_reserva = 'Aprobada'
                           AND hora_inicio < ? AND hora_fin > ? LIMIT 1", 'isss', (int) $d['id_sala'], $f, $hf, $hi);
        if ($choque) { $err[] = 'Ese horario ya está ocupado por una reserva confirmada.'; }
    }
    return $err;
}
