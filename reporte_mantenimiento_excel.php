<?php
// =============================================
//  reporte_mantenimiento_excel.php — Exportación a Excel (CSV)
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();

// Filtros opcionales desde la URL
$vehiculo_id  = $_GET['vehiculo_id'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin    = $_GET['fecha_fin'] ?? null;

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

// Headers para descarga directa en Excel
$filename = "reporte_mantenimientos_" . date('Y-m-d_H-i') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM UTF-8 para compatibilidad de acentos en Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados
fputcsv($output, [
    'ID Registro',
    'Marca',
    'Modelo',
    'Placas',
    'Mantenimiento / Servicio',
    'Categoría',
    'Fecha de Servicio',
    'Kilometraje',
    'Costo Total ($)',
    'Taller / Proveedor',
    'Notas'
]);

// Filas de datos
foreach ($registros as $row) {
    fputcsv($output, [
        $row['id'],
        $row['marca'],
        $row['modelo'],
        $row['placas'],
        $row['tipo_mantenimiento'],
        ucfirst($row['tipo_servicio']),
        $row['fecha_servicio'],
        $row['kilometraje'],
        $row['costo_total'],
        $row['taller_proveedor'],
        $row['notas']
    ]);
}

fclose($output);
exit();