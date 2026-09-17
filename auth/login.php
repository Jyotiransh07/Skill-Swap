<?php
/**
 * SkillSwap Campus - Login Page
 */
require_once __DIR__ . '/../includes/functions.php';
init_session();

$baseUrl = get_base_url();
$pageTitle = 'Login - SkillSwap Campus';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ' . $baseUrl . 'admin/dashboard.php');
    } else {
        header('Location: ' . $baseUrl . 'student/dashboard.php');
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid security token. Please try submitting again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email address and password.';
    } else {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT user_id, name, email, password, role, status, profile_image FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account status is currently ' . htmlspecialchars($user['status']) . '. Please contact the administrator.';
                } else {
                    // Successful Authentication
                    $_SESSION['user_id']       = $user['user_id'];
                    $_SESSION['user_name']     = $user['name'];
                    $_SESSION['user_email']    = $user['email'];
                    $_SESSION['user_role']     = $user['role'];
                    $_SESSION['profile_image'] = $user['profile_image'];

                    set_flash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                    if ($user['role'] === 'admin') {
                        header('Location: ' . $baseUrl . 'admin/dashboard.php');
                    } else {
                        header('Location: ' . $baseUrl . 'student/dashboard.php');
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email address or password.';
            }
        } catch (Exception $e) {
            $error = 'Authentication error: ' . $e->getMessage();
        }
    }
}

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0 min-vh-100 d-flex align-items-center justify-content-center bg-light">
  <div class="row g-0 w-100 shadow-lg rounded-4 overflow-hidden my-4" style="max-width: 1000px; background: #fff;">
    <!-- Left Hero Side -->
    <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between p-5 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 100%);">
      <div>
        <div class="d-flex align-items-center gap-2 mb-4">
          <div class="bg-primary text-white rounded-3 p-2 fw-bold"><i class="bi bi-arrow-repeat fs-4"></i></div>
          <span class="fs-4 fw-bold">SkillSwap Campus</span>
        </div>
        <h2 class="display-6 fw-bold mb-3 text-white">Learn. Teach. Connect.</h2>
        <p class="text-white-50 fs-5">Exchange knowledge with fellow college students without spending money.</p>
      </div>

      <div class="p-4 rounded-3" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px);">
        <div class="d-flex align-items-center gap-3 mb-2">
          <i class="bi bi-stars text-warning fs-3"></i>
          <span class="fw-bold text-white">Smart Compatibility Engine</span>
        </div>
        <p class="small text-white-50 mb-0">Our automated algorithm pairs your learning goals with students who teach exactly what you need.</p>
      </div>

      <div class="small text-white-50">
        &copy; <?= date('Y') ?> SkillSwap Campus. Peer-to-Peer Student Exchange.
      </div>
    </div>

    <!-- Right Login Form -->
    <div class="col-lg-6 p-4 p-sm-5 d-flex flex-column justify-content-center">
      <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
        <p class="text-muted">Sign in to manage your skill exchanges and sessions.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="this.closest('.alert').remove();"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="mb-3">
          <label for="email" class="form-label fw-semibold">Campus Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="student@skillswap.edu" required>
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label fw-semibold">Password</label>
            <a href="forgot-password.php" class="small text-primary text-decoration-none">Forgot password?</a>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="remember" name="remember">
          <label class="form-check-label text-muted small" for="remember">
            Remember me on this browser
          </label>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3 fs-6">
          <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
        </button>

        <div class="text-center">
          <span class="text-muted small">Don't have an account yet? </span>
          <a href="register.php" class="fw-bold text-primary text-decoration-none small">Create Student Account</a>
        </div>
      </form>

      <!-- Demo Credentials Helper Card -->
      <div class="mt-4 p-3 bg-light rounded-3 border">
        <div class="fw-bold text-dark small mb-1"><i class="bi bi-key-fill text-primary me-1"></i> Demo Login Credentials:</div>
        <div class="row g-2 small text-muted">
          <div class="col-6">
            <strong>Student:</strong> rahul@skillswap.edu<br>
            <strong>Password:</strong> Student@123
          </div>
          <div class="col-6">
            <strong>Admin:</strong> admin@skillswap.edu<br>
            <strong>Password:</strong> Student@123
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
