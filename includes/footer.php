<?php
/**
 * SkillSwap Campus - Modular Footer & Script Loader
 */
$baseUrl = get_base_url();
?>
    <!-- Flash Notifications Toast Container -->
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="toast-container-custom">
        <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-lg" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom Scripts -->
    <script src="<?= $baseUrl ?>assets/js/script.js"></script>
    <script src="<?= $baseUrl ?>assets/js/dashboard.js"></script>
    <script src="<?= $baseUrl ?>assets/js/validation.js"></script>
</body>
</html>
