<?php
// check_database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = 'localhost';
$databaseName = 'ecommerce';

// Comando para verificar tablas
$cmdCheck = "sqlcmd -S $server -E -Q \"SELECT COUNT(*) AS TableCount FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'\"";
exec($cmdCheck, $output, $return_var);

echo "<pre>Resultado de verificación:\n";
print_r($output);
echo "</pre>";

// Comando para verificar datos recientes
$cmdData = "sqlcmd -S $server -E -Q \"SELECT TOP 5 GETDATE() AS CurrentTime, * FROM YourMainTable ORDER BY CreatedAt DESC\"";
exec($cmdData, $outputData, $return_var);

echo "<pre>Datos recientes:\n";
print_r($outputData);
echo "</pre>";