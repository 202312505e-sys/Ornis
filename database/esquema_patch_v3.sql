-- ============================================================
--  ORNIS v5 — PATCH SQL v3 (phpMyAdmin, ornis_db seleccionada)
--  Agrega tablas de Listas + columnas faltantes en registros
-- ============================================================

USE ornis_db;

-- ── 1. Columna is_public en registros (si no existe)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='ornis_db' AND TABLE_NAME='registros' AND COLUMN_NAME='is_public');
SET @sql = IF(@col_exists=0,
    'ALTER TABLE registros ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER foto_ave',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── 2. Columna nombre_cientifico en registros (si no existe)
SET @col2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='ornis_db' AND TABLE_NAME='registros' AND COLUMN_NAME='nombre_cientifico');
SET @sql2 = IF(@col2=0,
    'ALTER TABLE registros ADD COLUMN nombre_cientifico VARCHAR(200) DEFAULT NULL AFTER nombre_comun',
    'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- ── 3. Columna hora en registros (si no existe)
SET @col3 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='ornis_db' AND TABLE_NAME='registros' AND COLUMN_NAME='hora');
SET @sql3 = IF(@col3=0,
    'ALTER TABLE registros ADD COLUMN hora TIME DEFAULT NULL AFTER fecha_avistamiento',
    'SELECT 1');
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- ── 4. Tabla listas_avistamiento
CREATE TABLE IF NOT EXISTS listas_avistamiento (
    id_lista       INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    id_usuario     INT UNSIGNED   NOT NULL,
    nombre_lista   VARCHAR(200)   NOT NULL,
    fecha_lista    DATE           NOT NULL,
    hora_inicio    TIME           DEFAULT NULL,
    duracion_min   SMALLINT       DEFAULT NULL,
    tipo_protocolo ENUM('libre','estacionario','en_movimiento') NOT NULL DEFAULT 'libre',
    lugar_nombre   VARCHAR(255)   DEFAULT NULL,
    lugar_lat      DECIMAL(10,8)  DEFAULT NULL,
    lugar_lng      DECIMAL(11,8)  DEFAULT NULL,
    notas          TEXT           DEFAULT NULL,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_lista),
    CONSTRAINT fk_lista_usr
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_lista_usuario (id_usuario),
    INDEX idx_lista_fecha   (fecha_lista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Tabla lista_especies
CREATE TABLE IF NOT EXISTS lista_especies (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    id_lista       INT UNSIGNED      NOT NULL,
    species_code   VARCHAR(20)       DEFAULT NULL,
    nombre_comun   VARCHAR(200)      NOT NULL,
    sci_name       VARCHAR(200)      DEFAULT NULL,
    cantidad       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    notas_especie  TEXT              DEFAULT NULL,
    orden          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    CONSTRAINT fk_lesp_lista
        FOREIGN KEY (id_lista) REFERENCES listas_avistamiento(id_lista)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_lesp_lista   (id_lista),
    INDEX idx_lesp_species (species_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Verificación final
SELECT 'listas_avistamiento' AS tabla, COUNT(*) AS filas FROM listas_avistamiento
UNION ALL
SELECT 'lista_especies', COUNT(*) FROM lista_especies
UNION ALL
SELECT 'registros (total)', COUNT(*) FROM registros;
