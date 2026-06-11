-- ============================================================
--  ORNIS v3.1 — PATCH SQL corregido para MySQL 5.7 / XAMPP
--  Ejecutar en phpMyAdmin con la BD ornis_db seleccionada
-- ============================================================

USE ornis_db;

-- 1. Columna is_public en registros
ALTER TABLE registros
    ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '0 = privada, 1 = visible en galeria publica'
    AFTER foto_ave;

-- 2. Índice para galería pública
ALTER TABLE registros
    ADD INDEX idx_reg_publicas (is_public, fecha_avistamiento);

-- 3. Columna nombre_cientifico en registros
ALTER TABLE registros
    ADD COLUMN nombre_cientifico VARCHAR(200) DEFAULT NULL
    AFTER nombre_comun;

-- Verificación
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'ornis_db'
  AND TABLE_NAME   = 'registros'
  AND COLUMN_NAME IN ('is_public', 'nombre_cientifico');
