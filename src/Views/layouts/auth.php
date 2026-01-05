<?php
// src/Views/layouts/auth.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Login' ?> - ORFS Estadísticas</title>
    <link rel="stylesheet" href="<?= asset('/css/main.css') ?>">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        
        .auth-logo {
            text-align: center;
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .auth-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #2C3E50;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <?= $content ?? '' ?>
        </div>
    </div>
</body>
</html>