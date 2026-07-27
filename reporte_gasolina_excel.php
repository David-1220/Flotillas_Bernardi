<?php
// =============================================
//  reporte_gasolina_excel.php — Exportación a Excel (CSV)
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
            cg.id,
            v.marca,
            v.modelo,
            v.placas,
            cg.fecha_carga,
            cg.kilometraje,
            cg.litros,
            cg.costo_total,
            cg.gasolinera,
            cg.notas
        FROM cargas_gasolina cg
        INNER JOIN vehiculos v ON cg.vehiculo_id = v.id
        WHERE 1=1";

$params = [];

if (!empty($vehiculo_id)) {
    $sql .= " AND cg.vehiculo_id = :vehiculo_id";
    $params[':vehiculo_id'] = $vehiculo_id;
}

if (!empty($fecha_inicio)) {
    $sql .= " AND cg.fecha_carga >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}

if (!empty($fecha_fin)) {
    $sql .= " AND cg.fecha_carga <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

$sql .= " ORDER BY cg.fecha_carga DESC, cg.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuración de Headers para forzar descarga como Excel (.csv)
$filename = "reporte_gasolina_" . date('Y-m-d_H-i') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Crear el puntero de salida de datos
$output = fopen('php://output', 'w');

// BOM para que Excel reconozca acentos e idioma español correctamente (UTF-8)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados de las columnas en Excel
fputcsv($output, [
    'ID Registro',
    'Marca',
    'Modelo',
    'Placas',
    'Fecha de Carga',
    'Kilometraje',
    'Litros Cargados',
    'Costo Total ($)',
    'Gasolinera',
    'Notas'
]);

// Llenar filas de datos
foreach ($registros as $row) {
    fputcsv($output, [
        $row['id'],
        $row['marca'],
        $row['modelo'],
        $row['placas'],
        $row['fecha_carga'],
        $row['kilometraje'],
        $row['litros'],
        $row['costo_total'],
        $row['gasolinera'],
        $row['notas']
    ]);
}

fclose($output);
exit();