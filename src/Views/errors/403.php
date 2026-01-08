<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Prohibido</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5576c 0%, #d63447 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .error-container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }

        .error-code {
            font-size: 120px;
            font-weight: bold;
            margin: 0;
            line-height: 1;
            text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.3);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
            animation: swing 1s ease-in-out infinite;
        }

        @keyframes swing {
            0%, 100% { transform: rotate(-10deg); }
            50% { transform: rotate(10deg); }
        }

        .error-title {
            font-size: 32px;
            margin: 20px 0 10px 0;
            font-weight: 600;
        }

        .error-message {
            font-size: 18px;
            margin: 20px 0;
            opacity: 0.95;
            line-height: 1.6;
        }

        .error-reasons {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }

        .error-reasons h3 {
            margin-top: 0;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .error-reasons ul {
            margin: 0;
            padding-left: 20px;
        }

        .error-reasons li {
            margin: 8px 0;
            opacity: 0.9;
        }

        .error-actions {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .btn-primary {
            background: white;
            color: #f5576c;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.8);
        }

        .error-details {
            margin-top: 30px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            font-size: 14px;
            opacity: 0.8;
        }

        .warning-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 80px;
            }

            .error-icon {
                font-size: 60px;
            }

            .error-title {
                font-size: 24px;
            }

            .error-message {
                font-size: 16px;
            }

            .error-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>

        <h1 class="error-code">403</h1>

        <div class="warning-badge">
            <i class="fas fa-exclamation-triangle"></i> Acceso Denegado
        </div>

        <h2 class="error-title">No Tienes Permiso para Acceder</h2>

        <p class="error-message">
            Lo sentimos, no tienes los permisos necesarios para acceder a esta página o recurso.
        </p>

        <div class="error-reasons">
            <h3><i class="fas fa-info-circle"></i> Posibles razones:</h3>
            <ul>
                <li>No tienes el rol de usuario necesario (Admin/Trader)</li>
                <li>Estás intentando acceder a datos de otro usuario</li>
                <li>Tu sesión ha expirado</li>
                <li>Necesitas permisos especiales para esta acción</li>
            </ul>
        </div>

        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Ir a Inicio
            </a>
            <a href="/login" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </a>
        </div>

        <?php if (isset($requestedUrl)): ?>
        <div class="error-details">
            <strong>URL solicitada:</strong> <?= htmlspecialchars($requestedUrl) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($userRole)): ?>
        <div class="error-details">
            <strong>Tu rol actual:</strong> <?= htmlspecialchars(ucfirst($userRole)) ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
