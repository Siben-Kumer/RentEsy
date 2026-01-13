<?php
require_once 'includes/header.php';
require_once 'database.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

// Current time for validation
$current_time = date('Y-m-d H:i:s');

// 1. Initial validation on GET request to show/hide form
if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($token)) {
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ?");
    $stmt->execute([$token, $current_time]);
    if (!$stmt->fetch()) {
        $error = 'This password reset link is invalid or has expired.';
        $token = ''; // Hide the form
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['password_confirm'] ?? $_POST['confirm_password']; // Support both just in case

    if (empty($password) || strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Validate token using PHP time
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ?");
        $stmt->execute([$token, $current_time]);
        $reset_request = $stmt->fetch();

        if ($reset_request) {
            $email = $reset_request['email'];
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            if ($stmt->execute([$password_hash, $email])) {
                // Delete used token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);

                $message = 'Your password has been reset successfully. You can now <a href="login.php">login</a>.';
                $token = ''; // Hide form
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } else {
            $error = 'Invalid or expired token.';
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Reset Password</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($token): ?>
                    <form method="post" action="reset_password.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" title="Must contain at least one uppercase letter, one lowercase letter, one number, and be at least 8 characters long." required>
                            <div class="form-text">Min. 8 characters with uppercase, lowercase & number.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                <?php elseif (empty($message) && empty($error)): ?>
                    <div class="alert alert-warning">No token provided or token is invalid.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
