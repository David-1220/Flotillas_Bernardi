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
        
        header("Location: vehiculo_detalle.php?id=" . $id . "&msg=estado_ok");
        exit();
    }
}

// 2. Cargas de gasolina del auto
$stmtGas = $db->prepare("SELECT g.*, u.nombre as usuario_nombre FROM cargas_gasolina g LEFT JOIN usuarios u ON g.usuario_id = u.id WHERE g.vehiculo_id = :id ORDER BY g.fecha_carga DESC, g.id DESC");
$stmtGas->execute([':id' => $id]);
$gasolinas = $stmtGas->fetchAll();

// 3. Mantenimientos del auto
$stmtMant = $db->prepare("SELECT * FROM mantenimientos WHERE vehiculo_id = :id ORDER BY fecha_servicio DESC, id DESC");
$stmtMant->execute([':id' => $id]);
$mantenimientos = $stmtMant->fetchAll();

// 4. Consultar viaje activo en la bitácora
$stmtUso = $db->prepare("SELECT * FROM bitacora_uso WHERE vehiculo_id = :id AND estado = 'en_transito' ORDER BY id DESC LIMIT 1");
$stmtUso->execute([':id' => $id]);
$viaje_activo = $stmtUso->fetch();

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

<main class="page-wrapper" style="display: flex; flex-direction: column; gap: 1.5rem;">
  
  <div>
    <a href="index.php" class="btn btn-outline btn-sm">← Volver al catálogo</a>
  </div>
  
  <!-- Encabezado del Vehículo -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h1 style="font-size: 1.8rem; margin-bottom: 0.4rem;">
          <?= htmlspecialchars($auto['marca'] . ' ' . $auto['modelo']) ?> (<?= $auto['anio'] ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
          Placas: <b style="color: var(--accent);"><?= htmlspecialchars($auto['placas']) ?></b> | 
          VIN: <?= htmlspecialchars($auto['vin'] ?? 'N/A') ?> | 
          Km Actual: <b><?= number_format($auto['kilometraje_actual']) ?> km</b>
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <form method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
          <input type="hidden" name="cambiar_estado" value="1">
          <select name="estado" onchange="this.form.submit()" class="btn btn-outline btn-sm" style="padding: 10px; cursor: pointer;">
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

  <!-- CONTROL DE USO Y BITÁCORA -->
  <div class="card">
    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--text);">📋 Control de Uso de Unidad</h3>

    <?php if ($viaje_activo): ?>
      <div style="background: rgba(230, 126, 34, 0.15); border-left: 4px solid #e67e22; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
        <p style="margin-bottom: 0.3rem;"><b>⚠️ Vehículo actualmente en tránsito.</b></p>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
          Salida registrada el: <b><?= $viaje_activo['fecha_salida'] ?></b> | 
          Km Inicial: <b><?= number_format($viaje_activo['km_salida']) ?> km</b>
        </p>
      </div>

      <?php if ($viaje_activo['usuario_id'] == $_SESSION['user_id'] || ($_SESSION['rol'] ?? $_SESSION['user_role'] ?? '') === 'admin'): ?>
        <form action="uso_registrar.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
          <input type="hidden" name="action" value="entrada">
          <input type="hidden" name="bitacora_id" value="<?= $viaje_activo['id'] ?>">
          <input type="hidden" name="vehiculo_id" value="<?= $auto['id'] ?>">

          <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
            <div>
              <label style="font-size: 0.85rem; font-weight: bold; display: block; margin-bottom: 0.3rem;">Km Final de Entrega:</label>
              <input type="number" name="km_entrada" class="form-control" min="<?= $viaje_activo['km_salida'] ?>" value="<?= $auto['kilometraje_actual'] ?>" required style="width: 100%; padding: 8px;">
            </div>
            <div>
              <label style="font-size: 0.85rem; font-weight: bold; display: block; margin-bottom: 0.3rem;">Observaciones al Devolver:</label>
              <input type="text" name="observaciones_entrada" class="form-control" placeholder="Sin novedad, entrega limpio, etc." style="width: 100%; padding: 8px;">
            </div>
          </div>

          <div>
            <button type="submit" class="btn btn-warning" style="background-color: #e67e22; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
              🏁 Finalizar Viaje / Devolver Unidad
            </button>
          </div>
        </form>
      <?php endif; ?>

    <?php else: ?>

      <form action="uso_registrar.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
        <input type="hidden" name="action" value="salida">
        <input type="hidden" name="vehiculo_id" value="<?= $auto['id'] ?>">

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
          <div>
            <label style="font-size: 0.85rem; font-weight: bold; display: block; margin-bottom: 0.3rem;">KM DE SALIDA:</label>
            <input type="number" name="km_salida" value="<?= (int)$auto['kilometraje_actual'] ?>" class="form-control" required style="width: 100%; padding: 8px;">
          </div>
          <div>
            <label style="font-size: 0.85rem; font-weight: bold; display: block; margin-bottom: 0.3rem;">MOTIVO O DESTINO DEL VIAJE:</label>
            <input type="text" name="motivo_salida" class="form-control" placeholder="Ej. Entrega de pedido, visita a sucursal..." required style="width: 100%; padding: 8px;">
          </div>
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="padding: 10px 15px; border-radius: 5px; cursor: pointer;">
            🚗 Registrar Salida de Vehículo
          </button>
        </div>
      </form>

    <?php endif; ?>
      <br>
    <!-- BOTÓN DE DESCARGA DE REPORTES DE USO -->
      <a href="reporte_uso_excel.php?vehiculo_id=<?= $id ?>" class="btn btn-outline" target="_blank">
        📋 Descargar Reporte de Uso en Excel
      </a>
  </div>

  </div>

