<?php
/**
 * ABC de contenido público: Preguntas Frecuentes (FAQ) — Responsable: Lary
 * Tabla: tblcontenido_publico (modulo = 'FAQ'). La lógica está en includes/contenido_abc_motor.php;
 * aquí solo se configura qué apartados y qué campos usa este módulo.
 */
require_once __DIR__ . '/../includes/auth.php';
$cfg = [
    'modulo' => 'FAQ',
    'roles' => [ROL_BIBLIOTECARIO, ROL_ADMIN],
    'pagina' => 'admin/faq_abc.php',
    'apartados' => null,   // null = el apartado se escribe libre (FAQ: cada apartado es un grupo)
    'prefijo' => 'FAQ',
    'etiqueta' => 'Preguntas Frecuentes (FAQ)',
    'foto' => null,         // apartado que sube imagen (fachada) o null
    'lbl_titulo' => 'Pregunta',
    'lbl_contenido' => 'Respuesta',
];
require __DIR__ . '/../includes/contenido_abc_motor.php';
