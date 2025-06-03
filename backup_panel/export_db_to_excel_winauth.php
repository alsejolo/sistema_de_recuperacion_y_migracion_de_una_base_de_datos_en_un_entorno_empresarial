<?php
require 'auth.php';

$backupFile = $_GET['file'] ?? '';
$password = $_GET['password'] ?? '';
$server = 'localhost';
$tempDb = 'temp_export_db_' . uniqid();

// Si se especificó un backup, restaurarlo en una base de datos temporal
if (!empty($backupFile) && file_exists($backupFile)) {
    $isEncrypted = (pathinfo($backupFile, PATHINFO_EXTENSION) === 'enc');
    
    if ($isEncrypted && empty($password)) {
        die('Error: Se requiere contraseña para desencriptar este backup.');
    }

    // Crear base de datos temporal
    $cmdCreateDb = "sqlcmd -S $server -E -Q \"CREATE DATABASE [$tempDb]\"";
    exec($cmdCreateDb, $output, $return_var);
    
    if ($return_var !== 0) {
        die("Error al crear base de datos temporal: " . implode("\n", $output));
    }
    
    // Si es encriptado, primero desencriptar
    if ($isEncrypted) {
        $encData = file_get_contents($backupFile);
        if ($encData === false) {
            dropTempDb($server, $tempDb);
            die("Error al leer archivo encriptado");
        }

        $iv = substr($encData, 0, 16);
        $encryptedContent = substr($encData, 16);
        $key = hash('sha256', $password, true);
        
        $decrypted = openssl_decrypt($encryptedContent, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            dropTempDb($server, $tempDb);
            die("Error al desencriptar: " . openssl_error_string());
        }

        $tempBackupFile = sys_get_temp_dir() . '/' . uniqid('decrypted_') . '.bak';
        if (file_put_contents($tempBackupFile, $decrypted) === false) {
            dropTempDb($server, $tempDb);
            die("Error al crear archivo temporal desencriptado");
        }
        
        $backupFileToRestore = $tempBackupFile;
    } else {
        $backupFileToRestore = $backupFile;
    }
    
    // Restaurar el backup
    $cmdRestore = "sqlcmd -S $server -E -Q \"RESTORE DATABASE [$tempDb] FROM DISK = N'$backupFileToRestore' WITH REPLACE, RECOVERY, MOVE 'ecommerce' TO 'C:\\temp\\$tempDb.mdf', MOVE 'ecommerce_log' TO 'C:\\temp\\$tempDb.ldf'\"";
    exec($cmdRestore, $output, $return_var);
    
    // Eliminar archivo temporal desencriptado si existe
    if (isset($tempBackupFile)) {
        @unlink($tempBackupFile);
    }
    
    if ($return_var !== 0) {
        dropTempDb($server, $tempDb);
        die("Error al restaurar backup: " . implode("\n", $output));
    }
    
    $databaseName = $tempDb;
} else {
    $databaseName = 'ecommerce';
}

// Conexión con Windows Authentication
$connectionInfo = [
    'Database' => $databaseName,
    'TrustServerCertificate' => true, // Opcional, para evitar problemas con certificados
];
$conn = sqlsrv_connect($server, $connectionInfo);

if (!$conn) {
    if (isset($tempDb)) {
        dropTempDb($server, $tempDb);
    }
    die("<h2>Error de conexión:</h2><pre>".print_r(sqlsrv_errors(), true)."</pre>");
}

// Cabeceras para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="export_datos_'.date('Ymd_His').'.xls"');

// HTML con formato Excel
echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' 
      xmlns:x='urn:schemas-microsoft-com:office:excel' 
      xmlns='http://www.w3.org/TR/REC-html40'>
      <head>
          <meta charset='UTF-8'>
          <!--[if gte mso 9]>
          <xml>
              <x:ExcelWorkbook>
                  <x:ExcelWorksheets>
                      <x:ExcelWorksheet>
                          <x:Name>Datos Exportados</x:Name>
                          <x:WorksheetOptions>
                              <x:DisplayGridlines/>
                          </x:WorksheetOptions>
                      </x:ExcelWorksheet>
                  </x:ExcelWorksheets>
              </x:ExcelWorkbook>
          </xml>
          <![endif]-->
      </head>
      <body>";

// Obtener tablas
$tablesQuery = "SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_TYPE = 'BASE TABLE'
                AND TABLE_NAME NOT LIKE 'sys%'
                ORDER BY TABLE_NAME";
$tablesResult = sqlsrv_query($conn, $tablesQuery);

if (!$tablesResult) {
    if (isset($tempDb)) {
        dropTempDb($server, $tempDb);
    }
    die("<h2>Error al obtener tablas:</h2><pre>".print_r(sqlsrv_errors(), true)."</pre>");
}

while ($tableRow = sqlsrv_fetch_array($tablesResult, SQLSRV_FETCH_ASSOC)) {
    $tableName = $tableRow['TABLE_NAME'];
    
    echo "<h2>Tabla: $tableName</h2>";
    echo "<table border='1' cellspacing='0' cellpadding='3'>";
    
    // Obtener datos con límite de 1000 registros para evitar problemas de memoria
    $dataQuery = "SELECT TOP 1000 * FROM [$tableName]";
    $dataResult = sqlsrv_query($conn, $dataQuery);
    
    if (!$dataResult) {
        echo "<tr><td colspan='10'>Error al obtener datos de $tableName</td></tr>";
        continue;
    }
    
    // Encabezados
    echo "<tr style='background-color:#4472C4;color:white;font-weight:bold;'>";
    $fields = sqlsrv_field_metadata($dataResult);
    foreach ($fields as $field) {
        echo "<td>".$field['Name']."</td>";
    }
    echo "</tr>";
    
    // Datos
    while ($row = sqlsrv_fetch_array($dataResult, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_resource($value)) {
                $value = '[BLOB]';
            } elseif ($value === null) {
                $value = 'NULL';
            }
            echo "<td style='border:1px solid #ddd;'>".htmlspecialchars($value ?? '')."</td>";
        }
        echo "</tr>";
    }
    
    echo "</table><br>";
}

echo "</body></html>";

// Cerrar conexión y limpiar
sqlsrv_free_stmt($tablesResult);
sqlsrv_close($conn);

if (isset($tempDb)) {
    dropTempDb($server, $tempDb);
}

function dropTempDb($server, $dbName) {
    $cmdDropDb = "sqlcmd -S $server -E -Q \"DROP DATABASE [$dbName]\"";
    exec($cmdDropDb);
}
?>