<?php
// =============================================
//  mantenimiento_alta.php — Registro de Mantenimientos
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();
$mensaje = '';
$error = '';

$vehiculo_id_preset = $_GET['vehiculo_id'] ?? null;

$stmtVehiculos = $db->query("SELECT id, marca, modelo, placas, kilometraje_actual FROM vehiculos WHERE estado != 'inactivo' ORDER BY marca ASC");
$vehiculos = $stmtVehiculos->fetchAll();

// Función de compresión idéntica para fotos de tickets/facturas de taller
function comprimirImagen($rutaOrigen, $rutaDestino, $calidad = 70, $anchoMax = 1000) {
    $info = getimagesize($rutaOrigen);
    if (!$info) return false;

    list($anchoOriginal, $altoOriginal) = $info;
    $mime = $info['mime'];

    if ($anchoOriginal > $anchoMax) {
        $nuevoAncho = $anchoMax;
        $nuevoAlto  = floor(($altoOriginal / $anchoOriginal) * $anchoMax);
    } else {
        $nuevoAncho = $anchoOriginal;
        $nuevoAlto  = $altoOriginal;
    }

    $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        $imagen = imagecreatefromjpeg($rutaOrigen);
    } elseif ($mime === 'image/png') {
        $imagen = imagecreatefrompng($rutaOrigen);
        imagealphablending($lienzo, false);
        imagesavealpha($lienzo, true);
    } else {
        return false;
    }

    imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
    $resultado = imagejpeg($lienzo, $rutaDestino, $calidad);

    unset($imagen, $lienzo);

    return $resultado;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id        = $_POST['vehiculo_id'] ?? '';
    $tipo_mantenimiento = trim($_POST['tipo_mantenimiento'] ?? '');
    $tipo_servicio      = $_POST['tipo_servicio'] ?? 'preventivo';
    $fecha_servicio     = $_POST['fecha_servicio'] ?? date('Y-m-d');
    $kilometraje        = floatval($_POST['kilometraje'] ?? 0);
    $costo_total        = floatval($_POST['costo_total'] ?? 0);
    $taller_proveedor   = trim($_POST['taller_proveedor'] ?? '');
    $notas              = trim($_POST['notas'] ?? '');
    $usuario_id         = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
    $ticket_foto        = null;

    if (empty($vehiculo_id) || empty($tipo_mantenimiento) || empty($kilometraje) || empty($costo_total)) {
        $error = 'Por favor completa todos los campos obligatorios (*).';
    } else {
        try {
            // Procesar ticket/foto de taller si se subió
            if (isset($_FILES['ticket_foto']) && $_FILES['ticket_foto']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['ticket_foto']['size'] > 10 * 1024 * 1024) {
                    $error = 'El archivo original excede el límite permitido (10 MB).';
                } else {
                    $ext = strtolower(pathinfo($_FILES['ticket_foto']['name'], PATHINFO_EXTENSION));
                    $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

                    if (in_array($ext, $ext_permitidas)) {
                        $dir_destino = 'uploads/mantenimientos/';
                        if (!is_dir($dir_destino)) {
                            mkdir($dir_destino, 0777, true);
                        }

                        $nombre_archivo = 'mant_' . $vehiculo_id . '_' . time() . '.jpg';
                        $ruta_final = $dir_destino . $nombre_archivo;

                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            if (comprimirImagen($_FILES['ticket_foto']['tmp_name'], $ruta_final, 70, 1000)) {
                                $ticket_foto = $ruta_final;
                            } else {
                                $error = 'No se pudo optimizar la imagen del ticket.';
                            }
                        } else {
                            $ruta_doc = $dir_destino . 'mant_' . $vehiculo_id . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($_FILES['ticket_foto']['tmp_name'], $ruta_doc)) {
                                $ticket_foto = $ruta_doc;
                            }
                        }
                    } else {
                        $error = 'Formato no permitido. Usa JPG, PNG, WEBP o PDF.';
                    }
                }
            }

            if (!$error) {
                $db->beginTransaction();

                $sql = "INSERT INTO mantenimientos 
                        (vehiculo_id, usuario_id, tipo_mantenimiento, tipo_servicio, fecha_servicio, kilometraje, costo_total, taller_proveedor, ticket_foto, notas) 
                        VALUES 
                        (:vehiculo_id, :usuario_id, :tipo_mantenimiento, :tipo_servicio, :fecha_servicio, :kilometraje, :costo_total, :taller_proveedor, :ticket_foto, :notas)";

                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':vehiculo_id'        => $vehiculo_id,
                    ':usuario_id'         => $usuario_id,
                    ':tipo_mantenimiento' => $tipo_mantenimiento,
                    ':tipo_servicio'      => $tipo_servicio,
                    ':fecha_servicio'     => $fecha_servicio,
                    ':kilometraje'        => $kilometraje,
                    ':costo_total'        => $costo_total,
                    ':taller_proveedor'   => $taller_proveedor,
                    ':ticket_foto'        => $ticket_foto,
                    ':notas'              => $notas
                ]);

                // Actualizar kilometraje actual del vehículo si es mayor
                $sqlKm = "UPDATE vehiculos SET kilometraje_actual = GREATEST(kilometraje_actual, :km) WHERE id = :vehiculo_id";
                $stmtKm = $db->prepare($sqlKm);
                $stmtKm->execute([
                    ':km'          => $kilometraje,
                    ':vehiculo_id' => $vehiculo_id
                ]);

                // Cambiar el estado a 'mantenimiento' de forma automática
                $sqlEstado = "UPDATE vehiculos SET estado = 'mantenimiento' WHERE id = :vehiculo_id";
                $stmtEstado = $db->prepare($sqlEstado);
                $stmtEstado->execute([':vehiculo_id' => $vehiculo_id
                ]);

                $db->commit();

                header("Location: vehiculo_detalle.php?id=" . $vehiculo_id . "&msg=mantenimiento_ok");
                exit();
            }

        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Error al registrar el mantenimiento: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<main class="page-wrapper">
  <div style="margin-bottom: 1rem;">
    <?php if ($vehiculo_id_preset): ?>
      <a href="vehiculo_detalle.php?id=<?= $vehiculo_id_preset ?>" class="btn btn-outline btn-sm">← Volver al vehículo</a>
    <?php else: ?>
      <a href="index.php" class="btn btn-outline btn-sm">← Volver al catálogo</a>
    <?php endif; ?>
  </div>

  <div class="card" style="max-width: 650px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 1.2rem;">
      <h1>🛠️ Registrar Mantenimiento / Servicio</h1>
      <p>Guarda el historial de servicios, afinaciones o reparaciones realizadas a la unidad.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="mantenimiento_alta.php<?= $vehiculo_id_preset ? '?vehiculo_id='.$vehiculo_id_preset : '' ?>" enctype="multipart/form-data" class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
      
      <div class="form-group full">
        <label for="vehiculo_id">Vehículo *</label>
        <select id="vehiculo_id" name="vehiculo_id" required>
          <option value="">-- Selecciona un vehículo --</option>
          <?php foreach ($vehiculos as $v): ?>
            <option value="<?= $v['id'] ?>" <?= ($vehiculo_id_preset == $v['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' [' . $v['placas'] . ']') ?> — Actual: <?= number_format($v['kilometraje_actual']) ?> km
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="tipo_mantenimiento">Tipo de Mantenimiento *</label>
        <input type="text" id="tipo_mantenimiento" name="tipo_mantenimiento" placeholder="Ej. Cambio de Aceite / Frenos" required />
      </div>

      <div class="form-group">
        <label for="tipo_servicio">Categoría *</label>
        <select id="tipo_servicio" name="tipo_servicio" required>
          <option value="preventivo">Preventivo (Programado)</option>
          <option value="correctivo">Correctivo (Reparación / Falla)</option>
        </select>
      </div>

      <div class="form-group">
        <label for="fecha_servicio">Fecha del Servicio *</label>
        <input type="date" id="fecha_servicio" name="fecha_servicio" value="<?= date('Y-m-d') ?>" required />
      </div>

      <div class="form-group">
        <label for="kilometraje">Kilometraje Marcado *</label>
        <input type="number" id="kilometraje" name="kilometraje" placeholder="Ej. 15000" step="1" min="0" required />
      </div>

      <div class="form-group">
        <label for="costo_total">Costo Total ($ MXN) *</label>
        <input type="number" id="costo_total" name="costo_total" placeholder="Ej. 2500.00" step="0.01" min="1" required />
      </div>

      <div class="form-group">
        <label for="taller_proveedor">Taller / Proveedor</label>
        <input type="text" id="taller_proveedor" name="taller_proveedor" placeholder="Ej. Taller Mecánico San José" />
      </div>

      <div class="form-group full">
        <label for="ticket_foto">📷 Nota / Factura / Comprobante</label>
        <input type="file" id="ticket_foto" name="ticket_foto" accept="image/*,.pdf" />
      </div>

      <div class="form-group full">
        <label for="notas">Detalles u Observaciones</label>
        <textarea id="notas" name="notas" placeholder="Ej. Se cambiaron balatas delanteras y filtro de aire."></textarea>
      </div>

      <div class="form-group full btn-actions" style="margin-top: 1rem;">
        <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
          💾 Guardar Mantenimiento
        </button>
        <a href="<?= $vehiculo_id_preset ? 'vehiculo_detalle.php?id='.$vehiculo_id_preset : 'index.php' ?>" class="btn btn-outline">
          Cancelar
        </a>
      </div>

    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>