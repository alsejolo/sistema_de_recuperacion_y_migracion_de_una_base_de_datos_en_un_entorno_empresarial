<?php
require_once 'auth.php';

$backupFile = $_GET['file'] ?? '';
$databaseName = 'ecommerce';
$server = 'localhost';

if (empty($backupFile) || !file_exists($backupFile)) {
    die('Archivo de backup no válido');
}

// Comando para listar tablas del backup
$cmdListTables = "sqlcmd -S $server -E -Q \"RESTORE FILELISTONLY FROM DISK = N'$backupFile'\"";
exec($cmdListTables, $output, $return_var);

if ($return_var !== 0) {
    die("Error al leer el backup: " . implode("\n", $output));
}

// Procesar la salida para obtener información de las tablas
$tablesInfo = [];
foreach ($output as $line) {
    if (strpos($line, 'LogicalName') !== false) continue; // Saltar encabezados
    $parts = preg_split('/\s+/', trim($line));
    if (count($parts) >= 3) {
        $tablesInfo[] = [
            'name' => $parts[0],
            'type' => $parts[1]
        ];
    }
}

// Mostrar interfaz
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsualizar Backup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Previsualizar Backup: <?= htmlspecialchars(basename($backupFile)) ?></h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Nota:</strong> Esta vista previa muestra la estructura del backup. Para exportar datos completos, use el botón Exportar.
                </div>
                
                <div class="mb-4">
                    <a href="export_backup_to_excel.php?file=<?= urlencode($backupFile) ?>" class="btn btn-success">
                        <i class="bi bi-file-excel"></i> Exportar a Excel
                    </a>
                    <a href="select_backup.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <h5>Tablas en el backup:</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre Lógico</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tablesInfo as $table): ?>
                                <tr>
                                    <td><?= htmlspecialchars($table['name']) ?></td>
                                    <td><?= htmlspecialchars($table['type']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info preview-btn" data-table="<?= htmlspecialchars($table['name']) ?>">
                                            <i class="bi bi-eye"></i> Ver datos
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para previsualización de datos -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Datos de la tabla: <span id="modalTableName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="tablePreviewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.preview-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tableName = this.getAttribute('data-table');
                document.getElementById('modalTableName').textContent = tableName;
                
                // Mostrar loading
                document.getElementById('tablePreviewContent').innerHTML = 
                    '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
                
                // Cargar datos via AJAX
                fetch(`get_table_preview.php?file=<?= urlencode($backupFile) ?>&table=${encodeURIComponent(tableName)}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('tablePreviewContent').innerHTML = data;
                    })
                    .catch(error => {
                        document.getElementById('tablePreviewContent').innerHTML = 
                            '<div class="alert alert-danger">Error al cargar los datos</div>';
                    });
                
                const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                modal.show();
            });
        });
    </script>
</body>
</html>