-- MeetHub Web v4: contenido inicial para "Datos de la empresa" (Misión, Visión, Política de calidad) y Ubicación/fachada.
-- Ejecutar UNA vez después de importar meethub.sql (phpMyAdmin > SQL).
INSERT INTO tblcontenido_publico (modulo, apartado, titulo, contenido, estado) VALUES
('Datos', 'Misión', 'Misión', 'Facilitar el acceso a espacios para el estudio y la colaboración académica.', 'Activo'),
('Datos', 'Visión', 'Visión', 'Ser el punto de encuentro de una comunidad que aprende y comparte.', 'Activo'),
('Datos', 'Política de calidad', 'Política de Calidad', 'Brindar servicios bibliotecarios y espacios de estudio bajo mejora continua institucional.', 'Activo'),
('Datos', 'Edificio y área', 'Edificio y Área', 'Edificio de Biblioteca Central UTHH · Planta Baja (Sala de Lectura y Cubículos) y Planta Alta (Salas de Audio y Video).', 'Activo'),
('Datos', 'Fachada', 'Fotografía de la entrada de la Biblioteca UTHH', '', 'Activo');
-- Otras filas útiles para el pie y el directorio de Ubicación (opcional):
INSERT INTO tblcontenido_publico (modulo, apartado, titulo, contenido, estado) VALUES
('Datos', 'Portal institucional', 'Portal Institucional', 'www.uthh.edu.mx', 'Activo');
