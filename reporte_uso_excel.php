<?php
// ========================================================
//   reporte_uso_excel.php — Generador de Reporte Visual
// ========================================================

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$vehiculo_id = $_GET['vehiculo_id'] ?? null;
if (!$vehiculo_id) { die("ID de vehículo no especificado."); }

$db = getDB();

// 1. Obtener datos del vehículo
$stmtV = $db->prepare("SELECT marca, modelo, placas FROM vehiculos WHERE id = :id LIMIT 1");
$stmtV->execute([':id' => $vehiculo_id]);
$auto = $stmtV->fetch();

if (!$auto) { die("Vehículo no encontrado."); }

// 2. Obtener la bitácora de uso con datos del usuario
$stmt = $db->prepare("
    SELECT b.*, u.nombre AS usuario_nombre 
    FROM bitacora_uso b 
    LEFT JOIN usuarios u ON b.usuario_id = u.id 
    WHERE b.vehiculo_id = :id 
    ORDER BY b.fecha_salida DESC
");
$stmt->execute([':id' => $vehiculo_id]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. LIMPIAR BÚFER (Crucial para eliminar espacios de require_once)
if (ob_get_level()) {
    ob_end_clean();
}

// 4. Configurar Encabezados
$filename = "Bitacora_Uso_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $auto['placas']) . "_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Imprimir el BOM UTF-8 para que Excel detecte tildes y ñs sin problemas
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
                    <x:Name>Bitácora de Uso</x:Name>
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
        .status-transito { background-color: #f39c12; color: #ffffff; font-weight: bold; text-align: center; }
        .status-fin { background-color: #2ecc71; color: #ffffff; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td colspan="10" class="title">Reporte de Bitácora de Uso de Unidad</td>
        </tr>
        <tr><td colspan="10"></td></tr>
        <tr>
            <td colspan="10" class="sub-info"><b>Vehículo:</b> <?= htmlspecialchars($auto['marca'] . ' ' . $auto['modelo']) ?> | <b>Placas:</b> <?= htmlspecialchars($auto['placas']) ?></td>
        </tr>
        <tr><td colspan="10"></td></tr>
        <tr>
            <td colspan="10" class="sub-info"><b>Fecha de generación:</b> <?= date('Y-m-d H:i:s') ?></td>
        </tr>
        <tr><td colspan="10"></td></tr>
        <thead>
            <tr>
                <th># ID</th>
                <th>Usuario</th>
                <th>Fecha Salida</th>
                <th>KM Salida</th>
                <th>Motivo / Destino</th>
                <th>Fecha Entrada</th>
                <th>KM Entrada</th>
                <th>KM Recorridos</th>
                <th>Observaciones Entrada</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="10" style="text-align: center;">No hay registros de uso para este vehículo.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $r): ?>
                    <?php 
                        $km_sal = (int)($r['km_salida'] ?? 0);
                        $km_ent = (int)($r['km_entrada'] ?? 0);
                        $km_recorridos = ($km_ent > 0 && $km_ent >= $km_sal) ? ($km_ent - $km_sal) : 0;
                    ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['usuario_nombre'] ?? 'N/A') ?></td>
                        <td><?= $r['fecha_salida'] ?></td>
                        <td><?= number_format($km_sal) ?> km</td>
                        <td><?= htmlspecialchars($r['motivo_salida'] ?? '') ?></td>
                        <td><?= $r['fecha_entrada'] ?? 'En tránsito' ?></td>
                        <td><?= $km_ent > 0 ? number_format($km_ent) . ' km' : '-' ?></td>
                        <td><b><?= $km_recorridos > 0 ? number_format($km_recorridos) . ' km' : '0 km' ?></b></td>
                        <td><?= htmlspecialchars($r['observaciones_entrada'] ?? '-') ?></td>

                        <?php if ($r['estado'] === 'en_transito'): ?>
                            <td class="status-transito">EN TRÁNSITO</td>
                        <?php else: ?>
                            <td class="status-fin">FINALIZADO</td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
<?php
exit();
