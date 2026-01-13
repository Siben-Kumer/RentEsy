<?php
// The header file starts the session and includes necessary styles.
require_once 'includes/header.php';
require_once 'database.php'; // Provides the $pdo object for database connection.
require_once 'includes/mailer.php'; // For sending registration email.

// Initialize variables to store form data and errors.
$errors = [];
$name = '';
$email = '';
$phone = '';
$role = ''; // Default role will be set by form or token
$invite_token_data = null; // Stores data if an invite token is used

// Check for invite token in URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $stmt_token = $pdo->prepare("
        SELECT * FROM registration_tokens 
        WHERE token = ? AND expires_at > NOW() AND used_by_tenant_id IS NULL
    ");
    $stmt_token->execute([$token]);
    $invite_token_data = $stmt_token->fetch();

    if ($invite_token_data) {
        $role = 'tenant'; // Force role to tenant if using invite

        // Fetch unit and property info to display to the user
        $stmt_unit_info = $pdo->prepare("
            SELECT pu.unit_number, p.name as property_name, p.address
            FROM property_units pu
            JOIN properties p ON pu.property_id = p.id
            WHERE pu.id = ? AND p.id = ?
        ");
        $stmt_unit_info->execute([$invite_token_data['unit_id'], $invite_token_data['property_id']]);
        $unit_info = $stmt_unit_info->fetch();

    } else {
        $errors[] = 'Invalid or expired invite token. You can register without a token or request a new one.';
    }
}

