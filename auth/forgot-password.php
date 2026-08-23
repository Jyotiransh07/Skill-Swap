<?php
/**
 * SkillSwap Campus - Password Reset Simulation Page
 */
require_once __DIR__ . '/../includes/functions.php';
init_session();

$baseUrl = get_base_url();
$pageTitle = 'Forgot Password - SkillSwap Campus';

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    if (!empty($email)) {
        $message = 'If an account exists for ' . htmlspecialchars($email) . ', password reset instructions have been dispatched to your inbox.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 min-vh-100 d-flex align-items-center justify-content-center">
  <div class="card-custom p-4 p-md-5" style="max-width: 500px; width: 100%;">
    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-2" style="width: 56px; height: 56px;">
        <i class="bi bi-key-fill fs-3"></i>
      </div>
      <h3 class="fw-bold text-dark">Reset Password</h3>
      <p class="text-muted small">Enter your registered college email address to receive reset instructions.</p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-success" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
      </div>
      <div class="text-center mt-3">
        <a href="login.php" class="btn btn-outline-custom w-100">Back to Login</a>
      </div>
    <?php else: ?>
      <form method="POST" action="forgot-password.php">
        <div class="mb-3">
          <label for="email" class="form-label fw-semibold">Campus Email Address</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="student@skillswap.edu" required>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3">
          Send Reset Instructions
        </button>
        <div class="text-center">
          <a href="login.php" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> Return to Login</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
