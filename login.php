<?php
/**
 * PetNest - Login Page
 * Purpose: User authentication login interface with credentials validation.
 * Scope: Member 1
 */
$pageTitle = "Login - PetNest";
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'owner';
    switch ($role) {
        case 'admin':
            redirect('/admin/dashboard.php');
            break;
        case 'operator':
            redirect('/operator/dashboard.php');
            break;
        case 'keeper':
            redirect('/keeper/dashboard.php');
            break;
        default:
            redirect('/owner/dashboard.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, full_name, email, password, role, profile_photo, is_active FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ((int)$user['is_active'] !== 1) {
                    $error = "Your account has been deactivated by the system administrator. Please contact support.";
                } else {
                    // Start session & set user attributes
                    $_SESSION['user_id']    = (int)$user['user_id'];
                    $_SESSION['user_name']  = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role']  = $user['role'];
                    $_SESSION['user_photo'] = $user['profile_photo'] ?: 'default_avatar.png';

                    setFlash('success', "Welcome back, " . htmlspecialchars($user['full_name']) . "!");

                    // Role-based redirection
                    switch ($user['role']) {
                        case 'admin':
                            redirect('/admin/dashboard.php');
                            break;
                        case 'operator':
                            redirect('/operator/dashboard.php');
                            break;
                        case 'keeper':
                            redirect('/keeper/dashboard.php');
                            break;
                        case 'owner':
                        default:
                            redirect('/owner/dashboard.php');
                            break;
                    }
                }
            } else {
                $error = "Invalid email address or password. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database query error: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 480px; padding: 40px 20px;">
    <div class="card">
        <div class="card-header" style="text-align: center; display: block;">
            <div style="font-size: 2.2rem; color: var(--primary); margin-bottom: 8px;">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h2 class="card-title" style="font-size: 1.6rem;">Sign In to PetNest</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">Enter your credentials to access your account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="display: block;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email"><i class="fa-solid fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="e.g. janani@petnest.com" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.05rem; margin-top: 8px;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account yet? <a href="<?= BASE_URL ?>/register.php" style="font-weight: 600;">Register here</a>
        </div>
    </div>

    <!-- Quick Credentials Helper for Demo / Testing -->
    <div class="card" style="margin-top: 20px; background: #F8F5F0; border: 1px dashed var(--border-color); padding: 16px;">
        <h4 style="font-size: 0.85rem; text-transform: uppercase; color: var(--primary); margin-bottom: 8px;">
            <i class="fa-solid fa-key"></i> Quick Demo Accounts (Password: <code>password123</code>)
        </h4>
        <div style="font-size: 0.8rem; line-height: 1.6; color: var(--text-muted);">
            <div><strong>Pet Owner:</strong> <a href="#" onclick="fillLogin('janani@petnest.com'); return false;">janani@petnest.com</a></div>
            <div><strong>Pet Keeper:</strong> <a href="#" onclick="fillLogin('sarah@petnest.com'); return false;">sarah@petnest.com</a></div>
            <div><strong>Operator:</strong> <a href="#" onclick="fillLogin('operator@petnest.com'); return false;">operator@petnest.com</a></div>
            <div><strong>Admin:</strong> <a href="#" onclick="fillLogin('admin@petnest.com'); return false;">admin@petnest.com</a></div>
        </div>
    </div>
</div>

<script>
function fillLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
