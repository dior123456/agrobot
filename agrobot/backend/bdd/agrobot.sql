-- ======================================================
-- BASE DE DATOS AGROBOT - VERSIÓN 2.0
-- Asistente Agrícola con IA para Guinea Ecuatorial
-- Hackathon "IA para el Desarrollo"
-- Fecha: Mayo 2026
-- ======================================================

-- Eliminar base de datos si existe
DROP DATABASE IF EXISTS agrobot;

-- Crear base de datos con charset correcto
CREATE DATABASE agrobot
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE agrobot;

-- ======================================================
-- 1. TABLA DE USUARIOS
-- ======================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del usuario',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre completo',
    codigo VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código de acceso único',
    rol ENUM('admin', 'user') DEFAULT 'user' COMMENT 'Rol del usuario',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo' COMMENT 'Estado de la cuenta',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro',
    ultimo_acceso DATETIME NULL COMMENT 'Última fecha de inicio de sesión',
    foto_perfil VARCHAR(500) NULL COMMENT 'URL de la foto de perfil',
    INDEX idx_codigo (codigo),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema';

-- ======================================================
-- 2. TABLA DE RESPUESTAS (BASE DE CONOCIMIENTO LOCAL)
-- ======================================================
CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único',
    palabra_clave VARCHAR(100) NOT NULL UNIQUE COMMENT 'Palabra clave para búsqueda',
    respuesta TEXT NOT NULL COMMENT 'Respuesta asociada',
    idioma VARCHAR(20) DEFAULT 'español' COMMENT 'español, fang, bubi',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última actualización',
    INDEX idx_palabra_clave (palabra_clave),
    INDEX idx_idioma (idioma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Base de conocimiento del chatbot';

-- ======================================================
-- 3. TABLA DE CONSULTAS (HISTORIAL)
-- ======================================================
CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único',
    usuario_id INT NULL COMMENT 'ID del usuario (puede ser NULL)',
    usuario_nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del usuario',
    consulta TEXT NOT NULL COMMENT 'Texto de la consulta',
    respuesta TEXT NULL COMMENT 'Respuesta del bot',
    imagen_url VARCHAR(500) NULL COMMENT 'URL de imagen adjunta',
    texto_adjunto TEXT NULL COMMENT 'Texto adicional adjunto',
    idioma VARCHAR(20) DEFAULT 'español' COMMENT 'Idioma detectado',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora',
    tiempo_respuesta FLOAT DEFAULT 0 COMMENT 'Tiempo de respuesta en segundos',
    calificacion TINYINT NULL COMMENT 'Calificación 1-5',
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    INDEX idx_idioma (idioma),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de consultas';

-- ======================================================
-- 4. TABLA DE CULTIVOS LOCALES
-- ======================================================
CREATE TABLE cultivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL COMMENT 'Nombre del cultivo',
    nombre_cientifico VARCHAR(100) NULL COMMENT 'Nombre científico',
    temporada_siembra VARCHAR(100) NULL COMMENT 'Época de siembra',
    temporada_cosecha VARCHAR(100) NULL COMMENT 'Época de cosecha',
    descripcion TEXT NULL COMMENT 'Descripción del cultivo',
    imagen_url VARCHAR(500) NULL COMMENT 'URL de imagen',
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 5. TABLA DE PLAGAS Y ENFERMEDADES
-- ======================================================
CREATE TABLE plagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre de la plaga/enfermedad',
    tipo ENUM('plaga', 'enfermedad') DEFAULT 'plaga' COMMENT 'Tipo',
    cultivo_afectado VARCHAR(50) NULL COMMENT 'Cultivo que afecta',
    sintomas TEXT NULL COMMENT 'Síntomas visibles',
    tratamiento TEXT NULL COMMENT 'Tratamiento químico',
    tratamiento_ecologico TEXT NULL COMMENT 'Tratamiento ecológico',
    estacionalidad VARCHAR(100) NULL COMMENT 'Época de aparición',
    INDEX idx_nombre (nombre),
    INDEX idx_tipo (tipo),
    INDEX idx_cultivo (cultivo_afectado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 6. TABLA DE CONSEJOS AGRÍCOLAS
-- ======================================================
CREATE TABLE consejos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL COMMENT 'Título del consejo',
    contenido TEXT NOT NULL COMMENT 'Contenido detallado',
    categoria VARCHAR(50) DEFAULT 'general' COMMENT 'abono, riego, poda, etc',
    idioma VARCHAR(20) DEFAULT 'español' COMMENT 'español, fang, bubi',
    fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_idioma (idioma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 7. TABLA DE LOGS DEL SISTEMA
-- ======================================================
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL COMMENT 'ID del usuario',
    accion VARCHAR(100) NOT NULL COMMENT 'Acción realizada',
    descripcion TEXT NULL COMMENT 'Descripción detallada',
    ip_address VARCHAR(45) NULL COMMENT 'Dirección IP',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 8. TABLA DE CONFIGURACIÓN
-- ======================================================
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- INSERCIÓN DE DATOS INICIALES
-- ======================================================

-- Usuarios (admin y usuarios de prueba)
INSERT INTO usuarios (nombre, codigo, rol, estado, fecha_registro) VALUES
('Administrador AgroBot', 'admin123', 'admin', 'activo', NOW()),
('Juan Mbá Asumu', 'user001', 'user', 'activo', NOW()),
('María Nguema', 'user002', 'user', 'activo', NOW()),
('Pedro Obama', 'user003', 'user', 'activo', NOW());

-- Respuestas base de conocimiento
INSERT INTO respuestas (palabra_clave, respuesta, idioma) VALUES
('plaga', '🐛 Para plagas en cacao o café, recomiendo usar extracto de nim o jabón potásico. También puedes retirar manualmente las hojas afectadas.', 'español'),
('enfermedad', '🦠 La moniliasis es común en cacao. Retira los frutos infectados y aplica cal agrícola. Para la roya del café, usa fungicidas a base de cobre.', 'español'),
('cacao', '🌱 El cacao requiere sombra del 40-50%, control de monilia y fertilización orgánica cada 3 meses. La cosecha se realiza entre octubre y marzo.', 'español'),
('café', '☕ El café arábica crece mejor en altitudes de 800-1200m. Poda después de cosecha y control de broca del café.', 'español'),
('yuca', '🥔 La yuca resiste sequía. Se cosecha a los 8-12 meses. Controla la cochinilla con extracto de neem.', 'español'),
('plátano', '🍌 El plátano requiere mucho potasio. Control del mal de Panamá con rotación de cultivos.', 'español'),
('fang', 'Mebolo: "Yebela bôto" significa "Agricultura buena". Los ancestros enseñaron rotación de cultivos y uso de abonos naturales como ceniza y estiércol.', 'fang'),
('bubi', 'Bubila: "Bôtó bôbô" es agricultura. Usa métodos tradicionales como el mulch orgánico con hojas secas.', 'bubi'),
('rendimiento', '📈 El rendimiento de yuca puede mejorar con rotación de cultivos, suelo bien drenado y precipitaciones de 1000-1500mm anuales.', 'español'),
('abono', '🌿 Abonos orgánicos recomendados: Estiércol de gallina (rico en nitrógeno), ceniza (potasio), compost de hojas.', 'español'),
('riego', '💧 El riego por goteo es más eficiente. Para plátano y cacao, mantener humedad constante.', 'español'),
('poda', '✂️ La poda se hace después de la cosecha. Retira ramas secas o enfermas. Mejora la ventilación y entrada de luz.', 'español');

-- Cultivos locales
INSERT INTO cultivos (nombre, nombre_cientifico, temporada_siembra, temporada_cosecha, descripcion) VALUES
('Cacao', 'Theobroma cacao', 'Mayo - Junio', 'Octubre - Marzo', 'Cultivo estrella de Guinea Ecuatorial. Requiere sombra y mucha humedad.'),
('Café Arábica', 'Coffea arabica', 'Abril - Mayo', 'Septiembre - Diciembre', 'Café de alta calidad que crece en altitudes medias.'),
('Café Robusta', 'Coffea canephora', 'Marzo - Abril', 'Agosto - Noviembre', 'Más resistente a plagas que el arábica.'),
('Yuca', 'Manihot esculenta', 'Todo el año', '8-12 meses después', 'Alimento básico resistente a la sequía.'),
('Plátano', 'Musa paradisiaca', 'Todo el año', '9-12 meses después', 'Fruto muy consumido. Requiere mucho potasio.'),
('Maíz', 'Zea mays', 'Marzo - Abril', 'Julio - Septiembre', 'Cereal básico para alimentación animal y humana.');

-- Plagas y enfermedades
INSERT INTO plagas (nombre, tipo, cultivo_afectado, sintomas, tratamiento, tratamiento_ecologico) VALUES
('Moniliasis', 'enfermedad', 'Cacao', 'Frutos con manchas marrones y moco blanco', 'Retirar frutos infectados, aplicar cal', 'Extracto de cola de caballo y ajo'),
('Broca del café', 'plaga', 'Café', 'Granos perforados con polvo', 'Recolección manual, trampas', 'Beauveria bassiana'),
('Cochinilla', 'plaga', 'Yuca', 'Hojas amarillas, secreciones pegajosas', 'Poda de hojas afectadas', 'Extracto de neem y jabón potásico'),
('Mal de Panamá', 'enfermedad', 'Plátano', 'Hojas amarillas, tallo partido', 'Rotación de cultivos', 'Uso de variedades resistentes'),
('Escoba de bruja', 'enfermedad', 'Cacao', 'Crecimiento anormal de brotes', 'Poda sanitaria', 'Control biológico con Trichoderma'),
('Roya del café', 'enfermedad', 'Café', 'Manchas anaranjadas en hojas', 'Fungicidas a base de cobre', 'Extracto de ajo y cebolla');

-- Consejos agrícolas
INSERT INTO consejos (titulo, contenido, categoria, idioma) VALUES
('Cómo hacer compost casero', 'Junta restos de cocina (cáscaras, verduras) con hojas secas. Humedece y voltea cada semana. En 2-3 meses tendrás abono natural.', 'abono', 'español'),
('Control natural de plagas', 'El extracto de ajo, cebolla y chile es un insecticida natural. Mezcla 3 dientes de ajo con 1 litro de agua y deja reposar 24 horas.', 'plaga', 'español'),
('Riego eficiente', 'Riega temprano en la mañana o al atardecer para evitar evaporación. El agua debe llegar a la raíz, no a las hojas.', 'riego', 'español'),
('Mebolo - Sabiduría Fang', 'Nkûkuma (lluvia) es vida. Los abuelos sembraban con luna llena y cosechaban con luna nueva.', 'tradicional', 'fang'),
('Bubila - Consejo Bubi', 'Rîpôtô (tierra) es madre. No quemes el bosque, úsalo como sombra para tus cultivos.', 'tradicional', 'bubi');

-- Configuración del sistema
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('sistema_nombre', 'AgroBot', 'Nombre del sistema'),
('sistema_version', '2.0.0', 'Versión actual'),
('idioma_por_defecto', 'español', 'Idioma predeterminado'),
('mantenimiento', 'false', 'Modo mantenimiento'),
('max_consultas_por_dia', '100', 'Límite de consultas diarias');

-- Log inicial
INSERT INTO logs (usuario_id, accion, descripcion, ip_address) VALUES
(1, 'sistema_iniciado', 'Base de datos AgroBot creada correctamente', '127.0.0.1');

-- ======================================================
-- VISTAS ÚTILES
-- ======================================================

-- Vista: Resumen de consultas por día
CREATE OR REPLACE VIEW vista_resumen_consultas AS
SELECT 
    DATE(fecha) as dia,
    COUNT(*) as total_consultas,
    COUNT(DISTINCT usuario_id) as usuarios_activos
FROM consultas
GROUP BY DATE(fecha)
ORDER BY dia DESC;

-- Vista: Palabras más consultadas
CREATE OR REPLACE VIEW vista_palabras_populares AS
SELECT 
    r.palabra_clave,
    COUNT(c.id) as veces_consultado
FROM respuestas r
LEFT JOIN consultas c ON c.consulta LIKE CONCAT('%', r.palabra_clave, '%')
GROUP BY r.palabra_clave
ORDER BY veces_consultado DESC;

-- ======================================================
-- PROCEDIMIENTOS ALMACENADOS
-- ======================================================

DELIMITER //

-- Limpiar logs antiguos (más de 30 días)
CREATE PROCEDURE limpiar_logs_antiguos()
BEGIN
    DELETE FROM logs WHERE fecha < DATE_SUB(NOW(), INTERVAL 30 DAY);
END//

-- Obtener estadísticas completas
CREATE PROCEDURE obtener_estadisticas_completas()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE rol = 'user') as total_usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE rol = 'admin') as total_admins,
        (SELECT COUNT(*) FROM respuestas) as total_respuestas,
        (SELECT COUNT(*) FROM consultas) as total_consultas,
        (SELECT COUNT(*) FROM consultas WHERE DATE(fecha) = CURDATE()) as consultas_hoy,
        (SELECT COUNT(*) FROM cultivos) as total_cultivos,
        (SELECT COUNT(*) FROM plagas) as total_plagas;
END//

DELIMITER ;

-- ======================================================
-- TRIGGERS
-- ======================================================

DELIMITER //

-- Trigger: Actualizar último acceso del usuario
CREATE TRIGGER actualizar_ultimo_acceso
BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF NEW.ultimo_acceso IS NOT NULL AND OLD.ultimo_acceso IS NULL THEN
        INSERT INTO logs (usuario_id, accion, descripcion)
        VALUES (NEW.id, 'primer_acceso', 'Primer inicio de sesión');
    END IF;
END//

-- Trigger: Registrar nuevas consultas
CREATE TRIGGER registrar_nueva_consulta
AFTER INSERT ON consultas
FOR EACH ROW
BEGIN
    INSERT INTO logs (usuario_id, accion, descripcion)
    VALUES (NEW.usuario_id, 'consulta_realizada', CONCAT('Consulta: ', LEFT(NEW.consulta, 100)));
END//

DELIMITER ;

-- ======================================================
-- VERIFICACIÓN FINAL
-- ======================================================
SELECT '✅ Base de datos AgroBot creada exitosamente' as Mensaje;
SELECT COUNT(*) as Total_Usuarios FROM usuarios;
SELECT COUNT(*) as Total_Respuestas FROM respuestas;
SELECT COUNT(*) as Total_Cultivos FROM cultivos;
SELECT COUNT(*) as Total_Plagas FROM plagas;
SELECT COUNT(*) as Total_Consejos FROM consejos;