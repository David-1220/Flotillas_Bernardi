<?php
// vehiculo_detalle.php — Vista 360 del vehículo
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit(); }

$db = getDB();

// 1. Datos del auto
$stmt = $db->prepare("SELECT * FROM vehiculos WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$auto = $stmt->fetch();

if (!$auto) { die("Vehículo no encontrado."); }

// Procesar cambio manual de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $nuevo_estado = $_POST['estado'] ?? 'activo';
    $estados_permitidos = ['activo', 'mantenimiento', 'inactivo'];

    if (in_array($nuevo_estado, $estados_permitidos)) {
        $stmtUpdateEst = $db->prepare("UPDATE vehiculos SET estado = :estado WHERE id = :id");
        $stmtUpdateEst->execute([':estado' => $nuevo_estado, ':id' => $id]);
        
        // Recargar la página para refrescar los datos
        header("Location: vehiculo_detalle.php?id=" . $id . "&msg=estado_ok");
        exit();
    }
}

// 2. Cargas de gasolina del auto (Ordenadas: lo más reciente arriba)
$stmtGas = $db->prepare("SELECT g.*, u.nombre as usuario_nombre FROM cargas_gasolina g LEFT JOIN usuarios u ON g.usuario_id = u.id WHERE g.vehiculo_id = :id ORDER BY g.fecha_carga DESC, g.id DESC");
$stmtGas->execute([':id' => $id]);
$gasolinas = $stmtGas->fetchAll();

// 3. Mantenimientos del auto (Ordenadas: lo más reciente arriba)
$stmtMant = $db->prepare("SELECT * FROM mantenimientos WHERE vehiculo_id = :id ORDER BY fecha_servicio DESC, id DESC");
$stmtMant->execute([':id' => $id]);
$mantenimientos = $stmtMant->fetchAll();

require_once 'includes/header.php';

?>
<?php if (($_GET['msg'] ?? '') === 'gasolina_ok'): ?>
  <div class="alert alert-success" style="margin-bottom: 1.5rem;">
    ⛽ ¡Carga de gasolina registrada correctamente y kilometraje actualizado!
  </div>
<?php elseif (($_GET['msg'] ?? '') === 'mantenimiento_ok'): ?>
  <div class="alert alert-success" style="margin-bottom: 1.5rem;">
    🛠️ ¡Mantenimiento registrado correctamente!
  </div>
<?php endif; ?>

<main class="page-wrapper">
  
  <div style="margin-bottom: 1rem;">
    <a href="index.php" class="btn btn-outline btn-sm">← Volver al catálogo</a>
  </div>
  
  <!-- Encabezado del Vehículo -->
  <div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h1 style="font-size: 1.8rem; margin-bottom: 0.4rem;">
          <?= htmlspecialchars($auto['marca'] . ' ' . $auto['modelo']) ?> (<?= $auto['anio'] ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
          Placas: <b style="color: var(--accent);"><?= htmlspecialchars($auto['placas']) ?></b> | 
          VIN: <?= htmlspecialchars($auto['vin'] ?? 'N/A') ?> | 
          Km Actual: <b><?= number_format($auto['kilometraje_actual']) ?> km</b>
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <form method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
    <input type="hidden" name="cambiar_estado" value="1">
    <select name="estado" onchange="this.form.submit()" class="btn btn-outline btn-sm" style="padding: 14px 10px; cursor: pointer;">
        <option value="activo" <?= ($auto['estado'] === 'activo') ? 'selected' : '' ?>>🟢 Activo</option>
        <option value="mantenimiento" <?= ($auto['estado'] === 'mantenimiento') ? 'selected' : '' ?>>🟠 Mantenimiento</option>
        <option value="inactivo" <?= ($auto['estado'] === 'inactivo') ? 'selected' : '' ?>>🔴 Inactivo</option>
    </select>
</form>
        <a href="gasolina_alta.php?vehiculo_id=<?= $auto['id'] ?>" class="btn btn-primary">+ Carga Gasolina</a>
        <a href="mantenimiento_alta.php?vehiculo_id=<?= $auto['id'] ?>" class="btn btn-outline">+ Mantenimiento</a>
      </div>
    </div>
  </div>

  <!-- Tarjeta 1: Combustible -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: flex-start; height: 100%; margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--text);">⛽ Historial de Combustible</h3>
    <div class="table-wrap" style="margin-top: 0; flex: 1;">
      <table style="width: 100%;">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Km</th>
            <th>Litros</th>
            <th>Costo</th>
            <th>Ticket</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($gasolinas)): ?>
            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Sin cargas registradas</td></tr>
        <?php else: ?>
            <?php foreach ($gasolinas as $g): ?>
            <tr>
                <td><?= $g['fecha_carga'] ?></td>
                <td><?= number_format($g['kilometraje']) ?></td>
                <td><?= $g['litros'] ?> L</td>
                <td><b>$<?= number_format($g['costo_total'], 2) ?></b></td>
                <td>
                <?php if (!empty($g['ticket_foto'])): ?>
                    <a href="<?= htmlspecialchars($g['ticket_foto']) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                    📄 Ver
                    </a>
                <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
      <br>  
      <!-- Botón corregido con $id del vehículo actual -->
      <a href="reporte_gasolina_excel.php?vehiculo_id=<?= $id ?>" class="btn btn-outline" target="_blank">
          📊 Descargar Reporte en Excel
      </a>
    </div>
  </div>

  <!-- Tarjeta 2: Mantenimientos -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: flex-start; height: 100%; margin: 0;">
    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--text);">🛠️ Mantenimientos</h3>
    <div class="table-wrap" style="margin-top: 0; flex: 1;">
      <table style="width: 100%;">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Servicio</th>
            <th>Taller</th>
            <th>Costo</th>
            <th>Ticket</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($mantenimientos)): ?>
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Sin mantenimientos</td></tr>
          <?php else: ?>
            <?php foreach ($mantenimientos as $m): ?>
              <tr>
                <td><?= $m['fecha_servicio'] ?></td>
                <td><span class="badge-page"><?= ucfirst($m['tipo_servicio']) ?></span></td>
                <td><?= htmlspecialchars($m['tipo_mantenimiento']) ?></td>
                <td><?= htmlspecialchars($m['taller_proveedor']) ?></td>
                <td><b>$<?= number_format($m['costo_total'], 2) ?></b></td>
                <td>
                <?php if (!empty($m['comprobante_foto'] ?? $m['ticket_foto'] ?? '')): ?>
                    <a href="<?= htmlspecialchars($m['comprobante_foto'] ?? $m['ticket_foto']) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                    📄 Ver
                    </a>
                <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <br>
      <!-- Botón corregido con $id del vehículo actual -->
      <a href="reporte_mantenimiento_excel.php?vehiculo_id=<?= $id ?>" class="btn btn-outline" target="_blank">
          🛠️ Descargar Reporte de Mantenimientos
      </a>
    </div>
  </div>

</main>

<?php include 'includes/footer.php'; ?>