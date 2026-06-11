-- ============================================================
--  ORNIS v3.0 — Esquema SQL Definitivo y Corregido
--  Motor: InnoDB | Charset: utf8mb4_unicode_ci
--  Autor: Denis Alexander Meza Huarcaya — UAC Cusco
--
--  CORRECCIONES vs v2.0:
--  ✅ Columna `activo` añadida a `usuarios` (requerida por Auth.php)
--  ✅ Columna `es_publica` añadida a `ubicaciones`
--  ✅ FK formal para `id_ubicacion_ref` en `registros`
--  ✅ Columnas `clima` y `comportamiento` en `registros` (ENUM)
--  ✅ Índices compuestos optimizados para paginación del dashboard
--  ✅ Hash bcrypt costo 12 consistente con Auth.php
--  ✅ Tabla `sesiones` para control de sesiones activas
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS ornis_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ornis_db;

-- ─────────────────────────────────────────────────────────────
-- 1. USUARIOS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(80)     NOT NULL,
    apellido        VARCHAR(80)     NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    password        VARCHAR(255)    NOT NULL,
    bio             TEXT            DEFAULT NULL,
    avatar          VARCHAR(255)    DEFAULT NULL,
    ebird_username  VARCHAR(100)    DEFAULT NULL,
    rol             ENUM('admin','observador','demo')
                                    NOT NULL DEFAULT 'observador',
    activo          TINYINT(1)      NOT NULL DEFAULT 1,   -- ← CORRECCIÓN: requerido por Auth.php
    fecha_registro  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_usuario),
    UNIQUE  KEY uq_email        (email),
    INDEX   idx_rol             (rol),
    INDEX   idx_activo          (activo)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────
