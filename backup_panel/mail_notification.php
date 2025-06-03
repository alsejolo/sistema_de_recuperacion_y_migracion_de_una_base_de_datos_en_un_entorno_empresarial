<?php
function sendBackupNotification($subject, $message) {
    $to = "frankgoku2011@gmail.com";
    $from = "frankgoku2011@gmail.com";
    
    // Obtener información de conexión
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('d/m/Y H:i:s');
    
    // Construir mensaje base
    $emailContent = "📊 Reporte de Backup - Sistema Ecommerce\n";
    $emailContent .= "══════════════════════════════════\n\n";
    $emailContent .= "🔹 Tipo de backup: " . (strpos($message, 'encriptado') !== false ? 'Encriptado' : 'Normal') . "\n";
    $emailContent .= "🔹 Base de datos: ecommerce\n";
    $emailContent .= "🔹 Archivo: " . basename(explode("Archivo: ", $message)[1] ?? '') . "\n";
    $emailContent .= "🔹 Tamaño: " . (explode("Tamaño: ", $message)[1] ?? '') . "\n";
    $emailContent .= "🔹 Usuario: " . (explode("Usuario: ", $message)[1] ?? '') . "\n";
    $emailContent .= "🔹 Fecha: $timestamp\n\n";
    
    // Sección de información de conexión
    $emailContent .= "🌍 Información Geográfica\n";
    $emailContent .= "══════════════════════════════════\n";
    
    if (in_array($ip, ['127.0.0.1', '::1'])) {
        $emailContent .= "🔸 IP: $ip (Servidor local)\n";
    } else {
        try {
            $geoData = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,city,zip,lat,lon,isp,org,as,query");
            
            if ($geoData !== false) {
                $geoInfo = json_decode($geoData, true);
                
                if ($geoInfo && $geoInfo['status'] == 'success') {
                    $flag = empty($geoInfo['countryCode']) ? '' : ' ('.flagEmoji($geoInfo['countryCode']).')';
                    
                    $emailContent .= "🔸 IP: {$geoInfo['query']}\n";
                    $emailContent .= "🔸 Ubicación: {$geoInfo['city']}, {$geoInfo['regionName']}, {$geoInfo['country']}$flag\n";
                    $emailContent .= "🔸 Coordenadas: {$geoInfo['lat']}, {$geoInfo['lon']}\n";
                    $emailContent .= "🔸 Mapa: https://www.google.com/maps?q={$geoInfo['lat']},{$geoInfo['lon']}\n";
                    $emailContent .= "🔸 Proveedor: {$geoInfo['isp']}\n";
                    $emailContent .= "🔸 Organización: {$geoInfo['org']}\n";
                } else {
                    $emailContent .= "🔸 IP: $ip\n";
                    $emailContent .= "🔸 Ubicación: No disponible\n";
                }
            }
        } catch (Exception $e) {
            $emailContent .= "🔸 IP: $ip\n";
            $emailContent .= "🔸 Error obteniendo geolocalización\n";
        }
    }
    
    $emailContent .= "\n⚙️ Sistema\n";
    $emailContent .= "══════════════════════════════════\n";
    $emailContent .= "🖥 Servidor: " . gethostname() . "\n";
    $emailContent .= "📅 Hora del servidor: $timestamp\n";
    
    // Función para emojis de bandera (solo para países con código de 2 letras)
    function flagEmoji($countryCode) {
        $countryCode = strtoupper($countryCode);
        if (strlen($countryCode) != 2 || !ctype_alpha($countryCode)) {
            return '';
        }
        return implode('', array_map(function ($char) {
            return mb_chr(127397 + ord($char));
        }, str_split($countryCode)));
    }

    $headers = "From: Sistema de Backups <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

    if (mail($to, $subject, $emailContent, $headers)) {
        return true;
    } else {
        error_log("Error al enviar correo: " . print_r(error_get_last(), true));
        return false;
    }
}