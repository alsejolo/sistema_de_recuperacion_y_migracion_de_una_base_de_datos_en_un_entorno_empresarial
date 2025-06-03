<?php
require_once 'auth.php';

$backupFolder = 'D:\\laragon\\backup_logs\\';

// Obtener archivos de backup
$encryptedFiles = glob($backupFolder . '*.bak.enc');
$normalFiles = glob($backupFolder . '*.bak');
$files = array_merge($encryptedFiles ?: [], $normalFiles ?: []);

// Ordenar por fecha de modificación descendente
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Backup para Exportar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .card {
            background: rgba(255, 255, 255, 0.95);
        }
        .backup-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .backup-card:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .selected {
            border: 3px solid #0d6efd;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Seleccionar Backup para Exportar</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($files)): ?>
                            <div class="alert alert-warning">No se encontraron backups disponibles.</div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                <?php foreach ($files as $file): 
                                    $fileName = basename($file);
                                    $fileDate = date("d/m/Y H:i:s", filemtime($file));
                                    $isEncrypted = (pathinfo($file, PATHINFO_EXTENSION) === 'enc');
                                    $fileSize = round(filesize($file) / (1024 * 1024), 2) . ' MB';
                                ?>
                                <div class="col">
                                    <div class="card backup-card h-100" onclick="selectBackup(this, '<?= htmlspecialchars($file) ?>', <?= $isEncrypted ? 'true' : 'false' ?>)">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($fileName) ?></h5>
                                            <p class="card-text">
                                                <strong>Fecha:</strong> <?= $fileDate ?><br>
                                                <strong>Tipo:</strong> <?= $isEncrypted ? 'Encriptado' : 'Normal' ?><br>
                                                <strong>Tamaño:</strong> <?= $fileSize ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <small class="text-muted">Haz clic para seleccionar</small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <form id="exportForm" method="get" action="export_db_to_excel_winauth.php" class="mt-4">
                                <input type="hidden" name="file" id="selectedBackup">
                                <div id="passwordContainer" class="mb-3" style="display:none;">
                                    <label for="password" class="form-label">Contraseña (para backup encriptado):</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>
                                <button type="submit" class="btn btn-success btn-lg" disabled id="exportBtn">
                                    <i class="bi bi-file-excel"></i> Exportar a Excel
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg ms-2">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
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

        let selectedFile = null;
        let isEncrypted = false;
        
        function selectBackup(element, file, encrypted) {
            // Deseleccionar todos los cards
            document.querySelectorAll('.backup-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Seleccionar el card actual
            element.classList.add('selected');
            selectedFile = file;
            isEncrypted = encrypted;
            
            // Habilitar botón de exportar
            document.getElementById('exportBtn').disabled = false;
            
            // Mostrar campo de contraseña si es encriptado
            document.getElementById('passwordContainer').style.display = encrypted ? 'block' : 'none';
            
            // Actualizar campo oculto con el archivo seleccionado
            document.getElementById('selectedBackup').value = file;
        }
    </script>
</body>
</html>