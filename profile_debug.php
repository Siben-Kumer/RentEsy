<?php
// profile_debug.php
// TEMPORARY DEBUGGING ENABLED
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Initialize session and auth
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'database.php';

echo "<!-- Debug: Loaded includes -->\n";

// 2. Ensure user is logged in
checkAuth();
$user_id = $_SESSION['user']['id'];

echo "<!-- Debug: User ID is $user_id -->\n";

// 3. Handle Form Submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- Debug: Processing POST -->\n";
    
    // Sanitize
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
    $password = $_POST['password'];

    // Validation
    if (empty($name)) $error = 'Name is required.';
    elseif (empty($email)) $error = 'Valid email is required.';
    
    // Check email uniqueness
    if (!$error) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) $error = 'Email already in use.';
    }

    // Handle File Upload
    // Get current picture from DB first to preserve it if no new upload
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user_db = $stmt->fetch();
    $profile_picture_name = $current_user_db['profile_picture'];

    if (!$error && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        echo "<!-- Debug: Processing File Upload -->\n";
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = 'Invalid file type. Allowed: JPG, PNG, GIF.';
        } elseif ($_FILES['profile_picture']['size'] > 5000000) {
            $error = 'File too large (Max 5MB).';
        } else {
            $new_name = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                $profile_picture_name = $new_name;
            } else {
                $error = 'Failed to upload file. Check folder permissions.';
            }
        }
    }

    // Update Database
    if (!$error) {
        echo "<!-- Debug: Updating Database -->\n";
        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, profile_picture = ?";
        $params = [$name, $email, $phone, $profile_picture_name];

        if (!empty($password)) {
            if (strlen($password) < 8) {
                $error = 'Password must be at least 8 chars.';
            } else {
                $sql .= ", password_hash = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        if (!$error) {
            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            try {
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    // Update session
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['profile_picture'] = $profile_picture_name;
                    
                    echo "<!-- Debug: Update Success. Redirecting... -->\n";
                    // Using a JS redirect here to see debug output if needed
                    echo "<script>window.location.href='profile.php?success=1';</script>";
                    exit;
                } else {
                    $error = 'Database update failed.';
                }
            } catch (PDOException $e) {
                die("Database Error: " . $e->getMessage());
            }
        }
    }
}

// 4. Fetch User Data for View
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- View Block: Output HTML ---
$page_title = 'My Profile';
require_once 'includes/dashboard_header.php'; 
?>

<div class="container-fluid px-4">
    <h1 class="h2 mb-4">My Profile (DEBUG MODE)</h1>

    <?php if (isset($_GET['success'])):
        
    ?>
        <div class="alert alert-success alert-dismissible fade show">
            Profile updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error):
        
    ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Update Information</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3 text-center">
                    <img src="/uploads/<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" 
                         alt="Profile" 
                         class="img-thumbnail rounded-circle" 
                         width="150" height="150"
                         style="object-fit: cover;">
                    <div class="mt-2">
                        <label class="form-label">Change Picture</label>
                        <input type="file" name="profile_picture" class="form-control w-50 mx-auto">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label">New Password (Optional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/dashboard_footer.php'; ?>
