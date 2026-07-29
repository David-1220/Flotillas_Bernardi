<?php
// =============================================
//   index.php — Dashboard de Flotillas Bernardi
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();
$user_role = $_SESSION['rol'] ?? ($_SESSION['user_role'] ?? 'auxiliar');
$user_id   = $_SESSION['user_id'];

// Consulta filtrada según rol
if ($user_role === 'admin') {
    $stmt = $db->query("
        SELECT v.*, u.nombre AS en_uso_nombre 
        FROM vehiculos v 
        LEFT JOIN usuarios u ON v.en_uso_por = u.id 
        ORDER BY v.id DESC
    ");
} else {
    $stmt = $db->prepare("
        SELECT v.*, u.nombre AS en_uso_nombre 
        FROM vehiculos v 
        LEFT JOIN usuarios u ON v.en_uso_por = u.id 
        WHERE v.usuario_asignado_id = :user_id OR v.en_uso_por = :user_id 
        ORDER BY v.id DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
}

$vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<main class="page-wrapper">
  
  <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h1>Vehículos</h1>
      <p>Selecciona un vehículo para ver su historial, registrar actividad o reportar uso.</p>
    </div>
    <?php if ($user_role === 'admin'): ?>
      <a href="vehiculo_alta.php" class="btn btn-primary">+ Nuevo Vehículo</a>
    <?php endif; ?>
  </div>

  <div class="dl-grid">
    <?php if (empty($vehiculos)): ?>
      <p style="color: var(--text-muted);">No tienes vehículos asignados o en uso actualmente.</p>
    <?php else: ?>
      <?php foreach ($vehiculos as $v): ?>
        <a href="vehiculo_detalle.php?id=<?= (int)$v['id'] ?>" style="text-decoration: none; color: inherit;">
          <div class="card auto-card">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem;">
              <span style="font-size: 1.8rem;">🚙</span>
              <span class="badge-status <?= htmlspecialchars($v['estado'] ?? 'activo') ?>">
                <?= strtoupper(htmlspecialchars($v['estado'] ?? 'ACTIVO')) ?>
              </span>
            </div>

            <h3 style="font-size: 1.2rem; margin-bottom: 0.3rem;">
              <?= htmlspecialchars(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?>
            </h3>
            
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">
              Año: <b><?= htmlspecialchars($v['anio'] ?? 'N/A') ?></b> | Placas: <b style="color: var(--accent);"><?= htmlspecialchars($v['placas'] ?? '') ?></b>
            </p>

            <?php if (!empty($v['en_uso_por'])): ?>
              <p style="font-size: 0.8rem; color: #e67e22; margin-bottom: 0.8rem;">
                En uso por: <b><?= htmlspecialchars($v['en_uso_nombre'] ?? 'Usuario') ?></b>
              </p>
            <?php endif; ?>

            <div style="border-top: 1px solid var(--border); padding-top: 0.8rem; font-size: 0.85rem; display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Kilometraje:</span>
              <b><?= number_format((float)($v['kilometraje_actual'] ?? 0)) ?> km</b>
            </div>

          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</main>

<?php include 'includes/footer.php'; ?>