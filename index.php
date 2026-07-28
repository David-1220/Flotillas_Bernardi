<?php
// =============================================
//  index.php  — Menú principal / Dashboard
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();
// Consultar todos los vehículos
$stmt = $db->query("SELECT * FROM vehiculos ORDER BY id DESC");
$vehiculos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<main class="page-wrapper">
  
  <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h1> Vehículos</h1>
      <p>Selecciona un vehículo para ver su historial completo o registrar actividad.</p>
    </div>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
      <a href="alta.php" class="btn btn-primary">+ Nuevo Vehículo</a>
    <?php endif; ?>
  </div>

  <!-- Grilla usando las clases de tu CSS -->
  <div class="dl-grid">
    <?php foreach ($vehiculos as $v): ?>
      <a href="vehiculo_detalle.php?id=<?= $v['id'] ?>" style="text-decoration: none; color: inherit;">
        <div class="card auto-card">
          
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem;">
            <span style="font-size: 1.8rem;">🚙</span>
            <span class="badge-status <?= $v['estado'] ?>">
              <?= strtoupper($v['estado']) ?>
            </span>
          </div>

          <h3 style="font-size: 1.2rem; margin-bottom: 0.3rem;">
            <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?>
          </h3>
          
          <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">
            Año: <b><?= htmlspecialchars($v['anio']) ?></b> | Placas: <b style="color: var(--accent);"><?= htmlspecialchars($v['placas']) ?></b>
          </p>

          <div style="border-top: 1px solid var(--border); padding-top: 0.8rem; font-size: 0.85rem; display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">Kilometraje:</span>
            <b><?= number_format($v['kilometraje_actual']) ?> km</b>
          </div>

        </div>
      </a>
    <?php endforeach; ?>
  </div>

</main>

<?php include 'includes/footer.php'; ?>
