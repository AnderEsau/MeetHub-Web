<?php
/** Compatibilidad con enlaces viejos: las políticas ahora son una página cada una (Samantha). */
require_once __DIR__ . '/config/app.php';
header('Location: ' . BASE_URL . '/politica_prestamo.php');
exit;
