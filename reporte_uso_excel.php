<?php
// ========================================================
//   reporte_uso_excel.php — Generador de Reporte de Bitácora
// ========================================================

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

// Configurar encabezados para descarga de archivo Excel HTML
$filename = "Bitacora_Uso_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $auto['placas']) . "_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th { background-color: #1a252f; color: #ffffff; padding: 10px; border: 1px solid #000; text-align: left; }
        td { padding: 8px; border: 1px solid #ccc; text-align: left; vertical-align: middle; }
        .header-title { font-size: 16pt; font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body>

    <p class="header-title">Reporte de Bitácora de Uso de Unidad</p>
    <p><b>Vehículo:</b> <?= htmlspecialchars($auto['marca'] . ' ' . $auto['modelo']) ?> | <b>Placas:</b> <?= htmlspecialchars($auto['placas']) ?></p>
    <p><b>Fecha de generación:</b> <?= date('Y-m-d H:i:s') ?></p>
    <br>

    <table>
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
                        <td><?= htmlspecialchars($r['motivo_salida']) ?></td>
                        <td><?= $r['fecha_entrada'] ?? 'En tránsito' ?></td>
                        <td><?= $km_ent > 0 ? number_format($km_ent) . ' km' : '-' ?></td>
                        <td><b><?= $km_recorridos > 0 ? number_format($km_recorridos) . ' km' : '0 km' ?></b></td>
                        <td><?= htmlspecialchars($r['observaciones_entrada'] ?? '-') ?></td>
                        
                        <!-- Aplica el estilo y fondo directo en el <td> para compatibilidad con Excel -->
                        <?php if ($r['estado'] === 'en_transito'): ?>
                            <td bgcolor="#F39C12" style="background-color: #F39C12; color: #000000; font-weight: bold; text-align: center;">
                                EN TRÁNSITO
                            </td>
                        <?php else: ?>
                            <td bgcolor="#2ECC71" style="background-color: #2ECC71; color: #000000; font-weight: bold; text-align: center;">
                                FINALIZADO
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>