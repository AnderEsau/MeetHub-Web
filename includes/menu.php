<?php
/**
 * Menús por rol (Reparto v4, sección 2). UN SOLO LUGAR para cambiar qué ve cada rol.
 * Formato: ['etiqueta' => 'Texto', 'url' => 'ruta/desde/la/raiz.php']
 */
function menu_por_rol(?string $rol): array
{
    switch ($rol) {
        case ROL_SOLICITANTE:
            return [
                ['etiqueta' => 'Inicio',            'url' => 'usuario/index.php'],
                ['etiqueta' => 'Nueva solicitud',   'url' => 'usuario/nueva_solicitud.php'],
                ['etiqueta' => 'Mis solicitudes',   'url' => 'usuario/mis_solicitudes.php'],
                ['etiqueta' => 'Buzón e historial', 'url' => 'usuario/buzon.php'],
                ['etiqueta' => 'Salas (catálogo)',  'url' => 'usuario/salas.php'],
                ['etiqueta' => 'Mi perfil',         'url' => 'usuario/mi_perfil.php'],
            ];
        case ROL_BIBLIOTECARIO:
            return [
                ['etiqueta' => 'Inicio',                 'url' => 'admin/index.php'],
                ['etiqueta' => 'Solicitudes pendientes', 'url' => 'admin/solicitudes.php'],
                ['etiqueta' => 'Reservas',               'url' => 'admin/reservas.php'],
                ['etiqueta' => 'Reportes e historial',   'url' => 'admin/reportes.php'],
                ['etiqueta' => 'Salas (ABC)',            'url' => 'admin/salas_abc.php'],
                ['etiqueta' => 'Mobiliario (ABC)',       'url' => 'admin/equipo_abc.php'],
                ['etiqueta' => 'Contenido Público (ABC)', 'url' => 'admin/faq_abc.php'],
                ['etiqueta' => 'Mensajes',               'url' => 'admin/mensajes.php'],
            ];
        case ROL_ADMIN:
            return [
                ['etiqueta' => 'Inicio',                  'url' => 'admin/index.php'],
                ['etiqueta' => 'Reportes e historial',    'url' => 'admin/reportes.php'],
                ['etiqueta' => 'Contenido Público (ABC)', 'url' => 'admin/faq_abc.php'],
                ['etiqueta' => 'Mensajes',                'url' => 'admin/mensajes.php'],
                ['etiqueta' => 'Usuarios / Roles (ABC)',  'url' => 'admin/usuarios_abc.php'],
            ];
        default:
            return [
                ['etiqueta' => 'Inicio',               'url' => 'index.php'],
                ['etiqueta' => 'Disponibilidad',       'url' => 'disponibilidad.php'],
                ['etiqueta' => 'Salas (Catálogo)',     'url' => 'salas.php'],
                ['etiqueta' => 'Políticas',            'url' => 'politicas.php'],
                ['etiqueta' => 'Preguntas frecuentes', 'url' => 'faq.php'],
                ['etiqueta' => 'Ubicación',            'url' => 'ubicacion.php'],
                ['etiqueta' => 'Contacto',             'url' => 'contacto.php'],
            ];
    }
}

/** Nombre legible del rol para mostrar en el navbar. */
function etiqueta_rol(?string $rol): string
{
    $m = [
        ROL_SOLICITANTE => 'Solicitante',
        ROL_BIBLIOTECARIO => 'Bibliotecario',
        ROL_ADMIN => 'Administrador',
    ];
    return $m[$rol] ?? 'Visitante';
}
