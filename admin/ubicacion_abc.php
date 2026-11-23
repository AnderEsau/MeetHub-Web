<?php
/**
 * ABC de contenido público: Ubicación y fachada — Responsable: Samantha
 * Tabla: tblcontenido_publico (modulo = 'Datos'). La lógica está en includes/contenido_abc_motor.php;
 * aquí solo se configura qué apartados y qué campos usa este módulo.
 */
require_once __DIR__ . '/../includes/auth.php';
$cfg = [
    'modulo' => 'Datos',
    'roles' => [ROL_ADMIN],
    'pagina' => 'admin/ubicacion_abc.php',
    'apartados' => ['Dirección física', 'Edificio y área', 'Horario y contacto', 'Portal institucional', 'Fachada'],   // null = el apartado se escribe libre (FAQ: cada apartado es un grupo)
    'prefijo' => 'UBI',
    'etiqueta' => 'Ubicación y fachada',
    'foto' => 'Fachada',         // apartado que sube imagen (fachada) o null
    'lbl_titulo' => 'Título o rubro',
    'lbl_contenido' => 'Contenido o dirección',
];
require __DIR__ . '/../includes/contenido_abc_motor.php';
