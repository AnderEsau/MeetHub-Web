<?php
/**
 * Encabezado común (navbar según rol). Uso en cada página:
 *     $titulo = 'Salas (Catálogo)';
 *     require_once __DIR__ . '/includes/header.php';       // en la raíz
 *     require_once __DIR__ . '/../includes/header.php';    // en usuario/ y admin/
 * Los candados de rol (requerir_rol) van ANTES de incluir el header.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/iconos.php';
require_once __DIR__ . '/menu.php';

$titulo = $titulo ?? 'Inicio';
$rol = rol_actual();
$menu = menu_por_rol($rol);
$actual = ltrim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(BASE_URL)), '/');
if ($actual === '' || substr($actual, -1) === '/') { $actual .= 'index.php'; }
$privado = usuario_logueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | <?= APP_NOMBRE ?></title>
    <link href="<?= url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= url('assets/css/meethub.css') ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-xl navbar-dark mh-navbar">
    <div class="container-fluid px-3 px-xl-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= url($privado ? ltrim(str_replace(BASE_URL, '', pagina_inicio_por_rol($rol)), '/') : 'index.php') ?>">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="40" height="40">
            <?= APP_NOMBRE ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav mx-xl-auto mb-2 mb-xl-0 gap-xl-1">
                <?php foreach ($menu as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $actual === $item['url'] ? ' active' : '' ?>"
                           href="<?= url($item['url']) ?>"><?= e($item['etiqueta']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($privado): ?>
                    <div class="dropdown">
                        <button class="btn btn-mh-borde btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= icono('user', 14) ?> <?= e(explode(' ', $_SESSION['usuario_nombre'] ?? 'Usuario')[0] . ' ' . (explode(' ', $_SESSION['usuario_nombre'] ?? '')[1] ?? '')) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">Rol: <?= e(etiqueta_rol($rol)) ?></span></li>
                            <?php if ($rol === ROL_SOLICITANTE): ?>
                                <li><a class="dropdown-item" href="<?= url('usuario/mi_perfil.php') ?>"><?= icono('gear', 15) ?> Configuración de cuenta (Mi perfil)</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>">Panel de gestión</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <a href="<?= url('logout.php') ?>" class="btn btn-mh btn-sm">Cerrar sesión</a>
                <?php else: ?>
                    <a href="<?= url('login.php#crear-cuenta') ?>" class="btn btn-mh btn-sm">Crear cuenta</a>
                    <a href="<?= url('login.php') ?>" class="btn btn-light btn-sm fw-semibold">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<?php /* Cada página imprime su contenido dentro de <main class="flex-grow-1"> */ ?>
