<?php
/**
 * SkillSwap Campus - Edit Profile & Avatar Upload Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Edit Profile - SkillSwap Campus';
$userId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$error = '';
$success = '';

// Fetch existing user record
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmtUser->execute(['id' => $userId]);
$user = $stmtUser->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = sanitize($_POST['name'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $year       = sanitize($_POST['year'] ?? '');
    $bio        = sanitize($_POST['bio'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid security token.';
    } elseif (empty($name) || empty($department) || empty($year)) {
        $error = 'Name, Department, and Academic Year are required.';
    } else {
        $profileImage = $user['profile_image'];

        // Handle File Upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileSize = $_FILES['profile_image']['size'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($fileExt, $allowedExts)) {
                $error = 'Invalid image type. Only JPG, PNG, and WEBP are allowed.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = 'Image file size must be less than 2MB.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExt;
                $targetFile  = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetFile)) {
                    $profileImage = $newFileName;
                    $_SESSION['profile_image'] = $newFileName;
                } else {
                    $error = 'Failed to upload profile picture.';
                }
            }
        }

        if (empty($error)) {
            $stmtUpdate = $pdo->prepare("
                UPDATE users 
                SET name = :name, department = :department, year = :year, bio = :bio, profile_image = :img, updated_at = NOW()
                WHERE user_id = :id
            ");
            $stmtUpdate->execute([
                'name'       => $name,
                'department' => $department,
                'year'       => $year,
                'bio'        => $bio,
                'img'        => $profileImage,
                'id'         => $userId
            ]);

            $_SESSION['user_name'] = $name;
            set_flash('success', 'Profile updated successfully!');
            header('Location: ' . $baseUrl . 'student/profile.php');
            exit;
        }
    }
}

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid" style="max-width: 800px;">
      <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Edit Profile</h2>
          <p class="text-muted mb-0">Update your personal details, academic information, and avatar.</p>
        </div>
        <a href="<?= $baseUrl ?>student/profile.php" class="btn btn-outline-custom">View Profile</a>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="card-custom p-4 p-md-5">
        <form method="POST" action="edit-profile.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

          <!-- Profile Picture Section -->
          <div class="d-flex align-items-center gap-4 mb-4 pb-4 border-bottom">
            <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" alt="Avatar" class="avatar-lg" style="width: 80px; height: 80px;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=4f46e5&color=fff';">
            <div>
              <label for="profile_image" class="form-label fw-semibold">Profile Avatar Picture</label>
              <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
              <span class="small text-muted d-block mt-1">Allowed formats: JPG, PNG, WEBP. Max size: 2MB.</span>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="name" class="form-label fw-semibold">Full Name *</label>
              <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label fw-semibold">Email Address (Read Only)</label>
              <input type="email" class="form-control bg-light" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="col-md-6">
              <label for="college_id" class="form-label fw-semibold">College ID (Read Only)</label>
              <input type="text" class="form-control bg-light" id="college_id" value="<?= htmlspecialchars($user['college_id']) ?>" readonly>
            </div>

            <div class="col-md-6">
              <label for="department" class="form-label fw-semibold">Department *</label>
              <select class="form-select" id="department" name="department" required>
                <option value="Computer Engineering" <?= $user['department'] === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                <option value="Computer Science" <?= $user['department'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                <option value="Electronics" <?= $user['department'] === 'Electronics' ? 'selected' : '' ?>>Electronics & Telecom</option>
                <option value="Information Technology" <?= $user['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                <option value="Mechanical Engineering" <?= $user['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                <option value="Civil Engineering" <?= $user['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                <option value="Artificial Intelligence" <?= $user['department'] === 'Artificial Intelligence' ? 'selected' : '' ?>>Artificial Intelligence & ML</option>
                <option value="Data Science" <?= $user['department'] === 'Data Science' ? 'selected' : '' ?>>Data Science</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="year" class="form-label fw-semibold">Academic Year *</label>
              <select class="form-select" id="year" name="year" required>
                <option value="1st Year" <?= $user['year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                <option value="2nd Year" <?= $user['year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                <option value="3rd Year" <?= $user['year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                <option value="4th Year" <?= $user['year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                <option value="Postgraduate" <?= $user['year'] === 'Postgraduate' ? 'selected' : '' ?>>Postgraduate</option>
              </select>
            </div>

            <div class="col-12">
              <label for="bio" class="form-label fw-semibold">Bio & Learning Goals</label>
              <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Share a few lines about what skills you teach or what projects you are building..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom px-4">
              <i class="bi bi-save me-1"></i> Save Changes
            </button>
            <a href="<?= $baseUrl ?>student/profile.php" class="btn btn-light border">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
