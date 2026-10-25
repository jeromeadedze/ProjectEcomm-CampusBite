<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
?>

<div class="p-5 mb-4 bg-light rounded-4 text-center border">
    <div class="container-fluid py-4">
        <h1 class="display-5 fw-bold">CampusBite Starter Template</h1>
        <p class="col-md-8 mx-auto fs-5 text-muted">
            The base MVC foundation (Core, Database, Classes, Controllers, Actions) is ready and connected.
        </p>
        <span class="badge bg-dark rounded-pill px-3 py-2">Ready for Custom UI / Design Implementation</span>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="p-4 border rounded-4 bg-white h-100">
            <h5 class="fw-bold"><i class="bi bi-layers text-primary me-2"></i>3-Tier MVC Architecture</h5>
            <p class="text-muted small">
                Organized into <code>core/</code>, <code>classes/</code> (models), <code>controllers/</code>, <code>actions/</code>, and <code>views/</code>.
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 border rounded-4 bg-white h-100">
            <h5 class="fw-bold"><i class="bi bi-palette text-warning me-2"></i>Clean Canvas</h5>
            <p class="text-muted small">
                Minimal styles in <code>css/style.css</code> ready to adopt any UI design system, color palette, or wireframes you plan.
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 border rounded-4 bg-white h-100">
            <h5 class="fw-bold"><i class="bi bi-database-check text-success me-2"></i>Database Ready</h5>
            <p class="text-muted small">
                PDO database class with secure prepared statements ready to plug into custom views.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
