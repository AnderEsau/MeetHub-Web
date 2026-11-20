<?php
/**
 * Configuración general de MeetHub (se sube al repositorio).
 * No poner aquí contraseñas: eso va en config/conexion.php (ignorado por Git).
 */

define('APP_NOMBRE', 'MeetHub');
define('APP_ANIO', '2026');

// Roles del sistema (un solo login; el rol cambia el menú)
define('ROL_SOLICITANTE', 'solicitante');      // Alumno, Docente o Externo
define('ROL_BIBLIOTECARIO', 'bibliotecario');  // Encargado de la biblioteca
define('ROL_ADMIN', 'administrador');

// BASE_URL: ruta del proyecto dentro del servidor, calculada sola.
//  - php -S localhost:8000 -t .      -> ''
//  - XAMPP: htdocs/meethub-web       -> '/meethub-web'
$__root = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$__doc  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
define('BASE_URL', ($__doc !== '' && stripos($__root, $__doc) === 0)
    ? rtrim(substr($__root, strlen($__doc)), '/')
    : '');
unset($__root, $__doc);

// Datos de contacto del pie de página (provisional: luego saldrán de tblcontenido_publico)
define('APP_CORREO', 'biblioteca@uthh.edu.mx');
define('APP_WEB', 'www.uthh.edu.mx');
define('APP_EDIFICIO', 'Edificio de Biblioteca UTHH');

// Estado y zona horaria
date_default_timezone_set('America/Mexico_City');

// Reglas de negocio (se usan en nueva_solicitud.php)
define('DIAS_ANTICIPACION', 3);   // mínimo de días naturales entre hoy y la fecha de reserva
