<?php
require_once 'includes/init.php'; // Initializes session and DB connection
require_once 'includes/header.php';

$message = '';
$status_class = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Find user with this token
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Verify user
        $update_stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        if ($update_stmt->execute([$user['id']])) {
            $status_class = 'alert-success';
            $message = "<h4>Email Verified!</h4><p>Thank you, " . htmlspecialchars($user['name']) . ". Your email has been successfully verified.</p><p>You can now <a href='login.php'>login here</a>.</p>";
        } else {
            $status_class = 'alert-danger';
            $message = "<h4>Error</h4><p>Could not verify your email. Please try again later.</p>";
        }
    } else {
        $status_class = 'alert-danger';
        $message = "<h4>Invalid Token</h4><p>The verification link is invalid or has already been used.</p>";
    }
} else {
    $status_class = 'alert-warning';
    $message = "<h4>No Token Found</h4><p>Please click the link sent to your email address.</p>";
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="alert <?php echo $status_class; ?> text-center" role="alert">
            <?php echo $message; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>