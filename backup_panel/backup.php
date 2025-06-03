<?php
require_once 'auth.php';
require_once 'mail_notification.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$backupFolder = 'D:\\laragon\\backup_logs\\';
$databaseName = 'ecommerce';
$server = 'localhost';

$encrypted = isset($_POST['encrypted']) && $_POST['encrypted'] == '1';
$password = $_POST['password'] ?? null;

date_default_timezone_set('America/El_Salvador');
$timestamp = date('Ymd_His');

if ($encrypted && empty($password)) {
    die('Se requiere contraseña para crear backup encriptado.');
}

$backupFile = $backupFolder . "backup_{$timestamp}.bak";

// Comando para crear backup normal
$cmdBackup = "sqlcmd -S $server -E -Q \"BACKUP DATABASE [$databaseName] TO DISK = N'$backupFile' WITH INIT, COMPRESSION\"";
exec($cmdBackup, $output, $return_var);

if ($return_var !== 0) {
    die("Error al crear el backup normal. Detalles: " . implode("\n", $output));
}

if ($encrypted) {
    // Leer contenido del backup normal
    $data = file_get_contents($backupFile);
    if ($data === false) {
        unlink($backupFile);
        die("Error al leer archivo de backup para encriptar.");
    }

    // Validar que el backup no esté vacío
    if (strlen($data) < 100) {
        unlink($backupFile);
        die("El archivo de backup está vacío o corrupto.");
    }

    // Encriptar con AES-256-CBC
    $key = hash('sha256', $password, true);
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encryptedData === false) {
        unlink($backupFile);
        die("Error al encriptar el backup: " . openssl_error_string());
    }

    $encFile = $backupFile . '.enc';
    $result = file_put_contents($encFile, $iv . $encryptedData);
    
    if ($result === false) {
        unlink($backupFile);
        die("Error al guardar el backup encriptado. Verifica permisos de escritura.");
    }

    // Verificar que el archivo encriptado tenga un tamaño razonable
    if (filesize($encFile) < 100) {
        unlink($backupFile);
        unlink($encFile);
        die("El archivo encriptado resultante es demasiado pequeño.");
    }

    // Borrar backup normal para que solo quede el encriptado
    unlink($backupFile);
    $backupFile = $encFile;
}

// Enviar notificación por correo
// Enviar notificación por correo
$backupType = $encrypted ? 'encriptado' : 'normal';
$backupSize = round(filesize($backupFile) / (1024 * 1024), 2) . ' MB';
$subject = "Backup creado - " . date('d/m/Y H:i:s');
$message = "Se ha creado un nuevo backup $backupType de la base de datos $databaseName.\n";
$message .= "Archivo: " . basename($backupFile) . "\n";
$message .= "Tamaño: $backupSize\n";
$message .= "Usuario: " . $_SESSION['username'] . "\n";
$message .= "Fecha: " . date('Y-m-d H:i:s') . "\n";

if (!sendBackupNotification($subject, $message)) {
    header('Location: index.php?mail_error=1');
    exit;
}

header('Location: index.php');
exit;