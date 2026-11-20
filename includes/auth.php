<?php
/**
 * Sesión y permisos por rol (un solo login para todos).
 * Claves de sesión (iguales a las que usamos en proyecto_web, más usuario_tipo):
 *   usuario_id     = llave de su tabla (id_alumno | matricula | id_externo | id_bibliotecario)
 *   usuario_nombre, usuario_rol
 *   usuario_tipo   = 'alumno' | 'docente' | 'externo' | 'bibliotecario' (define la columna de tblreserva)
 */
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuario_logueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** Devuelve el rol en sesión o null si es visitante. */
function rol_actual(): ?string
{
    return $_SESSION['usuario_rol'] ?? null;
}

/** Guarda los datos del usuario tras validar el login. */
function iniciar_sesion_usuario(int $id, string $nombre, string $rol, string $tipo = ''): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $id;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_rol'] = $rol;
    $_SESSION['usuario_tipo'] = $tipo;
}

/** A dónde mandar a cada rol después del login. */
function pagina_inicio_por_rol(?string $rol): string
{
    switch ($rol) {
        case ROL_BIBLIOTECARIO: return BASE_URL . '/admin/index.php';
        case ROL_ADMIN:         return BASE_URL . '/admin/index.php';
        case ROL_SOLICITANTE:   return BASE_URL . '/usuario/index.php';
        default:                return BASE_URL . '/index.php';
    }
}

/**
 * Candado de seguridad: poner al inicio de cada página privada.
 * Ejemplo:  requerir_rol(ROL_BIBLIOTECARIO);
 *           requerir_rol([ROL_BIBLIOTECARIO, ROL_ADMIN]);
 */
function requerir_rol($roles): void
{
    $roles = (array) $roles;
    if (!usuario_logueado()) {
        header('Location: ' . BASE_URL . '/login.php?error=acceso_denegado');
        exit;
    }
    if (!in_array(rol_actual(), $roles, true)) {
        header('Location: ' . BASE_URL . '/login.php?error=sin_permiso');
        exit;
    }
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    session_destroy();
}
