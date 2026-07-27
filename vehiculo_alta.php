<?php
// =============================================
//  vehiculo_alta.php — Dar de alta un vehículo
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

// Bloqueo de seguridad: solo administradores
if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca      = trim($_POST['marca'] ?? '');
    $modelo     = trim($_POST['modelo'] ?? '');
    $anio       = intval($_POST['anio'] ?? date('Y'));
    $placas     = strtoupper(trim($_POST['placas'] ?? ''));
    $vin        = strtoupper(trim($_POST['vin'] ?? ''));
    $km_actual  = floatval($_POST['kilometraje_actual'] ?? 0);
    $estado     = $_POST['estado'] ?? 'activo';

    if (empty($marca) || empty($modelo) || empty($placas)) {
        $error = 'Por favor ingresa Marca, Modelo y Placas.';
    } else {
        try {
            $sql = "INSERT INTO vehiculos (marca, modelo, anio, placas, vin, kilometraje_actual, estado) 
                    VALUES (:marca, :modelo, :anio, :placas, :vin, :km, :estado)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':marca'   => $marca,
                ':modelo'  => $modelo,
                ':anio'    => $anio,
                ':placas'  => $placas,
                ':vin'     => $vin,
                ':km'      => $km_actual,
                ':estado'  => $estado
            ]);

            header("Location: index.php?msg=vehiculo_creado");
            exit();
        } catch (PDOException $e) {
            $error = 'Error al registrar vehículo (verifica si las placas ya existen): ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<main class="page-wrapper">
  <div style="margin-bottom: 1rem;">
    <a href="index.php" class="btn btn-outline btn-sm">← Volver al catálogo</a>
  </div>

  <div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 1.2rem;">
      <h1>🚗 Agregar Nuevo Vehículo</h1>
      <p>Registra una nueva unidad a la flotilla.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="vehiculo_alta.php" class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
      <div class="form-group">
        <label for="marca">Marca *</label>
        <input type="text" id="marca" name="marca" placeholder="Ej. Chevrolet" required />
      </div>

      <div class="form-group">
        <label for="modelo">Modelo *</label>
        <input type="text" id="modelo" name="modelo" placeholder="Ej. Aveo" required />
      </div>

      <div class="form-group">
        <label for="anio">Año *</label>
        <input type="number" id="anio" name="anio" value="<?= date('Y') ?>" min="1990" max="<?= date('Y') + 1 ?>" required />
      </div>

      <div class="form-group">
        <label for="placas">Placas *</label>
        <input type="text" id="placas" name="placas" placeholder="Ej. LMN-456-C" required />
      </div>

      <div class="form-group">
        <label for="vin">Número VIN / Serie</label>
        <input type="text" id="vin" name="vin" placeholder="Ej. 3G1BC5SM3FL123456" />
      </div>

      <div class="form-group">
        <label for="kilometraje_actual">Kilometraje Inicial *</label>
        <input type="number" id="kilometraje_actual" name="kilometraje_actual" value="0" min="0" required />
      </div>

      <div class="form-group full">
        <label for="estado">Estado Inicial</label>
        <select id="estado" name="estado">
          <option value="activo">🟢 Activo</option>
          <option value="mantenimiento">🟠 Mantenimiento</option>
          <option value="inactivo">🔴 Inactivo</option>
        </select>
      </div>

      <div class="form-group full btn-actions" style="margin-top: 1rem;">
        <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
          💾 Guardar Vehículo
        </button>
        <a href="index.php" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>