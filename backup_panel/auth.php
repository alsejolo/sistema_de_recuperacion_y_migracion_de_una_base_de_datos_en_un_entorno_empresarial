<?php
session_start();

// Verificar si la sesión está activa y el usuario autenticado
if (empty($_SESSION['authenticated'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

// Verificar privilegios de administrador
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php?error=access_denied');
    exit;
}

// Verificar tiempo de inactividad (opcional)
$inactivity_limit = 1800; // 30 minutos en segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=session_expired');
    exit;
}

// Actualizar marca de tiempo de última actividad
$_SESSION['last_activity'] = time();