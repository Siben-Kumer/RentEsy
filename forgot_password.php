<?php
require_once 'includes/header.php';
require_once 'database.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            if ($stmt->execute([$email, $token, $expires_at])) {
                // Send email using PHPMailer
                require_once 'includes/mailer.php';

                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                $subject = "Password Reset Request - RentEsy";
                $body = "Hi,\n\nWe received a request to reset your password. Click the link below to set a new password:\n\n<a href='" . $reset_link . "'>" . $reset_link . "</a>\n\nThis link expires in 1 hour.\n\nIf you did not request this, please ignore this email.";
                
                if (send_mail($email, $subject, $body)) {
                    $message = 'A password reset link has been sent to your email address.';
                } else {
                    $error = 'Failed to send email. Please check your system logs.';
                }
            } else {
                $error = 'Database error. Please try again.';
            }
        } else {
            // Security: Don't reveal if email exists or not
            $message = 'If an account exists with that email, a reset link has been sent.';
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Forgot Password</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>

                <form method="post" action="forgot_password.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
