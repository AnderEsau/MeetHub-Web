<?php
/** Funciones de ayuda compartidas. */
require_once __DIR__ . '/../config/app.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();   // necesario para los mensajes flash en páginas públicas (contacto, etc.)
}

/** Escapa texto para imprimirlo en HTML (siempre usar al mostrar datos de la BD). */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Construye una URL del proyecto: url('salas.php') */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

/** Mensajes flash (alertas que se muestran una sola vez tras redirigir). */
function flash_set(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_show(): void
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['tipo']) . ' alert-dismissible fade show" role="alert">'
            . e($f['mensaje'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
    }
}

/** Clase Bootstrap/MeetHub para el estatus de una reserva. */
function clase_estatus(string $estatus): string
{
    $mapa = [
        'pendiente'  => 'mh-badge mh-badge-pendiente',
        'aprobada'   => 'mh-badge mh-badge-aprobada',
        'rechazada'  => 'mh-badge mh-badge-rechazada',
        'cancelada'  => 'mh-badge mh-badge-cancelada',
        'realizada'  => 'mh-badge mh-badge-aprobada',
    ];
    return $mapa[strtolower($estatus)] ?? 'mh-badge';
}

/**
 * Conexión a la BD con mensaje claro si falta config/conexion.php.
 * Uso:  $conexion = conectar_bd();
 * (También puedes usar el estilo de clase:  require_once __DIR__ . '/config/conexion.php';)
 */
function conectar_bd(): mysqli
{
    static $c = null;
    if ($c === null) {
        $archivo = __DIR__ . '/../config/conexion.php';
        if (!is_file($archivo)) {
            http_response_code(500);
            exit('Falta config/conexion.php: copia config/conexion.example.php, renómbralo y llena tus datos.');
        }
        require $archivo;
        $c = $conexion;
    }
    return $c;
}
