<?php
session_start();

// Redirigir a login si no está autenticado
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$backupFolder = 'D:\\laragon\\backup_logs\\';

// Obtener archivos encriptados y normales
$encryptedFiles = glob($backupFolder . '*.bak.enc');
$normalFiles = glob($backupFolder . '*.bak');

// Combinar ambos arreglos
$files = array_merge($encryptedFiles ?: [], $normalFiles ?: []);

// Ordenar por fecha de modificación descendente
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Backups de Base de Datos Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            top: 0;
            left: 0;
            background: #071a2f;
        }
        .content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-weight: bold;
        }
        .export-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        .btn-excel {
            background-color: #1d6f42;
            border-color: #1a633b;
            color: white;
        }
        .btn-excel:hover {
            background-color: #1a633b;
            border-color: #165530;
            color: white;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <!-- Botón para exportar a Excel -->
    <!-- Cambiar esta parte -->
<a href="select_backup.php" class="btn btn-success">
    <i class="bi bi-file-excel"></i> Exportar Datos
</a>
    <!-- Mostrar información de usuario -->
    <div class="user-info">
        <?php echo $_SESSION['username']; ?> | 
        <a href="logout.php" class="text-white">Cerrar sesión</a>
    </div>

    <div class="container my-5">
        <div class="content-wrapper">
            <h1 class="mb-4 text-center">Backups de la base de datos <span class="text-primary">ecommerce</span></h1>

            <?php if ($_SESSION['is_admin']): ?>
            <div class="d-flex justify-content-center mb-4 gap-3">
                <!-- Botón backup normal -->
                <form action="backup.php" method="post" style="display:inline;">
                    <input type="hidden" name="encrypted" value="0" />
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-cloud-arrow-up"></i> Crear Backup Normal
                    </button>
                </form>

                <!-- Botón backup encriptado -->
                <button id="btnBackupEncrypted" class="btn btn-warning btn-lg">
                    <i class="bi bi-lock"></i> Crear Backup Encriptado
                </button>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['mail_error'])): ?>
                <div class="alert alert-warning text-center">
                    Nota: El backup se creó correctamente pero no se pudo enviar la notificación por correo.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['restore_error'])): ?>
                <div class="alert alert-danger text-center">
                    Error al restaurar el backup: <?php echo htmlspecialchars($_GET['restore_error']); ?>
                </div>
            <?php elseif (isset($_GET['restore_success'])): ?>
                <div class="alert alert-success text-center">
                    ¡Backup restaurado correctamente!
                </div>
            <?php endif; ?>

            <?php if (count($files) === 0): ?>
                <div class="alert alert-warning text-center" role="alert">
                    No se encontraron backups en la carpeta.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Archivo</th>
                                <th>Fecha de creación</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file):
                                $fileName = basename($file);
                                $fileDate = date("d/m/Y H:i:s", filemtime($file));
                                $isEncrypted = (pathinfo($file, PATHINFO_EXTENSION) === 'enc');
                                $fileSize = round(filesize($file) / (1024 * 1024), 2) . ' MB';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fileName); ?></td>
                                <td><?php echo $fileDate; ?></td>
                                <td><?php echo $isEncrypted ? 'Encriptado' : 'Normal'; ?></td>
                                <td><?php echo $fileSize; ?></td>
                                <td class="text-center">
                                    <?php if ($isEncrypted): ?>
                                        <button class="btn btn-danger btn-sm restore-btn" 
                                                data-file="<?php echo htmlspecialchars($file); ?>"
                                                data-encrypted="1">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                        </button>
                                    <?php else: ?>
                                        <form action="restore.php" method="post" class="d-inline">
                                            <input type="hidden" name="backupFile" value="<?php echo htmlspecialchars($file); ?>">
                                            <input type="hidden" name="encrypted" value="0" />
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Modal para contraseña -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contraseña requerida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="restoreEncryptedForm" method="post" action="restore.php">
                        <input type="hidden" name="backupFile" id="modalBackupFile">
                        <input type="hidden" name="encrypted" value="1">
                        <div class="mb-3">
                            <label for="inputPassword" class="form-label">Ingrese la contraseña para desencriptar:</label>
                            <input type="password" class="form-control" id="inputPassword" name="password" required>
                            <small class="text-muted">La contraseña debe ser la misma usada al crear el backup</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="restoreEncryptedForm" class="btn btn-primary">Restaurar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        // Crear backup encriptado - pide contraseña y envía POST
        document.getElementById('btnBackupEncrypted').addEventListener('click', function() {
            const password = prompt('Ingrese la contraseña para encriptar el backup:');
            if (!password) {
                alert('Debes ingresar una contraseña para crear el backup encriptado.');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'backup.php';

            const encryptedInput = document.createElement('input');
            encryptedInput.type = 'hidden';
            encryptedInput.name = 'encrypted';
            encryptedInput.value = '1';
            form.appendChild(encryptedInput);

            const passInput = document.createElement('input');
            passInput.type = 'hidden';
            passInput.name = 'password';
            passInput.value = password;
            form.appendChild(passInput);

            document.body.appendChild(form);
            form.submit();
        });

        // Manejar restauración de backups encriptados
        document.querySelectorAll('.restore-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const backupFile = this.getAttribute('data-file');
                document.getElementById('modalBackupFile').value = backupFile;
                
                const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
                modal.show();
            });
        });

        // Configurar partículas
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 80,
                    "density": {
                        "enable": true,
                        "value_area": 700
                    }
                },
                "color": {
                    "value": "#ffffff"
                },
                "shape": {
                    "type": "circle"
                },
                "opacity": {
                    "value": 0.5,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 1,
                        "opacity_min": 0.1,
                        "sync": false
                    }
                },
                "size": {
                    "value": 3,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 4,
                        "size_min": 0.3,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 120,
                    "color": "#ffffff",
                    "opacity": 0.25,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 3,
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "bounce": false,
                    "attract": {
                        "enable": false
                    }
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                },
                "modes": {
                    "grab": {
                        "distance": 140,
                        "line_linked": {
                            "opacity": 0.4
                        }
                    },
                    "push": {
                        "particles_nb": 4
                    }
                }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>