<!-- Tarjeta 1: Combustible -->
  <div class="card">
    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--text);">⛽ Historial de Combustible</h3>
    
    <!-- Aquí agregamos el max-height y el overflow-y para el scroll -->
    <div class="table-wrap" style="max-height: 280px; overflow-y: auto; padding-right: 5px;">
      <table style="width: 100%; border-collapse: collapse;">
        <!-- position: sticky mantiene los títulos fijos al hacer scroll -->
        <thead style="position: sticky; top: 0; background-color: #162133; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
          <tr>
            <th style="padding: 12px 10px;">FECHA</th>
            <th style="padding: 12px 10px;">KM</th>
            <th style="padding: 12px 10px;">LITROS</th>
            <th style="padding: 12px 10px;">COSTO</th>
            <th style="padding: 12px 10px;">TICKET</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($gasolinas)): ?>
            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">Sin cargas registradas</td></tr>
        <?php else: ?>
            <?php foreach ($gasolinas as $g): ?>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 10px;"><?= $g['fecha_carga'] ?></td>
                <td style="padding: 10px;"><?= number_format($g['kilometraje']) ?></td>
                <td style="padding: 10px;"><?= $g['litros'] ?> L</td>
                <td style="padding: 10px;"><b>$<?= number_format($g['costo_total'], 2) ?></b></td>
                <td style="padding: 10px;">
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
    </div>
    
    <div style="margin-top: 1rem;">
      <a href="reporte_gasolina_excel.php?vehiculo_id=<?= $id ?>" class="btn btn-outline" target="_blank">
          📊 Descargar Reporte en Excel
      </a>
    </div>
  </div>

  <!-- Tarjeta 2: Mantenimientos -->
  <div class="card">
    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--text);">🛠️ Mantenimientos</h3>
    
    <!-- Mismo contenedor con scroll para mantenimientos -->
    <div class="table-wrap" style="max-height: 280px; overflow-y: auto; padding-right: 5px;">
      <table style="width: 100%; border-collapse: collapse;">
        <thead style="position: sticky; top: 0; background-color: #162133; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
          <tr>
            <th style="padding: 12px 10px;">FECHA</th>
            <th style="padding: 12px 10px;">TIPO</th>
            <th style="padding: 12px 10px;">SERVICIO</th>
            <th style="padding: 12px 10px;">TALLER</th>
            <th style="padding: 12px 10px;">COSTO</th>
            <th style="padding: 12px 10px;">TICKET</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($mantenimientos)): ?>
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">Sin mantenimientos</td></tr>
          <?php else: ?>
            <?php foreach ($mantenimientos as $m): ?>
              <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 10px;"><?= $m['fecha_servicio'] ?></td>
                <td style="padding: 10px;"><span class="badge-page"><?= ucfirst($m['tipo_servicio']) ?></span></td>
                <td style="padding: 10px;"><?= htmlspecialchars($m['tipo_mantenimiento']) ?></td>
                <td style="padding: 10px;"><?= htmlspecialchars($m['taller_proveedor']) ?></td>
                <td style="padding: 10px;"><b>$<?= number_format($m['costo_total'], 2) ?></b></td>
                <td style="padding: 10px;">
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
    </div>
    
    <div style="margin-top: 1rem;">
      <a href="reporte_mantenimiento_excel.php?vehiculo_id=<?= $id ?>" class="btn btn-outline" target="_blank">
          🛠️ Descargar Reporte de Mantenimientos
      </a>
    </div>
  </div>

</main>

<?php include 'includes/footer.php'; ?>