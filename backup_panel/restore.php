<?php
// Activar logging completo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Crear archivo de log
$logFile = __DIR__ . '/restore_debug.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Inicio de restauración\n", FILE_APPEND);

function logDebug($message) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

try {
    logDebug("Recibida solicitud: " . print_r($_POST, true));
    
    $backupFile = $_POST['backupFile'] ?? null;
    $encrypted = isset($_POST['encrypted']) && $_POST['encrypted'] == '1';
    $password = $_POST['password'] ?? null;

    // Validación básica
    if (empty($backupFile)) {
        throw new Exception("No se especificó archivo de backup");
    }

    logDebug("Procesando archivo: $backupFile");
    logDebug("Tamaño del archivo: " . filesize($backupFile) . " bytes");

    if (!file_exists($backupFile)) {
        throw new Exception("El archivo $backupFile no existe");
    }

    $databaseName = 'ecommerce';
    $server = 'localhost';
    $tempFile = null;

    if ($encrypted) {
        logDebug("Procesando backup ENCRIPTADO");
        
        if (empty($password)) {
            throw new Exception("Contraseña no proporcionada para backup encriptado");
        }

        // Leer archivo encriptado
        $encData = file_get_contents($backupFile);
        if ($encData === false) {
            throw new Exception("Error al leer archivo encriptado");
        }

        logDebug("Longitud datos encriptados: " . strlen($encData) . " bytes");

        // Extraer IV (primeros 16 bytes)
        $iv = substr($encData, 0, 16);
        logDebug("IV extraído: " . bin2hex($iv));

        // Extraer datos encriptados
        $encryptedContent = substr($encData, 16);
        $key = hash('sha256', $password, true);
        
        logDebug("Clave derivada: " . bin2hex($key));

        // Desencriptar
        $decrypted = openssl_decrypt($encryptedContent, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new Exception("Error al desencriptar: " . openssl_error_string());
        }

        logDebug("Datos desencriptados: " . strlen($decrypted) . " bytes");

        // Crear archivo temporal
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'decrypted_') . '.bak';
        
        logDebug("Creando archivo temporal en: $tempFile");
        
        $bytesWritten = file_put_contents($tempFile, $decrypted);
        
        if ($bytesWritten === false) {
            throw new Exception("Error al escribir archivo temporal");
        }

        logDebug("Escritos $bytesWritten bytes en archivo temporal");
        logDebug("Tamaño archivo temporal: " . filesize($tempFile) . " bytes");

        $backupFileToRestore = $tempFile;
    } else {
        logDebug("Procesando backup NORMAL");
        $backupFileToRestore = $backupFile;
    }

    // Verificar integridad del archivo
    $fileSize = filesize($backupFileToRestore);
    logDebug("Tamaño archivo a restaurar: $fileSize bytes");
    
    if ($fileSize < 1024) {
        throw new Exception("Archivo de backup demasiado pequeño (posible error)");
    }

    // Comando de restauración
    $cmdRestore = "sqlcmd -S $server -E -Q \"RESTORE DATABASE [$databaseName] FROM DISK = N'$backupFileToRestore' WITH REPLACE, RECOVERY\"";
    
    logDebug("Ejecutando comando: $cmdRestore");
    
    exec($cmdRestore, $output, $return_var);
    
    logDebug("Resultado del comando (Código $return_var): " . print_r($output, true));

    if ($return_var !== 0) {
        throw new Exception("Error en restauración (Código $return_var): " . implode("\n", $output));
    }

    // Limpiar archivo temporal si existe
    if ($tempFile && file_exists($tempFile)) {
        unlink($tempFile);
        logDebug("Archivo temporal eliminado");
    }

    logDebug("Restauración completada con éxito");
    header('Location: index.php?restore_success=1');
    exit;

} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage());
    
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
        logDebug("Archivo temporal eliminado por error");
    }
    
    header('Location: index.php?restore_error=' . urlencode($e->getMessage()));
    exit;
}