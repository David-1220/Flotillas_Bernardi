<?php
// =============================================
//  gasolina_alta.php — Carga con Compresión de Ticket
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

// ===================================================
// AQUÍ VA LA FUNCIÓN DE COMPRESIÓN (Área de Funciones)
// ===================================================
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

    // Liberar memoria de forma segura
    unset($imagen, $lienzo);
    return $resultado;

}

// ===================================================
// PROCESAMIENTO DEL FORMULARIO (POST)
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = $_POST['vehiculo_id'] ?? '';
    $fecha_carga = $_POST['fecha_carga'] ?? date('Y-m-d');
    $kilometraje = floatval($_POST['kilometraje'] ?? 0);
    $litros      = floatval($_POST['litros'] ?? 0);
    $costo_total = floatval($_POST['costo_total'] ?? 0);
    $gasolinera  = trim($_POST['gasolinera'] ?? '');
    $notas       = trim($_POST['notas'] ?? '');
    $usuario_id  = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
    $ticket_ruta = null;

    if (empty($vehiculo_id) || empty($kilometraje) || empty($litros) || empty($costo_total)) {
        $error = 'Por favor completa todos los campos obligatorios (*).';
    } else {
        try {
            // -------------------------------------------------------------
            // VALIDACIÓN: KILOMETRAJE INCREMENTAL
            // -------------------------------------------------------------
            $stmtKmCheck = $db->prepare("SELECT kilometraje_actual FROM vehiculos WHERE id = :id");
            $stmtKmCheck->execute([':id' => $vehiculo_id]);
            $autoActual = $stmtKmCheck->fetch();

            $km_registrado = $autoActual ? floatval($autoActual['kilometraje_actual']) : 0;

            if ($km_registrado > 0 && $kilometraje <= $km_registrado) {
                $error = 'El kilometraje ingresado (' . number_format($kilometraje) . ' km) no puede ser menor o igual al último registrado (' . number_format($km_registrado) . ' km).';
            } else {
                // -------------------------------------------------------------
                // PROCESAR FOTO DEL TICKET CON OPTIMIZACIÓN
                // -------------------------------------------------------------
                if (isset($_FILES['ticket_foto']) && $_FILES['ticket_foto']['error'] === UPLOAD_ERR_OK) {
                    
                    if ($_FILES['ticket_foto']['size'] > 10 * 1024 * 1024) {
                        $error = 'El archivo original excede el límite máximo permitido (10 MB).';
                    } else {
                        $ext = strtolower(pathinfo($_FILES['ticket_foto']['name'], PATHINFO_EXTENSION));
                        $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

                        if (in_array($ext, $ext_permitidas)) {
                            $dir_destino = 'uploads/tickets/';
                            if (!is_dir($dir_destino)) {
                                mkdir($dir_destino, 0777, true);
                            }

                            $nombre_archivo = 'ticket_' . $vehiculo_id . '_' . time() . '.jpg';
                            $ruta_final = $dir_destino . $nombre_archivo;

                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                // AQUÍ SE EJECUTA LA FUNCIÓN COMPRIMIR
                                if (comprimirImagen($_FILES['ticket_foto']['tmp_name'], $ruta_final, 70, 1000)) {
                                    $ticket_ruta = $ruta_final;
                                } else {
                                    $error = 'No se pudo procesar y comprimir la imagen.';
                                }
                            } else {
                                // PDFs u otros formatos se mueven directo
                                $ruta_doc = $dir_destino . 'ticket_' . $vehiculo_id . '_' . time() . '.' . $ext;
                                if (move_uploaded_file($_FILES['ticket_foto']['tmp_name'], $ruta_doc)) {
                                    $ticket_ruta = $ruta_doc;
                                }
                            }
                        } else {
                            $error = 'Formato de archivo no permitido. Usa JPG, PNG, WEBP o PDF.';
                        }
                    }
                }

                if (!$error) {
                    $db->beginTransaction();

                    $sqlGas = "INSERT INTO cargas_gasolina 
                                (vehiculo_id, usuario_id, fecha_carga, kilometraje, litros, costo_total, gasolinera, notas, ticket_foto) 
                               VALUES
                                 (:vehiculo_id, :usuario_id, :fecha_carga, :kilometraje, :litros, :costo_total, :gasolinera, :notas, :ticket_foto)";    
                                
                    $stmtGas = $db->prepare($sqlGas);
                    $stmtGas->execute([
                        ':vehiculo_id' => $vehiculo_id,
                        ':usuario_id'  => $usuario_id,
                        ':fecha_carga' => $fecha_carga,
                        ':kilometraje' => $kilometraje,
                        ':litros'      => $litros,
                        ':costo_total' => $costo_total,
                        ':gasolinera'  => $gasolinera,
                        ':notas'       => $notas,
                        ':ticket_foto' => $ticket_ruta
                    ]);

                    // Actualizar el kilometraje del auto si el nuevo es mayor
                    $sqlKm = "UPDATE vehiculos 
                            SET kilometraje_actual = GREATEST(kilometraje_actual, :km) 
                            WHERE id = :vehiculo_id";

                    $stmtKm = $db->prepare($sqlKm);
                    $stmtKm->execute([
                        ':km'          => $kilometraje,
                        ':vehiculo_id' => $vehiculo_id
                    ]);

                    $db->commit();

                    header("Location: vehiculo_detalle.php?id=" . $vehiculo_id . "&msg=gasolina_ok");
                    exit();
                }
            }

        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Error al registrar la carga: ' . $e->getMessage();
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
      <h1>⛽ Registrar Carga de Combustible</h1>
      <p>Ingresa los datos del ticket de gasolina para llevar el control del gasto y kilometraje.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="gasolina_alta.php<?= $vehiculo_id_preset ? '?vehiculo_id='.$vehiculo_id_preset : '' ?>" enctype="multipart/form-data" class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
      
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
        <label for="fecha_carga">Fecha *</label>
        <input type="date" id="fecha_carga" name="fecha_carga" value="<?= date('Y-m-d') ?>" required />
      </div>

      <div class="form-group">
        <label for="kilometraje">Kilometraje Marcado *</label>
        <input type="number" id="kilometraje" name="kilometraje" placeholder="Ej. 12500" step="1" min="0" required />
      </div>

      <div class="form-group">
        <label for="litros">Litros Cargados *</label>
        <input type="number" id="litros" name="litros" placeholder="Ej. 45.50" step="0.01" min="0.1" required />
      </div>

      <div class="form-group">
        <label for="costo_total">Costo Total ($ MXN) *</label>
        <input type="number" id="costo_total" name="costo_total" placeholder="Ej. 1150.00" step="0.01" min="1" required />
      </div>

      <div class="form-group full">
        <label for="gasolinera">Gasolinera / Proveedor</label>
        <input type="text" id="gasolinera" name="gasolinera" placeholder="Ej. OXXO GAS / BP Bernardo Quintana" />
      </div>

      <div class="form-group full">
        <label for="ticket_foto">📷 Comprobante / Foto del Ticket</label>
        <input type="file" id="ticket_foto" name="ticket_foto" accept="image/*,.pdf" />
        <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">
          ⚡ La imagen se comprimirá automáticamente a ~150 KB para optimizar el almacenamiento.
        </small>
      </div>

      <div class="form-group full">
        <label for="notas">Notas u Observaciones</label>
        <textarea id="notas" name="notas" placeholder="Ej. Se cargó tanque lleno / Ticket #12345"></textarea>
      </div>

      <div class="form-group full btn-actions" style="margin-top: 1rem;">
        <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
          💾 Guardar Carga
        </button>
        <a href="<?= $vehiculo_id_preset ? 'vehiculo_detalle.php?id='.$vehiculo_id_preset : 'index.php' ?>" class="btn btn-outline">
          Cancelar
        </a>
      </div>

    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>