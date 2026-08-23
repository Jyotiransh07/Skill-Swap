<?php
/**
 * SkillSwap Campus - Student Registration Page
 */
require_once __DIR__ . '/../includes/functions.php';
init_session();

$baseUrl = get_base_url();
$pageTitle = 'Register Student Account - SkillSwap Campus';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $baseUrl . 'student/dashboard.php');
    exit;
}

$error = '';
$formData = [
    'name' => '', 'email' => '', 'college_id' => '',
    'department' => '', 'year' => '1st Year', 'bio' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']       = sanitize($_POST['name'] ?? '');
    $formData['email']      = sanitize($_POST['email'] ?? '');
    $formData['college_id'] = sanitize($_POST['college_id'] ?? '');
    $formData['department'] = sanitize($_POST['department'] ?? '');
    $formData['year']       = sanitize($_POST['year'] ?? '1st Year');
    $formData['bio']        = sanitize($_POST['bio'] ?? '');

    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token       = $_POST['csrf_token'] ?? '';
    $terms            = isset($_POST['terms']);

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security check failed. Please re-submit the form.';
    } elseif (empty($formData['name']) || empty($formData['email']) || empty($formData['college_id']) || empty($formData['department']) || empty($password)) {
        $error = 'Please fill out all required fields marked with *';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!$terms) {
        $error = 'You must agree to the SkillSwap Campus terms of service.';
    } else {
        try {
            $pdo = Database::getConnection();

            // Check duplicate email or college_id
            $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE email = :email OR college_id = :college_id LIMIT 1");
            $stmtCheck->execute(['email' => $formData['email'], 'college_id' => $formData['college_id']]);
            if ($stmtCheck->fetch()) {
                $error = 'An account with this Email address or College ID already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, password, college_id, department, year, bio, role, status, reputation_score, created_at)
                    VALUES (:name, :email, :password, :college_id, :department, :year, :bio, 'student', 'active', 50.00, NOW())
                ");

                $stmtInsert->execute([
                    'name'       => $formData['name'],
                    'email'      => $formData['email'],
                    'password'   => $hashedPassword,
                    'college_id' => $formData['college_id'],
                    'department' => $formData['department'],
                    'year'       => $formData['year'],
                    'bio'        => $formData['bio']
                ]);

                $newUserId = $pdo->lastInsertId();

                // Create Welcome Notification
                create_notification($pdo, (int)$newUserId, "Welcome to SkillSwap Campus! Head to 'My Skills' to add what you teach and want to learn.", "SYSTEM");

                // Log in immediately
                $_SESSION['user_id']       = (int)$newUserId;
                $_SESSION['user_name']     = $formData['name'];
                $_SESSION['user_email']    = $formData['email'];
                $_SESSION['user_role']     = 'student';
                $_SESSION['profile_image'] = 'default-avatar.png';

                set_flash('success', 'Registration successful! Welcome to SkillSwap Campus.');
                header('Location: ' . $baseUrl . 'student/skills.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration error: ' . $e->getMessage();
        }
    }
}

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card-custom p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-2" style="width: 56px; height: 56px;">
            <i class="bi bi-person-plus-fill fs-3"></i>
          </div>
          <h2 class="fw-bold text-dark mb-1">Create Student Account</h2>
          <p class="text-muted">Join SkillSwap Campus and start exchanging skills with fellow students.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="registerForm" class="needs-validation">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label for="name" class="form-label fw-semibold">Full Name *</label>
              <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="e.g. Rahul Sharma" required>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label fw-semibold">College Email Address *</label>
              <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="student@skillswap.edu" required>
            </div>

            <div class="col-md-6">
              <label for="college_id" class="form-label fw-semibold">College ID / Roll Number *</label>
              <input type="text" class="form-control" id="college_id" name="college_id" value="<?= htmlspecialchars($formData['college_id']) ?>" placeholder="e.g. CS202301" required>
            </div>

            <div class="col-md-6">
              <label for="department" class="form-label fw-semibold">Department *</label>
              <select class="form-select" id="department" name="department" required>
                <option value="" disabled <?= empty($formData['department']) ? 'selected' : '' ?>>Select Department</option>
                <option value="Computer Engineering" <?= $formData['department'] === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                <option value="Computer Science" <?= $formData['department'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                <option value="Electronics" <?= $formData['department'] === 'Electronics' ? 'selected' : '' ?>>Electronics & Telecom</option>
                <option value="Information Technology" <?= $formData['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                <option value="Mechanical Engineering" <?= $formData['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                <option value="Civil Engineering" <?= $formData['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                <option value="Artificial Intelligence" <?= $formData['department'] === 'Artificial Intelligence' ? 'selected' : '' ?>>Artificial Intelligence & ML</option>
                <option value="Data Science" <?= $formData['department'] === 'Data Science' ? 'selected' : '' ?>>Data Science</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="year" class="form-label fw-semibold">Academic Year *</label>
              <select class="form-select" id="year" name="year" required>
                <option value="1st Year" <?= $formData['year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                <option value="2nd Year" <?= $formData['year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                <option value="3rd Year" <?= $formData['year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                <option value="4th Year" <?= $formData['year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                <option value="Postgraduate" <?= $formData['year'] === 'Postgraduate' ? 'selected' : '' ?>>Postgraduate</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="bio" class="form-label fw-semibold">Short Bio (Optional)</label>
              <input type="text" class="form-control" id="bio" name="bio" value="<?= htmlspecialchars($formData['bio']) ?>" placeholder="Tell peers what you love teaching...">
            </div>

            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold">Password *</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 6 characters" required>
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="password-strength-bar" id="passwordStrengthBar"></div>
            </div>

            <div class="col-md-6">
              <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
            </div>
          </div>

          <div class="form-check mt-3 mb-4">
            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
            <label class="form-check-label text-muted small" for="terms">
              I agree to the <a href="#" class="text-primary">SkillSwap Campus Code of Honor</a> and promise to exchange skills respectfully without money.
            </label>
          </div>

          <button type="submit" class="btn btn-primary-custom w-100 py-2 fs-6">
            <i class="bi bi-check-circle-fill me-2"></i> Register Account
          </button>
        </form>

        <div class="text-center mt-4">
          <span class="text-muted small">Already have an account? </span>
          <a href="login.php" class="fw-bold text-primary text-decoration-none small">Sign In here</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
