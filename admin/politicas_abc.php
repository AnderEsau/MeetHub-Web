<?php
/**
 * ABC de contenido público: Políticas — Responsable: Samantha
 * Tabla: tblcontenido_publico (modulo = 'Politicas'). La lógica está en includes/contenido_abc_motor.php;
 * aquí solo se configura qué apartados y qué campos usa este módulo.
 */
require_once __DIR__ . '/../includes/auth.php';
$cfg = [
    'modulo' => 'Politicas',
    'roles' => [ROL_ADMIN],
    'pagina' => 'admin/politicas_abc.php',
    'apartados' => ['Políticas de préstamo', 'Políticas de cancelación', 'Políticas de devolución'],   // null = el apartado se escribe libre (FAQ: cada apartado es un grupo)
    'prefijo' => 'POL',
    'etiqueta' => 'Políticas',
    'foto' => null,         // apartado que sube imagen (fachada) o null
    'lbl_titulo' => 'Título o regla',
    'lbl_contenido' => 'Contenido de la política',
];
require __DIR__ . '/../includes/contenido_abc_motor.php';
