<?php
/**
 * SkillSwap Campus - Modular HTML Header
 */
require_once __DIR__ . '/functions.php';
init_session();
$baseUrl = get_base_url();
$pageTitle = $pageTitle ?? 'SkillSwap Campus - Learn. Teach. Connect.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Peer-to-peer student skill exchange platform for colleges. Swap skills without money!">
    
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
      // Automatically log out if the page is refreshed
      window.addEventListener('load', function() {
          const entries = performance.getEntriesByType("navigation");
          if (entries.length > 0 && entries[0].type === "reload") {
              window.location.href = "<?= $baseUrl ?>auth/logout-refresh.php";
          }
      });
    </script>
    <?php endif; ?>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom Design Tokens & Dashboard CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/dashboard.css">
</head>
<body>
