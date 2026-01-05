<?php
// src/Views/admin/test.php
ob_start();
$pageTitle = 'Test';
?>

<div class="page-header mb-3">
    <h1 style="color: red; font-size: 48px;">✅ ESTO ES UN TEST</h1>
    <p class="text-muted">Si ves esto, las vistas están funcionando</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>Tarjeta de Prueba</h3>
    </div>
    <div class="card-body">
        <p style="font-size: 24px; color: green;">El contenido se está mostrando correctamente.</p>
        <ul>
            <li>Item 1</li>
            <li>Item 2</li>
            <li>Item 3</li>
        </ul>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
