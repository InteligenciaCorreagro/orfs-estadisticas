<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página No Encontrada</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
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
            color: #667eea;
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
            <i class="fas fa-search"></i>
        </div>

        <h1 class="error-code">404</h1>

        <h2 class="error-title">Página No Encontrada</h2>

        <p class="error-message">
            Lo sentimos, la página que estás buscando no existe o ha sido movida.
            <br>
            Verifica la URL o regresa a la página principal.
        </p>

        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Ir a Inicio
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Volver Atrás
            </a>
        </div>

        <?php if (isset($requestedUrl)): ?>
        <div class="error-details">
            <strong>URL solicitada:</strong> <?= htmlspecialchars($requestedUrl) ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
