<?php
require_once 'includes/header.php';
require_once 'database.php';
require_once 'includes/auth.php';

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user'])) {
    $dashboard_url = 'dashboard/' . strtolower($_SESSION['user']['role']) . '/';
    header("Location: $dashboard_url");
    exit();
}

// Attempt to log in user via "Remember Me" cookie if they aren't logged in
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
    $user = validate_remember_me_cookie($pdo);
    if ($user) {
        // Log the user in
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'profile_picture' => $user['profile_picture']
        ];

        // Redirect to the appropriate dashboard
        $dashboard_url = 'dashboard/' . strtolower($user['role']) . '/';
        header("Location: $dashboard_url");
        exit();
    } else {
        // Invalid cookie, clear it
        clear_remember_me_cookie();
    }
}

$error = '';
$email = '';

// Generate CSRF token for the login form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            // Fetch user from the database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify password if user exists
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // Check if account is verified
                if (isset($user['is_verified']) && $user['is_verified'] == 0) {
                     $error = 'Please verify your email address to log in.';
                } else {
                    // Password is correct, set up the session
                    session_regenerate_id(true); // Prevent session fixation
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'profile_picture' => $user['profile_picture']
                    ];

                    // Handle "Remember Me" functionality
                    if ($remember_me) {
                        $tokens = generate_tokens($user['id'], $pdo);
                        if ($tokens) {
                            set_remember_me_cookie($tokens['selector'], $tokens['validator']);
                        }
                    }

                    // Redirect user to their respective dashboard
                    $dashboard_url = 'dashboard/' . strtolower($user['role']) . '/';
                    header("Location: $dashboard_url");
                    exit();
                }
            } else {
                // Failed login attempt
                $error = 'Invalid email or password.';
                // Optional: Implement failed login attempt tracking here
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0"><?php echo trans('secure_login'); ?></h2>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['status']) && $_GET['status'] === 'loggedout'): ?>
                    <div class="alert alert-success">
                        You have been successfully logged out.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'authrequired'): ?>
                    <div class="alert alert-warning">
                        You must be logged in to view that page.
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo trans('email_address'); ?></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo trans('password'); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check d-flex justify-content-between align-items-center">
                        <div>
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me"><?php echo trans('remember_me'); ?></label>
                        </div>
                        <a href="forgot_password.php" class="small text-decoration-none">Forgot Password?</a>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><?php echo trans('login'); ?></button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0"><?php echo trans('register_prompt'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
