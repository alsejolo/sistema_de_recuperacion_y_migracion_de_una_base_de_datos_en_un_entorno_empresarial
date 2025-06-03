<?php
// test_decrypt.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$backupFile = 'D:\\laragon\\backup_logs\\backup_20230815_123456.bak.enc'; // Reemplaza con tu archivo
$password = 'tu_contraseña'; // Reemplaza con la contraseña usada

echo "Probando desencriptación de: $backupFile\n";

// Leer archivo encriptado
$encData = file_get_contents($backupFile);
if ($encData === false) {
    die("Error al leer archivo");
}

echo "Tamaño archivo: " . strlen($encData) . " bytes\n";

// Extraer IV (primeros 16 bytes)
$iv = substr($encData, 0, 16);
echo "IV: " . bin2hex($iv) . "\n";

// Extraer datos encriptados
$encryptedContent = substr($encData, 16);
$key = hash('sha256', $password, true);

echo "Clave derivada: " . bin2hex($key) . "\n";

// Desencriptar
$decrypted = openssl_decrypt($encryptedContent, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted === false) {
    die("Error al desencriptar: " . openssl_error_string());
}

echo "Datos desencriptados: " . strlen($decrypted) . " bytes\n";

// Guardar resultado
$outputFile = 'test_decrypted.bak';
file_put_contents($outputFile, $decrypted);

echo "Archivo desencriptado guardado en: $outputFile\n";
echo "Intenta restaurar manualmente con:\n";
echo "sqlcmd -S localhost -E -Q \"RESTORE DATABASE [ecommerce] FROM DISK = N'$outputFile' WITH REPLACE\"\n";