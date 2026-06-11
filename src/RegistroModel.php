<?php
// ============================================================
//  src/RegistroModel.php  —  ORNIS v3.1 FIXED
//
//  FIX DASHBOARD EN 0:
//  El dashboard solo consultaba la tabla `registros` (entradas
//  manuales del formulario). Las 835 observaciones importadas
//  del CSV de Denis viven en la tabla `observaciones`.
//
//  SOLUCIÓN: estadisticas(), listar() y fotos() ahora hacen
//  UNION de ambas tablas para mostrar el total real.
//
//  FIX GALERÍA PRIVADA:
//  Nuevo método fotosPrivadas() con soporte para is_public,
//  y método togglePublica() para publicar/despublicar fotos.
// ============================================================

declare(strict_types=1);

class RegistroModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ──────────────────────────────────────────────────────
    //  GUARDAR avistamiento + foto (transacción atómica)
    // ──────────────────────────────────────────────────────
    public function guardar(int $id_usuario, array $datos, ?array $archivo = null): array
    {
        $requeridos = ['nombre_comun', 'nombre_lugar', 'fecha_avistamiento', 'latitud', 'longitud'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['ok' => false, 'msg' => "El campo '{$campo}' es obligatorio."];
            }
        }

        $foto_nombre = null;
        if (!empty($archivo['name'])) {
            $resultado = $this->procesarFoto($archivo);
            if (!$resultado['ok']) return $resultado;
            $foto_nombre = $resultado['nombre'];
        }

        $species_code   = !empty($datos['species_code']) ? mb_strtolower(trim($datos['species_code'])) : null;
        $nombre_comun   = mb_substr(trim($datos['nombre_comun']),  0, 200);
        $nombre_lugar   = mb_substr(trim($datos['nombre_lugar']),  0, 200);
        $fecha          = trim($datos['fecha_avistamiento']);
        $latitud        = (float) $datos['latitud'];
        $longitud       = (float) $datos['longitud'];
        $cantidad       = max(1, (int)($datos['cantidad'] ?? 1));
        $notas          = mb_substr(trim($datos['notas'] ?? ''), 0, 2000) ?: null;
        $clima          = $datos['clima']          ?? null;
        $comportamiento = $datos['comportamiento'] ?? null;

        if ($species_code !== null) {
            $check = $this->pdo->prepare("SELECT 1 FROM especies_taxonomia WHERE species_code = ? LIMIT 1");
            $check->execute([$species_code]);
            if (!$check->fetchColumn()) $species_code = null;
        }

        try {
            $this->pdo->beginTransaction();
            $id_ubi = $this->upsertUbicacion($nombre_lugar, $latitud, $longitud);

            $stmt = $this->pdo->prepare("
                INSERT INTO registros
                    (id_usuario, species_code, nombre_comun,
                     id_ubicacion_ref, nombre_lugar, latitud, longitud,
                     cantidad, fecha_avistamiento,
                     clima, comportamiento, notas, foto_ave)
                VALUES
                    (:usr, :sc, :nc, :id_ubi, :nl, :lat, :lng,
                     :cant, :fecha, :clima, :comp, :notas, :foto)
            ");
            $stmt->execute([
                ':usr'    => $id_usuario, ':sc'    => $species_code,
                ':nc'     => $nombre_comun, ':id_ubi' => $id_ubi,
                ':nl'     => $nombre_lugar, ':lat'    => $latitud,
                ':lng'    => $longitud,    ':cant'   => $cantidad,
                ':fecha'  => $fecha,       ':clima'  => $clima,
                ':comp'   => $comportamiento, ':notas' => $notas,
                ':foto'   => $foto_nombre,
            ]);
            $this->pdo->commit();
            return ['ok' => true, 'id' => (int)$this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($foto_nombre) {
                $ruta = (defined('UPLOADS_PATH') ? UPLOADS_PATH : 'public/uploads/') . $foto_nombre;
                if (file_exists($ruta)) unlink($ruta);
            }
            error_log('[ORNIS-REG] ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al guardar. Inténtalo de nuevo.'];
        }
    }

    // ──────────────────────────────────────────────────────
    //  LISTAR — UNION de registros manuales + observaciones CSV
    //  FIX: antes solo miraba `registros`, ahora muestra todo
    // ──────────────────────────────────────────────────────
    public function listar(int $id_usuario, int $pagina = 1, int $por_pagina = 10): array
    {
        $por_pagina = max(1, min(50, $por_pagina));
        $offset     = ($pagina - 1) * $por_pagina;

        // Total combinado (registros manuales + observaciones CSV)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM (
                SELECT id_registro AS id FROM registros    WHERE id_usuario = ?
                UNION ALL
                SELECT 0           AS id FROM observaciones WHERE id_usuario = ?
            ) AS combined
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $total = (int) $stmt->fetchColumn();

        // UNION ordenada + paginada
        $stmt = $this->pdo->prepare("
            SELECT
                id_registro,
                nombre_comun,
                species_code,
                nombre_lugar,
                latitud,
                longitud,
                cantidad,
                fecha_avistamiento,
                clima,
                comportamiento,
                notas,
                foto_ave,
                fecha_registro,
                nombre_cientifico,
                familia,
                'manual' AS fuente
            FROM (
                -- Registros del formulario manual
                SELECT
                    r.id_registro,
                    r.nombre_comun,
                    r.species_code,
                    r.nombre_lugar,
                    r.latitud,
                    r.longitud,
                    r.cantidad,
                    r.fecha_avistamiento,
                    r.clima,
                    r.comportamiento,
                    r.notas,
                    r.foto_ave,
                    r.fecha_registro,
                    t.sci_name  AS nombre_cientifico,
                    t.family    AS familia
                FROM registros r
                LEFT JOIN especies_taxonomia t ON r.species_code = t.species_code
                WHERE r.id_usuario = :usr1

                UNION ALL

                -- Observaciones importadas del CSV
                SELECT
                    0                      AS id_registro,
                    o.nombre_comun,
                    o.species_code,
                    COALESCE(u.nombre_ubicacion, 'Sin ubicación') AS nombre_lugar,
                    CAST(u.latitud  AS DECIMAL(10,8))             AS latitud,
                    CAST(u.longitud AS DECIMAL(11,8))             AS longitud,
                    o.conteo                                      AS cantidad,
                    o.fecha                                       AS fecha_avistamiento,
                    NULL                                          AS clima,
                    NULL                                          AS comportamiento,
                    o.detalles                                    AS notas,
                    o.foto_subida                                 AS foto_ave,
                    o.fecha_registro,
                    o.nombre_cientifico,
                    t.family                                      AS familia
                FROM observaciones o
                LEFT JOIN ubicaciones u          ON o.id_ubicacion  = u.id_ubicacion
                LEFT JOIN especies_taxonomia t   ON o.species_code  = t.species_code
                WHERE o.id_usuario = :usr2
            ) AS todo
            ORDER BY fecha_avistamiento DESC, id_registro DESC
            LIMIT :limite OFFSET :offset
        ");
        $stmt->bindValue(':usr1',   $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':usr2',   $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
        $stmt->execute();

        return [
            'registros'     => $stmt->fetchAll(),
            'total'         => $total,
            'pagina'        => $pagina,
            'por_pagina'    => $por_pagina,
            'total_paginas' => (int) ceil($total / $por_pagina),
        ];
    }

    // ──────────────────────────────────────────────────────
    //  ESTADÍSTICAS — FIX: UNION de ambas tablas
    //  Antes: solo contaba `registros` → mostraba 0 para Denis
    // ──────────────────────────────────────────────────────
    public function estadisticas(int $id_usuario): array
    {
        // Conteos combinados de ambas tablas
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*)                        AS total_registros,
                COUNT(DISTINCT species_code)    AS total_especies,
                COUNT(DISTINCT nombre_lugar)    AS total_lugares,
                MIN(fecha)                      AS primera_fecha,
                MAX(fecha)                      AS ultima_fecha
            FROM (
                SELECT species_code, nombre_lugar, fecha_avistamiento AS fecha
                FROM   registros
                WHERE  id_usuario = ?

                UNION ALL

                SELECT
                    o.species_code,
                    COALESCE(u.nombre_ubicacion, 'Sin ubicación') AS nombre_lugar,
                    o.fecha
                FROM observaciones o
                LEFT JOIN ubicaciones u ON o.id_ubicacion = u.id_ubicacion
                WHERE o.id_usuario = ?
            ) AS todo
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $stats = $stmt->fetch();

        // Fotos: solo en registros manuales (las obs. del CSV raramente tienen foto)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(foto_ave) AS total_fotos
            FROM   registros
            WHERE  id_usuario = ?
        ");
        $stmt->execute([$id_usuario]);
        $fotos = (int)($stmt->fetchColumn() ?? 0);

        // Especie más avistada (entre ambas tablas)
        $stmt = $this->pdo->prepare("
            SELECT nombre_comun, COUNT(*) AS veces
            FROM (
                SELECT nombre_comun FROM registros     WHERE id_usuario = ? AND nombre_comun IS NOT NULL
                UNION ALL
                SELECT nombre_comun FROM observaciones WHERE id_usuario = ? AND nombre_comun IS NOT NULL
            ) AS todo
            GROUP BY nombre_comun
            ORDER BY veces DESC
            LIMIT 1
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $favorita = $stmt->fetch();

        // Días activos
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT fecha) AS dias_activos
            FROM (
                SELECT fecha_avistamiento AS fecha FROM registros     WHERE id_usuario = ?
                UNION ALL
                SELECT fecha              AS fecha FROM observaciones WHERE id_usuario = ?
            ) AS todo
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $dias = (int)$stmt->fetchColumn();

        return [
            'total_registros'  => (int)($stats['total_registros'] ?? 0),
            'total_especies'   => (int)($stats['total_especies']  ?? 0),
            'total_lugares'    => (int)($stats['total_lugares']   ?? 0),
            'total_fotos'      => $fotos,
            'primera_fecha'    => $stats['primera_fecha'] ?? null,
            'ultima_fecha'     => $stats['ultima_fecha']  ?? null,
            'especie_favorita' => $favorita['nombre_comun'] ?? null,
            'dias_activos'     => $dias,
        ];
    }

    // ──────────────────────────────────────────────────────
    //  FOTOS DEL USUARIO — solo registros con foto subida
    // ──────────────────────────────────────────────────────
    public function fotos(int $id_usuario, int $limite = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT  r.id_registro, r.foto_ave, r.nombre_comun,
                    r.nombre_lugar, r.fecha_avistamiento, r.notas,
                    t.sci_name AS nombre_cientifico
            FROM    registros r
            LEFT JOIN especies_taxonomia t ON r.species_code = t.species_code
            WHERE   r.id_usuario = :usr
              AND   r.foto_ave   IS NOT NULL
            ORDER BY r.fecha_avistamiento DESC
            LIMIT   :lim
        ");
        $stmt->bindValue(':usr', $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limite,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ──────────────────────────────────────────────────────
    //  GALERÍA PRIVADA — fotos del usuario con control is_public
    //  FIX: antes redirigía a galería general. Ahora hay un
    //  endpoint dedicado galeria_privada.php + este método.
    // ──────────────────────────────────────────────────────
    public function fotosPrivadas(int $id_usuario, int $pagina = 1, int $por_pagina = 20): array
    {
        $por_pagina = max(1, min(60, $por_pagina));
        $offset     = ($pagina - 1) * $por_pagina;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM registros WHERE id_usuario = ? AND foto_ave IS NOT NULL");
        $stmt->execute([$id_usuario]);
        $total = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT  r.id_registro,
                    r.foto_ave,
                    r.nombre_comun,
                    r.nombre_lugar,
                    r.fecha_avistamiento,
                    r.notas,
                    r.is_public,
                    t.sci_name AS nombre_cientifico,
                    t.family   AS familia
            FROM    registros r
            LEFT JOIN especies_taxonomia t ON r.species_code = t.species_code
            WHERE   r.id_usuario = :usr
              AND   r.foto_ave   IS NOT NULL
            ORDER BY r.fecha_avistamiento DESC, r.id_registro DESC
            LIMIT   :lim OFFSET :off
        ");
        $stmt->bindValue(':usr', $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,     PDO::PARAM_INT);
        $stmt->execute();

        return [
            'fotos'         => $stmt->fetchAll(),
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int) ceil($total / $por_pagina),
        ];
    }

    // ──────────────────────────────────────────────────────
    //  TOGGLE PÚBLICA — cambia is_public de 0 a 1 y viceversa
    //  Verifica que el registro pertenezca al usuario.
    // ──────────────────────────────────────────────────────
    public function togglePublica(int $id_registro, int $id_usuario): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_registro, is_public FROM registros
            WHERE id_registro = ? AND id_usuario = ?
        ");
        $stmt->execute([$id_registro, $id_usuario]);
        $reg = $stmt->fetch();

        if (!$reg) return ['ok' => false, 'msg' => 'Foto no encontrada o sin permiso.'];

        $nuevo_estado = $reg['is_public'] ? 0 : 1;
        $stmt = $this->pdo->prepare("UPDATE registros SET is_public = ? WHERE id_registro = ? AND id_usuario = ?");
        $stmt->execute([$nuevo_estado, $id_registro, $id_usuario]);

        return [
            'ok'        => true,
            'is_public' => (bool) $nuevo_estado,
            'msg'       => $nuevo_estado ? 'Foto publicada en la galería general.' : 'Foto retirada de la galería general.',
        ];
    }

    // ──────────────────────────────────────────────────────
    //  ELIMINAR — verifica autoría antes de borrar
    // ──────────────────────────────────────────────────────
    public function eliminar(int $id_registro, int $id_usuario): bool
    {
        $stmt = $this->pdo->prepare("SELECT foto_ave FROM registros WHERE id_registro = ? AND id_usuario = ?");
        $stmt->execute([$id_registro, $id_usuario]);
        $reg = $stmt->fetch();
        if (!$reg) return false;

        if (!empty($reg['foto_ave'])) {
            $ruta = (defined('UPLOADS_PATH') ? UPLOADS_PATH : 'public/uploads/') . $reg['foto_ave'];
            if (file_exists($ruta)) unlink($ruta);
        }

        $stmt = $this->pdo->prepare("DELETE FROM registros WHERE id_registro = ? AND id_usuario = ?");
        $stmt->execute([$id_registro, $id_usuario]);
        return $stmt->rowCount() > 0;
    }

    // ──────────────────────────────────────────────────────
    //  PRIVADO: Procesar foto subida
    // ──────────────────────────────────────────────────────
    private function procesarFoto(array $archivo): array
    {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidas = defined('FOTOS_EXT_OK') ? FOTOS_EXT_OK : ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $permitidas, true))
            return ['ok' => false, 'msg' => 'Formato no permitido (JPG, PNG, WEBP).'];
        $max = defined('FOTOS_MAX_BYTES') ? FOTOS_MAX_BYTES : 10 * 1024 * 1024;
        if ($archivo['size'] > $max)
            return ['ok' => false, 'msg' => 'La imagen supera los 10 MB.'];
        if (@getimagesize($archivo['tmp_name']) === false)
            return ['ok' => false, 'msg' => 'El archivo no es una imagen válida.'];
        $dir = defined('UPLOADS_PATH') ? UPLOADS_PATH : __DIR__ . '/../public/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $nombre = 'ave_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($archivo['tmp_name'], $dir . $nombre))
            return ['ok' => false, 'msg' => 'Error al guardar la imagen.'];
        return ['ok' => true, 'nombre' => $nombre];
    }

    // ──────────────────────────────────────────────────────
    //  PRIVADO: Upsert de ubicación
    // ──────────────────────────────────────────────────────
    private function upsertUbicacion(string $nombre, float $lat, float $lng): string
    {
        $stmt = $this->pdo->prepare("SELECT id_ubicacion FROM ubicaciones WHERE nombre_ubicacion = ? LIMIT 1");
        $stmt->execute([$nombre]);
        $existente = $stmt->fetchColumn();
        if ($existente) return (string) $existente;
        $id   = 'M_' . bin2hex(random_bytes(8));
        $stmt = $this->pdo->prepare("INSERT INTO ubicaciones (id_ubicacion, nombre_ubicacion, latitud, longitud, estado_provincia) VALUES (?, ?, ?, ?, 'PE-CUS')");
        $stmt->execute([$id, $nombre, $lat, $lng]);
        return $id;
    }
}
