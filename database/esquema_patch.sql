-- ============================================================
--  ORNIS v3.1 — PATCH SQL (ejecutar en phpMyAdmin)
--  Agrega columnas faltantes sin destruir datos existentes.
-- ============================================================

USE ornis_db;

-- 1. Columna is_public en registros (para galería privada/pública)
ALTER TABLE registros
    ADD COLUMN IF NOT EXISTS is_public TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '0 = privada, 1 = visible en galería pública'
    AFTER foto_ave;

-- 2. Índice para consultas de galería pública eficientes
ALTER TABLE registros
    ADD INDEX IF NOT EXISTS idx_reg_publicas (is_public, fecha_avistamiento DESC);

-- 3. Columna nombre_cientifico en registros (para display en galería privada)
ALTER TABLE registros
    ADD COLUMN IF NOT EXISTS nombre_cientifico VARCHAR(200) DEFAULT NULL
    AFTER nombre_comun;

-- Verificación post-patch
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'ornis_db'
  AND TABLE_NAME   = 'registros'
  AND COLUMN_NAME  IN ('is_public', 'nombre_cientifico')
ORDER BY TABLE_NAME, ORDINAL_POSITION;
