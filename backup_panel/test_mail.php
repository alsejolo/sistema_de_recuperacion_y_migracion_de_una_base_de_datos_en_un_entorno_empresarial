<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$to = 'tuemail@tudominio.com';
$subject = 'Prueba de correo desde PHP';
$message = 'Este es un mensaje de prueba para verificar la configuración de correo.';
$headers = 'From: webmaster@tudominio.com' . "\r\n" .
    'Reply-To: webmaster@tudominio.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo 'Correo enviado correctamente';
} else {
    echo 'Error al enviar correo: ' . print_r(error_get_last(), true);
}