// Generate a CSRF token to protect against Cross-Site Request Forgery.
// This token is stored in the session and validated on form submission.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form data only if the request method is POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. CSRF Token Validation: Ensure the form was submitted from our site.
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token. Please refresh and try again.';
    } else {
        // Sanitize and retrieve form data to prevent XSS.
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password']; // Password is not sanitized here to check its length first.
        $password_confirm = $_POST['password_confirm'];
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));

        // If an invite token is submitted with the form, this is an invite-based registration.
        if (isset($_POST['token']) && !empty($_POST['token'])) {
            $posted_token = $_POST['token'];
            
            // Re-validate the token on submission to ensure it's still valid.
            $stmt_token = $pdo->prepare("
                SELECT * FROM registration_tokens 
                WHERE token = ? AND expires_at > NOW() AND used_by_tenant_id IS NULL
            ");
            $stmt_token->execute([$posted_token]);
            $invite_token_data = $stmt_token->fetch();

            if (!$invite_token_data) {
                $errors[] = 'The invite token submitted is invalid or has expired. Registration will proceed as a standard user.';
                // Unset token data to prevent accidental processing
                $invite_token_data = null; 
                // Since token is invalid, the role might not be set. Let's ensure it's not empty.
                $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING) ?: 'tenant';
            } else {
                $role = 'tenant'; // A valid invite always makes the user a tenant.
            }
        } else {
            // This is a standard registration without an invite token.
            $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        }

        // 2. Server-side Validation Logic.
        if (empty($name)) {
            $errors[] = 'Full Name is required.';
        } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            $errors[] = 'Name must contain only letters and spaces (no numbers or symbols).';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif (strpos($email, ' ') !== false) {
            $errors[] = 'Email address cannot contain spaces.';
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match("/[a-z]/", $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match("/[0-9]/", $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';
        
        if (!empty($phone) && !preg_match("/^[0-9]+$/", $phone)) {
            $errors[] = 'Phone number must contain only numbers (no letters or symbols).';
        }

        if (empty($role) || !in_array($role, ['landlord', 'tenant'])) $errors[] = 'You must select a role (Landlord or Tenant).';

        // Check for email uniqueness if the email format is valid.
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please <a href="login.php">login</a>.';
            }
        }
    }

    // If validation passes (no errors), proceed with user creation.
    if (empty($errors)) {
        // 3. Securely hash the password before storing it.
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate email verification token
        $verification_token = bin2hex(random_bytes(32));

        // 4. Insert the new user data into the database.
        try {
            $pdo->beginTransaction();
            // Added verification_token and is_verified (default 0 in DB or handled here if no default)
            $sql = "INSERT INTO users (name, email, password_hash, role, phone, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $pdo->prepare($sql);
            // The 'phone' field is inserted as NULL if empty.
            $stmt->execute([$name, $email, $password_hash, $role, empty($phone) ? null : $phone, $verification_token]);
            $new_user_id = $pdo->lastInsertId();

            // If registration was via an invite token, create tenancy and mark token as used
            if ($invite_token_data && $role === 'tenant') {
                $stmt_tenancy = $pdo->prepare(
                    "INSERT INTO tenancies (property_id, unit_id, tenant_id, start_date, status)
                     VALUES (?, ?, ?, CURDATE(), 'active')"
                );
                $stmt_tenancy->execute([
                    $invite_token_data['property_id'], 
                    $invite_token_data['unit_id'], 
                    $new_user_id
                ]);

                // Update unit status to occupied
                $stmt_update_unit_status = $pdo->prepare("UPDATE property_units SET status = 'occupied' WHERE id = ?");
                $stmt_update_unit_status->execute([$invite_token_data['unit_id']]);

                // Mark token as used
                $stmt_update_token = $pdo->prepare("UPDATE registration_tokens SET used_by_tenant_id = ? WHERE id = ?");
                $stmt_update_token->execute([$new_user_id, $invite_token_data['id']]);
            }
            
            $_SESSION['success_message'] = 'Registration successful! Please check your email to verify your account.';
            
            // Send verification email
            $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $verification_token;
            $subject = "Verify your RentEsy Account";
            $body = "Dear " . htmlspecialchars($name) . ",\n\n"
                    . "Thank you for registering with RentEsy. Please click the link below to verify your email address:\n\n"
                    . $verification_link . "\n\n"
                    . "If you did not register for this account, please ignore this email.\n\n"
                    . "Best regards,\n"
                    . "The RentEsy Team";
            
            if (!send_mail($email, $subject, $body)) {
                error_log("Failed to send verification email to " . $email);
                // Optionally add a user-facing error, but registration is successful regardless of email.
            }

            $pdo->commit();
            header("Location: login.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            // 6. Handle potential database errors gracefully.
            error_log('Registration Database Error: ' . $e->getMessage());
            $errors[] = 'A database error occurred during registration. Please try again later.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0">Create Your RentEsy Account</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Errors Found!</h4>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; /* Already sanitized or safe */ ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="post" novalidate>
                    <!-- Hidden CSRF token field for security -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <?php if ($invite_token_data): ?>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($invite_token_data['token']); ?>">
                        <div class="alert alert-info">
                            You are registering to be automatically assigned to unit <strong><?php echo htmlspecialchars($unit_info['unit_number']); ?></strong> in <strong><?php echo htmlspecialchars($unit_info['property_name'] ?? $unit_info['address']); ?></strong>.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" pattern="[A-Za-z\s]+" title="Name must contain only letters and spaces (no numbers or symbols)." required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" title="Must contain at least one uppercase letter, one lowercase letter, one number, and be at least 8 characters long." required>
                            <div class="form-text">Min. 8 characters with uppercase, lowercase & number.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                    </div>
                    
                    <?php if (!$invite_token_data): // Only show role selection if no invite token ?>
                    <div class="mb-3">
                        <label class="form-label d-block">Register as:</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_tenant" value="tenant" <?php if ($role === 'tenant') echo 'checked'; ?> required>
                            <label class="form-check-label" for="role_tenant">Tenant</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_landlord" value="landlord" <?php if ($role === 'landlord') echo 'checked'; ?> required>
                            <label class="form-check-label" for="role_landlord">Landlord</label>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="role" value="tenant">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" pattern="[0-9]+" title="Phone number must contain only numbers (no letters or symbols)." placeholder="e.g., 01712345678">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login here</a>.</p>
            </div>
        </div>
    </div>
</div>

<?php
// The footer file includes closing HTML tags and scripts.
require_once 'includes/footer.php';
?>