-- 2. TAXONOMÍA eBird (~17 890 especies)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS especies_taxonomia (
    id_taxon         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    taxon_order      INT UNSIGNED    DEFAULT NULL,
    category         VARCHAR(50)     NOT NULL,
    species_code     VARCHAR(20)     NOT NULL,
    primary_com_name VARCHAR(200)    NOT NULL,
    sci_name         VARCHAR(200)    NOT NULL,
    e_order          VARCHAR(100)    DEFAULT NULL,
    family           VARCHAR(150)    DEFAULT NULL,
    species_group    VARCHAR(150)    DEFAULT NULL,
    report_as        VARCHAR(20)     DEFAULT NULL,

    PRIMARY KEY (id_taxon),
    UNIQUE  KEY uq_species_code     (species_code),
    INDEX   idx_category            (category),
    INDEX   idx_busqueda_nombre     (primary_com_name(80)),
    INDEX   idx_busqueda_sci        (sci_name(80)),
    INDEX   idx_taxon_order         (taxon_order)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────
-- 3. UBICACIONES GPS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ubicaciones (
    id_ubicacion      VARCHAR(50)     NOT NULL,
    nombre_ubicacion  VARCHAR(255)    NOT NULL,
    estado_provincia  VARCHAR(50)     DEFAULT 'PE-CUS',
    latitud           DECIMAL(10, 8)  NOT NULL,
    longitud          DECIMAL(11, 8)  NOT NULL,
    region            VARCHAR(100)    DEFAULT 'Cusco',
    es_publica        TINYINT(1)      NOT NULL DEFAULT 1,  -- ← añadido

    PRIMARY KEY (id_ubicacion),
    INDEX   idx_provincia           (estado_provincia),
    INDEX   idx_coords              (latitud, longitud)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────
-- 4. OBSERVACIONES (importadas del CSV de Denis)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS observaciones (
    id_observacion    VARCHAR(50)       NOT NULL,
    id_usuario        INT UNSIGNED      NOT NULL,
    species_code      VARCHAR(20)       DEFAULT NULL,
    nombre_comun      VARCHAR(200)      NOT NULL,
    nombre_cientifico VARCHAR(200)      DEFAULT NULL,
    id_ubicacion      VARCHAR(50)       DEFAULT NULL,
    conteo            SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    fecha             DATE              NOT NULL,
    hora              TIME              DEFAULT NULL,
    protocolo         VARCHAR(100)      DEFAULT NULL,
    duracion_min      SMALLINT UNSIGNED DEFAULT NULL,
    distancia_km      DECIMAL(8, 3)     DEFAULT NULL,
    num_observadores  TINYINT UNSIGNED  DEFAULT 1,
    breeding_code     VARCHAR(10)       DEFAULT NULL,
    detalles          TEXT              DEFAULT NULL,
    notas_lista       TEXT              DEFAULT NULL,
    foto_subida       VARCHAR(255)      DEFAULT NULL,
    fuente            ENUM('ebird_csv','manual') NOT NULL DEFAULT 'ebird_csv',
    fecha_registro    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_observacion),

    CONSTRAINT fk_obs_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES  usuarios(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_obs_especie
        FOREIGN KEY (species_code)
        REFERENCES  especies_taxonomia(species_code)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_obs_ubicacion
        FOREIGN KEY (id_ubicacion)
        REFERENCES  ubicaciones(id_ubicacion)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX   idx_obs_usuario         (id_usuario),
    INDEX   idx_obs_fecha           (fecha),
    INDEX   idx_obs_especie         (species_code),
    INDEX   idx_obs_usr_fecha       (id_usuario, fecha DESC)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────
-- 5. REGISTROS (formulario manual de avistamientos)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS registros (
    id_registro        INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    id_usuario         INT UNSIGNED      NOT NULL,
    species_code       VARCHAR(20)       DEFAULT NULL,
    nombre_comun       VARCHAR(200)      NOT NULL,
    id_ubicacion_ref   VARCHAR(50)       DEFAULT NULL,
    nombre_lugar       VARCHAR(200)      DEFAULT NULL,
    latitud            DECIMAL(10, 8)    DEFAULT NULL,
    longitud           DECIMAL(11, 8)    DEFAULT NULL,
    cantidad           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    fecha_avistamiento DATE              NOT NULL,
    notas              TEXT              DEFAULT NULL,
    -- ↓ CORRECCIÓN: columnas clima y comportamiento con ENUM
    clima              ENUM('soleado','nublado','lluvioso','neblina')
                                         DEFAULT NULL,
    comportamiento     ENUM('volando','alimentandose','anidando','en_reposo','cantando')
                                         DEFAULT NULL,
    foto_ave           VARCHAR(255)      DEFAULT NULL,
    fecha_registro     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_registro),

    CONSTRAINT fk_reg_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES  usuarios(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_reg_especie
        FOREIGN KEY (species_code)
        REFERENCES  especies_taxonomia(species_code)
        ON DELETE SET NULL ON UPDATE CASCADE,

    -- ↓ CORRECCIÓN: FK formal para id_ubicacion_ref
    CONSTRAINT fk_reg_ubicacion
        FOREIGN KEY (id_ubicacion_ref)
        REFERENCES  ubicaciones(id_ubicacion)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX   idx_reg_usuario         (id_usuario),
    INDEX   idx_reg_fecha           (fecha_avistamiento),
    INDEX   idx_reg_especie         (species_code),
    INDEX   idx_reg_usr_fecha       (id_usuario, fecha_avistamiento DESC)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────
-- 6. SESIONES (control de sesiones activas, anti-hijacking)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sesiones (
    id_sesion        CHAR(64)       NOT NULL,
    id_usuario       INT UNSIGNED   NOT NULL,
    ip_address       VARCHAR(45)    DEFAULT NULL,
    user_agent       VARCHAR(255)   DEFAULT NULL,
    creada_en        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en        DATETIME       NOT NULL,
    activa           TINYINT(1)     NOT NULL DEFAULT 1,

    PRIMARY KEY (id_sesion),

    CONSTRAINT fk_ses_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES  usuarios(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX   idx_ses_usuario         (id_usuario),
    INDEX   idx_ses_expira          (expira_en),
    INDEX   idx_ses_activa          (activa)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────
-- DATOS INICIALES — 3 usuarios
-- Contraseña: ornis2026 | Hash bcrypt costo 12
-- CORRECCIÓN: hash actualizado a costo 12 (consistente con Auth.php)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO usuarios
    (nombre, apellido, email, password, bio, ebird_username, rol, activo)
VALUES
(
    'Denis', 'Meza',
    'denis@ornis.com',
    '$2b$12$yXR9sxrSsckO008CSM/Ccu5ocKOjYADs5Shu.xa7gjT078kKFnl/.',
    'Observador de aves y estudiante de Ingeniería de Sistemas en la UAC Cusco. Apasionado por la avifauna andino-amazónica del departamento de Cusco.',
    'denis_meza_cusco',
    'admin', 1
),
(
    'María', 'Huanca',
    'maria@ornis.com',
    '$2b$12$yXR9sxrSsckO008CSM/Ccu5ocKOjYADs5Shu.xa7gjT078kKFnl/.',
    'Bióloga egresada de la UNSAAC. Especialista en aves del Valle Sagrado y los bosques de polylepis del Cusco.',
    'mhuanca_aves',
    'observador', 1
),
(
    'Carlos', 'Quispe',
    'carlos@ornis.com',
    '$2b$12$yXR9sxrSsckO008CSM/Ccu5ocKOjYADs5Shu.xa7gjT078kKFnl/.',
    'Guía ornitológico certificado. Ha registrado más de 400 especies en la región de Cusco y el Manu.',
    'cquispe_birding',
    'observador', 1
);
