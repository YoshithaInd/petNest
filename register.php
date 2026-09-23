<?php
/**
 * PetNest - Registration Page
 * Purpose: New user and keeper registration page with account setup.
 * Scope: Member 1
 */

$pageTitle = "Register - PetNest";
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'owner';
    redirect($role === 'owner' ? '/owner/dashboard.php' : '/' . $role . '/dashboard.php');
}

$errors = [];
$fullName = '';
$email = '';
$phone = '';
$role = $_GET['role'] ?? 'owner';
if (!in_array($role, ['owner', 'keeper'], true)) {
    $role = 'owner';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $role            = trim($_POST['role'] ?? 'owner');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($fullName)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (!in_array($role, ['owner', 'keeper'], true)) {
        $errors[] = "Please select a valid account type.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email already exists. Please log in.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    // Insert user
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password, phone, role, profile_photo, is_active)
                VALUES (?, ?, ?, ?, ?, 'default_avatar.png', 1)
            ");
            $stmt->execute([$fullName, $email, $passwordHash, $phone, $role]);
            $userId = (int)$pdo->lastInsertId();

            // If registering as a keeper, create the initial keeper profile
            if ($role === 'keeper') {
                $stmtKeeper = $pdo->prepare("
                    INSERT INTO keeper_profiles (user_id, bio, location, price_per_day, breeds_experienced, availability_status, years_experience, is_verified)
                    VALUES (?, 'Hello! I am a passionate pet keeper ready to care for your furry friends.', 'Colombo', 20.00, 'Dogs, Cats', 'available', 1, 0)
                ");
                $stmtKeeper->execute([$userId]);
            }

            $pdo->commit();

            // Auto-login user
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_name']  = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = $role;
            $_SESSION['user_photo'] = 'default_avatar.png';

            if ($role === 'keeper') {
                setFlash('success', 'Welcome to PetNest! Your keeper account has been created. Please complete your profile.');
                redirect('/keeper/profile.php');
            } else {
                setFlash('success', 'Welcome to PetNest! Your pet owner account is ready.');
                redirect('/owner/dashboard.php');
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 580px; padding: 40px 20px;">
    <div class="card">
        <div class="card-header" style="text-align: center; display: block;">
            <div style="font-size: 2.2rem; color: var(--primary); margin-bottom: 8px;">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2 class="card-title" style="font-size: 1.6rem;">Create Your PetNest Account</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">Join as a pet owner or trusted keeper</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="display: block;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 20px; font-size: 0.9rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register.php" method="POST" style="margin-top: 15px;">
            <!-- Role Selector Tabs -->
            <div class="form-group">
                <label class="form-label">I want to join PetNest as:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="role" value="owner" <?= $role === 'owner' ? 'checked' : '' ?> style="display: none;" onchange="this.parentElement.style.borderColor='var(--primary)'; document.getElementById('keeper-tab').style.borderColor='var(--border-color)';">
                        <div id="owner-tab" style="padding: 14px; text-align: center; border: 2px solid <?= $role === 'owner' ? 'var(--primary)' : 'var(--border-color)' ?>; border-radius: var(--radius-md); background: <?= $role === 'owner' ? 'var(--primary-light)' : '#FFF' ?>;">
                            <i class="fa-solid fa-paw" style="font-size: 1.3rem; color: var(--primary); display: block; margin-bottom: 6px;"></i>
                            <strong>Pet Owner</strong>
                            <small style="display: block; color: var(--text-muted); font-size: 0.75rem;">I need a keeper for my pet</small>
                        </div>
                    </label>

                    <label style="cursor: pointer;">
                        <input type="radio" name="role" value="keeper" <?= $role === 'keeper' ? 'checked' : '' ?> style="display: none;" onchange="this.parentElement.style.borderColor='var(--secondary)'; document.getElementById('owner-tab').style.borderColor='var(--border-color)';">
                        <div id="keeper-tab" style="padding: 14px; text-align: center; border: 2px solid <?= $role === 'keeper' ? 'var(--secondary)' : 'var(--border-color)' ?>; border-radius: var(--radius-md); background: <?= $role === 'keeper' ? 'var(--secondary-light)' : '#FFF' ?>;">
                            <i class="fa-solid fa-house-user" style="font-size: 1.3rem; color: var(--secondary); display: block; margin-bottom: 6px;"></i>
                            <strong>Pet Keeper</strong>
                            <small style="display: block; color: var(--text-muted); font-size: 0.75rem;">I want to host & board pets</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Full Name -->
            <div class="form-group">
                <label class="form-label" for="full_name"><i class="fa-solid fa-user"></i> Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($fullName) ?>" placeholder="e.g. John Doe" required>
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label class="form-label" for="email"><i class="fa-solid fa-envelope"></i> Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="e.g. name@example.com" required>
            </div>

            <!-- Phone Number -->
            <div class="form-group">
                <label class="form-label" for="phone"><i class="fa-solid fa-phone"></i> Contact Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" placeholder="e.g. 0771234567">
            </div>

            <!-- Password & Confirm -->
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="password"><i class="fa-solid fa-lock"></i> Password *</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password"><i class="fa-solid fa-lock"></i> Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.05rem; margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Complete Registration
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="<?= BASE_URL ?>/login.php" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>

<script>
// Role switcher dynamic UI styling
document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const ownerTab = document.getElementById('owner-tab');
        const keeperTab = document.getElementById('keeper-tab');
        if (radio.value === 'owner') {
            ownerTab.style.borderColor = 'var(--primary)';
            ownerTab.style.background = 'var(--primary-light)';
            keeperTab.style.borderColor = 'var(--border-color)';
            keeperTab.style.background = '#FFF';
        } else {
            keeperTab.style.borderColor = 'var(--secondary)';
            keeperTab.style.background = 'var(--secondary-light)';
            ownerTab.style.borderColor = 'var(--border-color)';
            ownerTab.style.background = '#FFF';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

