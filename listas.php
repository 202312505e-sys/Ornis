<?php
// ============================================================
//  listas.php — CRUD de Listas de Avistamiento (eBird-style)
//  Permite crear, ver, editar y eliminar listas con múltiples
//  especies, similar a una lista de campo eBird.
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/rutas.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/Auth.php';

Auth::requerir('auth.php');

$id_usuario = Auth::id();
$nombre_user = Auth::nombre();

function e(mixed $v):string{ return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }

// ── Obtener listas del usuario
$stmt = $pdo->prepare("
    SELECT l.*,
           COUNT(le.id) AS n_especies,
           SUM(le.cantidad) AS total_aves
    FROM listas_avistamiento l
    LEFT JOIN lista_especies le ON le.id_lista = l.id_lista
    WHERE l.id_usuario = ?
    GROUP BY l.id_lista
    ORDER BY l.fecha_lista DESC, l.created_at DESC
");
$stmt->execute([$id_usuario]);
$listas = $stmt->fetchAll();

$msg_ok  = isset($_GET['ok'])  ? htmlspecialchars(urldecode($_GET['ok']),  ENT_QUOTES,'UTF-8') : null;
$msg_err = isset($_GET['err']) ? htmlspecialchars(urldecode($_GET['err']), ENT_QUOTES,'UTF-8') : null;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mis Listas — ORNIS</title>
  <link rel="stylesheet" href="<?= CSS_URL ?>"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script>(function(){const t=localStorage.getItem('ornis-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    .page-wrap{max-width:1100px;margin:0 auto;padding:calc(var(--nav-h)+32px) 24px 60px}
    .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:14px}
    .page-title{font-family:var(--font-display);font-size:2rem;color:var(--text-primary)}
    .page-title span{color:var(--mo-corona)}
    .btn-new-lista{
      display:inline-flex;align-items:center;gap:8px;
      background:var(--mo-corona);color:#fff;
      padding:11px 22px;border-radius:var(--r-pill);
      font-weight:600;font-size:.9rem;cursor:pointer;border:none;
      transition:background var(--t),transform var(--t);
      font-family:var(--font-body);text-decoration:none;
    }
    .btn-new-lista:hover{background:var(--accent-hover);transform:translateY(-1px)}

    /* Cards grid */
    .listas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
    .lista-card{
      background:var(--bg-card);border:1px solid var(--border-base);
      border-radius:var(--r-lg);overflow:hidden;
      transition:box-shadow var(--t),transform var(--t);
    }
    .lista-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
    .lista-card-header{
      padding:18px 20px 14px;
      border-bottom:1px solid var(--border-base);
      display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
    }
    .lista-nombre{font-weight:700;font-size:1rem;color:var(--text-primary);margin-bottom:4px}
    .lista-meta{font-size:.78rem;color:var(--text-muted);display:flex;flex-wrap:wrap;gap:8px}
    .lista-meta span{display:flex;align-items:center;gap:4px}
    .lista-card-body{padding:16px 20px}
    .lista-especies-preview{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
    .lista-esp-row{
      display:flex;align-items:center;justify-content:space-between;
      font-size:.83rem;color:var(--text-secondary);
      padding:4px 8px;border-radius:var(--r-sm);
      background:var(--bg-muted);
    }
    .lista-esp-name{font-weight:600;color:var(--text-primary)}
    .lista-esp-sci{font-style:italic;color:var(--text-muted);font-size:.75rem}
    .lista-esp-cant{
      background:var(--mo-corona);color:#fff;
      padding:2px 9px;border-radius:var(--r-pill);font-size:.72rem;font-weight:700;
    }
    .lista-more{font-size:.75rem;color:var(--mo-corona);text-align:center;padding:4px}
    .lista-card-footer{
      padding:12px 20px;border-top:1px solid var(--border-base);
      display:flex;gap:8px;
    }
    .btn-lista-action{
      flex:1;padding:8px;border-radius:var(--r-sm);
      font-size:.82rem;font-weight:600;cursor:pointer;border:none;
      display:flex;align-items:center;justify-content:center;gap:5px;
      font-family:var(--font-body);transition:background var(--t);text-decoration:none;
    }
    .btn-ver{background:var(--bg-muted);color:var(--text-primary)}
    .btn-ver:hover{background:var(--border-base)}
    .btn-editar-l{background:rgba(26,143,175,.15);color:var(--mo-corona)}
    .btn-editar-l:hover{background:rgba(26,143,175,.25)}
    .btn-del-l{background:rgba(220,50,50,.1);color:#e05050}
    .btn-del-l:hover{background:rgba(220,50,50,.2)}

    /* Badge tipo */
    .badge-tipo{
      padding:3px 10px;border-radius:var(--r-pill);font-size:.7rem;font-weight:700;
      text-transform:uppercase;letter-spacing:.5px;flex-shrink:0;
    }
    .badge-campo{background:rgba(72,194,122,.15);color:#48C27A}
    .badge-recorrido{background:rgba(26,143,175,.15);color:var(--mo-corona)}
    .badge-punto{background:rgba(212,160,85,.15);color:var(--mo-canela)}

    /* Stats bar */
    .stats-bar{
      display:flex;gap:20px;flex-wrap:wrap;
      background:var(--bg-card);border:1px solid var(--border-base);
      border-radius:var(--r-lg);padding:18px 24px;margin-bottom:28px;
    }
    .stat-item{text-align:center}
    .stat-item .n{font-size:1.6rem;font-weight:700;color:var(--mo-corona);font-family:var(--font-display)}
    .stat-item .l{font-size:.75rem;color:var(--text-muted)}

    /* Empty state */
    .empty-state{
      text-align:center;padding:80px 20px;
      color:var(--text-muted);
    }
    .empty-state i{font-size:4rem;margin-bottom:20px;opacity:.3;display:block}
    .empty-state h3{font-size:1.3rem;color:var(--text-secondary);margin-bottom:8px}

    /* Alert */
    .alert{padding:12px 18px;border-radius:var(--r-md);margin-bottom:20px;font-size:.9rem;display:flex;align-items:center;gap:8px}
    .alert-ok{background:rgba(72,194,122,.12);color:#48C27A;border:1px solid rgba(72,194,122,.2)}
    .alert-err{background:rgba(220,50,50,.1);color:#e05050;border:1px solid rgba(220,50,50,.2)}
  </style>
</head>
<body>
<?php include __DIR__ . '/views/partials/navbar.php'; ?>

<div class="page-wrap">
  <div class="page-header">
    <div>
      <h1 class="page-title">Mis <span>Listas</span></h1>
      <p style="color:var(--text-muted);font-size:.9rem;margin-top:4px">
        Registra listas de campo con múltiples especies — igual que eBird
      </p>
    </div>
    <a href="lista_form.php" class="btn-new-lista">
      <i class="fa-solid fa-plus"></i> Nueva Lista
    </a>
  </div>

  <?php if($msg_ok): ?><div class="alert alert-ok"><i class="fa-solid fa-check-circle"></i> <?= $msg_ok ?></div><?php endif; ?>
  <?php if($msg_err): ?><div class="alert alert-err"><i class="fa-solid fa-exclamation-circle"></i> <?= $msg_err ?></div><?php endif; ?>

  <!-- Stats -->
  <?php
  $total_listas   = count($listas);
  $total_especies = array_sum(array_column($listas,'n_especies'));
  $total_aves_all = array_sum(array_column($listas,'total_aves'));
  $fechas         = array_column($listas,'fecha_lista');
  $ultima_fecha   = $fechas ? max($fechas) : null;
  ?>
  <?php if($total_listas > 0): ?>
  <div class="stats-bar">
    <div class="stat-item"><div class="n"><?= $total_listas ?></div><div class="l">Listas creadas</div></div>
    <div class="stat-item"><div class="n"><?= $total_especies ?></div><div class="l">Registros de especies</div></div>
    <div class="stat-item"><div class="n"><?= number_format((int)$total_aves_all) ?></div><div class="l">Aves contadas</div></div>
    <?php if($ultima_fecha): ?>
    <div class="stat-item"><div class="n" style="font-size:1rem"><?= date('d/m/Y',strtotime($ultima_fecha)) ?></div><div class="l">Última salida</div></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Grid de listas -->
  <?php if(empty($listas)): ?>
  <div class="empty-state">
    <i class="fa-solid fa-clipboard-list"></i>
    <h3>Aún no tienes listas de campo</h3>
    <p>Crea tu primera lista para registrar múltiples especies en una salida de observación.</p>
    <a href="lista_form.php" class="btn-new-lista" style="display:inline-flex;margin-top:20px">
      <i class="fa-solid fa-plus"></i> Crear primera lista
    </a>
  </div>
  <?php else: ?>
  <div class="listas-grid">
    <?php foreach($listas as $lista):
      // Obtener especies de la lista
      $st = $pdo->prepare("SELECT le.*, t.sci_name FROM lista_especies le LEFT JOIN especies_taxonomia t ON le.species_code=t.species_code WHERE le.id_lista=? ORDER BY le.orden ASC LIMIT 5");
      $st->execute([$lista['id_lista']]);
      $especies_preview = $st->fetchAll();
      $tipos = ['libre'=>'campo','estacionario'=>'punto','en_movimiento'=>'recorrido'];
      $tipo_label = ['libre'=>'Campo libre','estacionario'=>'Punto fijo','en_movimiento'=>'En movimiento'];
      $tipo_class = ['libre'=>'badge-campo','estacionario'=>'badge-punto','en_movimiento'=>'badge-recorrido'];
      $tipo = $lista['tipo_protocolo'] ?? 'libre';
    ?>
    <div class="lista-card">
      <div class="lista-card-header">
        <div style="flex:1">
          <div class="lista-nombre"><?= e($lista['nombre_lista']) ?></div>
          <div class="lista-meta">
            <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y',strtotime($lista['fecha_lista'])) ?></span>
            <span><i class="fa-solid fa-location-dot"></i> <?= e(mb_substr($lista['lugar_nombre']??'Sin lugar',0,30)) ?></span>
            <?php if(!empty($lista['duracion_min'])): ?>
            <span><i class="fa-regular fa-clock"></i> <?= (int)$lista['duracion_min'] ?> min</span>
            <?php endif; ?>
          </div>
        </div>
        <span class="badge-tipo <?= $tipo_class[$tipo] ?? 'badge-campo' ?>">
          <?= $tipo_label[$tipo] ?? 'Campo' ?>
        </span>
      </div>
      <div class="lista-card-body">
        <?php if(empty($especies_preview)): ?>
          <p style="font-size:.82rem;color:var(--text-muted);text-align:center;padding:8px">Sin especies registradas</p>
        <?php else: ?>
        <div class="lista-especies-preview">
          <?php foreach($especies_preview as $esp): ?>
          <div class="lista-esp-row">
            <div>
              <div class="lista-esp-name"><?= e($esp['nombre_comun']) ?></div>
              <?php if(!empty($esp['sci_name'])): ?><div class="lista-esp-sci"><?= e($esp['sci_name']) ?></div><?php endif; ?>
            </div>
            <span class="lista-esp-cant"><?= $esp['cantidad'] == 0 ? 'X' : (int)$esp['cantidad'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if((int)$lista['n_especies'] > 5): ?>
          <div class="lista-more">+<?= (int)$lista['n_especies'] - 5 ?> especies más</div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="lista-card-footer">
        <a href="lista_ver.php?id=<?= (int)$lista['id_lista'] ?>" class="btn-lista-action btn-ver">
          <i class="fa-solid fa-eye"></i> Ver
        </a>
        <a href="lista_form.php?edit=<?= (int)$lista['id_lista'] ?>" class="btn-lista-action btn-editar-l">
          <i class="fa-solid fa-pen"></i> Editar
        </a>
        <button onclick="confirmarEliminar(<?= (int)$lista['id_lista'] ?>, '<?= e(addslashes($lista['nombre_lista'])) ?>')"
                class="btn-lista-action btn-del-l">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function confirmarEliminar(id, nombre) {
  if (confirm(`¿Eliminar la lista "${nombre}"?\nEsta acción no se puede deshacer.`)) {
    window.location.href = `lista_eliminar.php?id=${id}`;
  }
}
</script>
<script src="<?= JS_URL ?>"></script>
</body>
</html>
