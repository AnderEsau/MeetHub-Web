<?php
/** Salas (catálogo) del solicitante — mismo catálogo con botón Reservar directo. Responsable: Leonel. */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
$titulo = 'Salas (catálogo)';
$pagina_catalogo = 'usuario/salas.php';
$es_solicitante = true;
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/parciales/catalogo.php';
require_once __DIR__ . '/../includes/footer.php';
