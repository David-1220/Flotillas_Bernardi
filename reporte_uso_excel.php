<?php
// =============================================
//  reporte_uso_excel.php — Exportación a Excel (CSV)
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();

$vehiculo_id = $_GET['vehiculo_id'] ?? null;
if (!$vehiculo_id) { die("ID de vehículo no especificado."); }

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

// Configuración de Headers exactamente igual a Mantenimiento y Gasolina (.csv)
$filename = "Bitacora_Uso_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $auto['placas']) . "_" . date('Y-m-d_H-i') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Crear el puntero de salida de datos
$output = fopen('php://output', 'w');

// BOM para que Excel reconozca acentos e idioma español correctamente (UTF-8)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados de las columnas en Excel
fputcsv($output, [
    'ID Registro',
    'Usuario',
    'Fecha Salida',
    'KM Salida',
    'Motivo / Destino',
    'Fecha Entrada',
    'KM Entrada',
    'KM Recorridos',
    'Observaciones Entrada',
    'Estado'
]);

// Llenar filas de datos
foreach ($registros as $r) {
    $km_sal = (int)($r['km_salida'] ?? 0);
    $km_ent = (int)($r['km_entrada'] ?? 0);
    $km_recorridos = ($km_ent > 0 && $km_ent >= $km_sal) ? ($km_ent - $km_sal) : 0;
    $estado_texto = ($r['estado'] === 'en_transito') ? 'EN TRÁNSITO' : 'FINALIZADO';

    fputcsv($output, [
        $r['id'],
        $r['usuario_nombre'] ?? 'N/A',
        $r['fecha_salida'],
        $km_sal,
        $r['motivo_salida'] ?? '',
        $r['fecha_entrada'] ?? 'En tránsito',
        $km_ent > 0 ? $km_ent : '-',
        $km_recorridos,
        $r['observaciones_entrada'] ?? '-',
        $estado_texto
    ]);
}

fclose($output);
exit();
