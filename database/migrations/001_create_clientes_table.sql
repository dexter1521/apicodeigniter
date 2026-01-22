-- ===================================================
-- Migración: Crear tabla de clientes
-- Descripción: Tabla ejemplo para demostrar CRUD completo
-- Fecha: 2025-07-05
-- Autor: Eduardo Marvil <emtv2126@gmail.com>
-- ===================================================

-- Crear tabla de clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre completo del cliente',
  `email` varchar(100) NOT NULL COMMENT 'Email único del cliente',
  `telefono` varchar(20) DEFAULT NULL COMMENT 'Teléfono de contacto',
  `direccion` text DEFAULT NULL COMMENT 'Dirección del cliente',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Eliminado (soft delete)',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
  `fecha_eliminacion` datetime DEFAULT NULL COMMENT 'Fecha de eliminación (soft delete)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_clientes_email` (`email`),
  KEY `idx_clientes_activo` (`activo`),
  KEY `idx_clientes_nombre` (`nombre`),
  KEY `idx_clientes_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de clientes del sistema';

-- Insertar datos de ejemplo
INSERT INTO `clientes` (`nombre`, `email`, `telefono`, `direccion`) VALUES
('Juan Pérez', 'juan.perez@email.com', '+1234567890', 'Av. Principal 123, Ciudad'),
('María García', 'maria.garcia@email.com', '+1234567891', 'Calle Secundaria 456, Ciudad'),
('Carlos López', 'carlos.lopez@email.com', '+1234567892', 'Boulevard Central 789, Ciudad'),
('Ana Martínez', 'ana.martinez@email.com', '+1234567893', 'Plaza Mayor 321, Ciudad'),
('Pedro Rodríguez', 'pedro.rodriguez@email.com', '+1234567894', 'Paseo del Parque 654, Ciudad');

-- ===================================================
-- Tabla de tokens estáticos para API (opcional)
-- ===================================================

CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) NOT NULL COMMENT 'Nombre de la aplicación',
  `token` varchar(255) NOT NULL COMMENT 'Token de acceso',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `expires_at` datetime DEFAULT NULL COMMENT 'Fecha de expiración (NULL = no expira)',
  `last_used_at` datetime DEFAULT NULL COMMENT 'Última fecha de uso',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_tokens_token` (`token`),
  KEY `idx_api_tokens_active` (`is_active`),
  KEY `idx_api_tokens_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens estáticos para acceso a la API';

-- Insertar token de ejemplo
INSERT INTO `api_tokens` (`app_name`, `token`) VALUES
('Aplicación de Prueba', 'test_token_123456789abcdef');
