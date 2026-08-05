<?php
// =============================================
//  reporte_mantenimiento_excel.php — Exportación a Excel Visual
// =============================================

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();

// Filtros opcionales desde la URL
$vehiculo_id  = $_GET['vehiculo_id'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin    = $_GET['fecha_fin'] ?? null;

// Obtener datos del vehículo si se filtró por uno específico
$auto_info = "Todos los vehículos";
if (!empty($vehiculo_id)) {
    $stmtV = $db->prepare("SELECT marca, modelo, placas FROM vehiculos WHERE id = :id LIMIT 1");
    $stmtV->execute([':id' => $vehiculo_id]);
    $auto = $stmtV->fetch();
    if ($auto) {
        $auto_info = "Vehículo: " . $auto['marca'] . " " . $auto['modelo'] . " | Placas: " . $auto['placas'];
    }
}

// Construcción de la consulta con filtros dinámicos
$sql = "SELECT 
            m.id,
            v.marca,
            v.modelo,
            v.placas,
            m.tipo_mantenimiento,
            m.tipo_servicio,
            m.fecha_servicio,
            m.kilometraje,
            m.costo_total,
            m.taller_proveedor,
            m.notas
        FROM mantenimientos m
        INNER JOIN vehiculos v ON m.vehiculo_id = v.id
        WHERE 1=1";

$params = [];

if (!empty($vehiculo_id)) {
    $sql .= " AND m.vehiculo_id = :vehiculo_id";
    $params[':vehiculo_id'] = $vehiculo_id;
}

if (!empty($fecha_inicio)) {
    $sql .= " AND m.fecha_servicio >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}

if (!empty($fecha_fin)) {
    $sql .= " AND m.fecha_servicio <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

$sql .= " ORDER BY m.fecha_servicio DESC, m.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Limpieza de búfer de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Configuración de Headers
$filename = "reporte_mantenimientos_" . date('Y-m-d_H-i') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8 para acentos y caracteres especiales
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Mantenimientos</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Calibri, Arial, sans-serif; }
        .title { font-size: 16pt; font-weight: bold; color: #1f4e78; }
        .sub-info { font-size: 11pt; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #ffffff; color: #000000; font-weight: bold; border: 1px solid #d9d9d9; padding: 6px; text-align: left; }
        td { border: 1px solid #d9d9d9; padding: 6px; text-align: left; font-size: 10pt; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td colspan="11" class="title">Reporte de Mantenimientos</td>
        </tr>
        <tr><td colspan="11"></td></tr>
        <tr>
            <td colspan="11" class="sub-info"><b><?= htmlspecialchars($auto_info) ?></b></td>
        </tr>
        <tr><td colspan="11"></td></tr>
        <tr>
            <td colspan="11" class="sub-info"><b>Fecha de generación:</b> <?= date('Y-m-d H:i:s') ?></td>
        </tr>
        <tr><td colspan="11"></td></tr>
        <thead>
            <tr>
                <th># ID</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Placas</th>
                <th>Mantenimiento / Servicio</th>
                <th>Categoría</th>
                <th>Fecha de Servicio</th>
                <th>Kilometraje</th>
                <th>Costo Total ($)</th>
                <th>Taller / Proveedor</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="11" style="text-align: center;">No hay registros de mantenimiento para este filtro.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['marca']) ?></td>
                        <td><?= htmlspecialchars($row['modelo']) ?></td>
                        <td><?= htmlspecialchars($row['placas']) ?></td>
                        <td><?= htmlspecialchars($row['tipo_mantenimiento']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($row['tipo_servicio'])) ?></td>
                        <td><?= $row['fecha_servicio'] ?></td>
                        <td><?= number_format((int)$row['kilometraje']) ?> km</td>
                        <td><b>$<?= number_format((float)$row['costo_total'], 2) ?></b></td>
                        <td><?= htmlspecialchars($row['taller_proveedor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['notas'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
<?php
exit();
