<?php
/**
 * PetNest - Keeper Profile Editor
 * Member 2 Module
 */

$pageTitle = "My Keeper Profile - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('keeper');

$userId = (int)$_SESSION['user_id'];
$errors = [];

// Fetch current user and keeper profile
try {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.email, u.phone, u.profile_photo,
               kp.profile_id, kp.bio, kp.location, kp.price_per_day,
               kp.breeds_experienced, kp.availability_status, kp.years_experience, kp.is_verified
        FROM users u
        LEFT JOIN keeper_profiles kp ON u.user_id = kp.user_id
        WHERE u.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $keeper = $stmt->fetch();

    if (!$keeper) {
        setFlash('danger', 'User account not found.');
        redirect('/login.php');
    }

    // If profile row doesn't exist yet, create one
    if (!$keeper['profile_id']) {
        $initStmt = $pdo->prepare("
            INSERT INTO keeper_profiles (user_id, bio, location, price_per_day, breeds_experienced, availability_status, years_experience, is_verified)
            VALUES (?, 'Hello! I am ready to host your pets.', 'Colombo', 20.00, 'Dogs, Cats', 'available', 1, 0)
        ");
        $initStmt->execute([$userId]);
        redirect('/keeper/profile.php');
    }
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    redirect('/keeper/dashboard.php');
}

$fullName           = $keeper['full_name'];
$phone              = $keeper['phone'];
$profilePhoto       = $keeper['profile_photo'];
$bio                = $keeper['bio'];
$location           = $keeper['location'];
$pricePerDay        = $keeper['price_per_day'];
$breedsExperienced  = $keeper['breeds_experienced'];
$availabilityStatus = $keeper['availability_status'];
$yearsExperience    = $keeper['years_experience'];
$isVerified         = (int)$keeper['is_verified'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName           = trim($_POST['full_name'] ?? '');
    $phone              = trim($_POST['phone'] ?? '');
    $bio                = trim($_POST['bio'] ?? '');
    $location           = trim($_POST['location'] ?? '');
    $pricePerDay        = (float)($_POST['price_per_day'] ?? 20.00);
    $breedsExperienced  = trim($_POST['breeds_experienced'] ?? '');
    $availabilityStatus = in_array($_POST['availability_status'] ?? '', ['available', 'unavailable'], true) ? $_POST['availability_status'] : 'available';
    $yearsExperience    = (int)($_POST['years_experience'] ?? 0);

    // Validation
    if (empty($fullName)) {
        $errors[] = "Full name is required.";
    }
    if (empty($location)) {
        $errors[] = "City / Location is required.";
    }
    if ($pricePerDay <= 0) {
        $errors[] = "Daily boarding rate must be greater than $0.00.";
    }
    if ($yearsExperience < 0 || $yearsExperience > 50) {
        $errors[] = "Please provide a valid number of years of experience.";
    }

    // Photo Upload Handling
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $fileSize    = $_FILES['profile_photo']['size'];
        $fileType    = mime_content_type($fileTmpPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

        if (!in_array($fileType, $allowedMimes, true)) {
            $errors[] = "Only JPEG, PNG, or WebP images are allowed for profile photos.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Profile photo size must not exceed 5 MB.";
        } else {
            $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $newPhotoFileName = 'keeper_' . $userId . '_' . time() . '.' . strtolower($extension);
            $destination = UPLOAD_PATH_PROFILES . $newPhotoFileName;

            if (!is_dir(UPLOAD_PATH_PROFILES)) {
                mkdir(UPLOAD_PATH_PROFILES, 0777, true);
            }

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $profilePhoto = $newPhotoFileName;
                $_SESSION['user_photo'] = $newPhotoFileName;
            } else {
                $errors[] = "Failed to save uploaded profile photo.";
            }
        }
    }

    // Update Database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Update users table
            $stmtUser = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, profile_photo = ? WHERE user_id = ?");
            $stmtUser->execute([$fullName, $phone, $profilePhoto, $userId]);
            $_SESSION['user_name'] = $fullName;

            // 2. Update keeper_profiles table
            $stmtProfile = $pdo->prepare("
                UPDATE keeper_profiles
                SET bio = ?, location = ?, price_per_day = ?, breeds_experienced = ?, availability_status = ?, years_experience = ?
                WHERE user_id = ?
            ");
            $stmtProfile->execute([$bio, $location, $pricePerDay, $breedsExperienced, $availabilityStatus, $yearsExperience, $userId]);

            $pdo->commit();

            setFlash('success', 'Your keeper profile has been updated successfully!');
            redirect('/keeper/profile.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 760px; padding: 35px 20px;">
    <!-- Verification Alert Banner -->
    <?php if ($isVerified === 1): ?>
        <div class="alert alert-success" style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
            <span><strong>Verified Pet Keeper:</strong> Your profile is verified by an operator and visible in public search.</span>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-clock" style="font-size: 1.2rem;"></i>
            <span><strong>Pending Operator Verification:</strong> An operator will review and verify your profile soon. You can still set up your bio, rates, and breeds.</span>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-id-card"></i> Manage Keeper Profile</h2>
            <a href="<?= BASE_URL ?>/keeper_profile.php?id=<?= $userId ?>" class="btn btn-outline btn-sm" target="_blank">
                <i class="fa-solid fa-eye"></i> View Public Listing
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="display: block;">
                <strong>Please fix the following issues:</strong>
                <ul style="margin: 8px 0 0 20px; font-size: 0.9rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/keeper/profile.php" method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
            
            <!-- Photo & Basic Info -->
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap;">
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($profilePhoto) ?>" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=6B4226&color=fff&size=100'" 
                     alt="Profile Avatar" 
                     style="width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary);">
                <div style="flex: 1; min-width: 220px;">
                    <label class="form-label" for="profile_photo"><i class="fa-solid fa-camera"></i> Change Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small style="color: var(--text-muted); font-size: 0.8rem;">Max 5MB (JPEG, PNG, WebP)</small>
                </div>
            </div>

            <!-- Name, Phone, Email -->
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($fullName) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="e.g. 0771234567">
                </div>
            </div>

            <!-- Location & Price & Experience -->
            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="location">City / Location *</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($location) ?>" placeholder="e.g. Colombo" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="price_per_day">Daily Rate ($) *</label>
                    <input type="number" step="0.5" id="price_per_day" name="price_per_day" class="form-control" value="<?= htmlspecialchars((string)$pricePerDay) ?>" required min="1">
                </div>

                <div class="form-group">
                    <label class="form-label" for="years_experience">Experience (Years) *</label>
                    <input type="number" id="years_experience" name="years_experience" class="form-control" value="<?= htmlspecialchars((string)$yearsExperience) ?>" required min="0" max="50">
                </div>
            </div>

            <!-- Availability Toggle -->
            <div class="form-group">
                <label class="form-label">Availability Status</label>
                <div style="display: flex; gap: 20px; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="availability_status" value="available" <?= $availabilityStatus === 'available' ? 'checked' : '' ?>>
                        <span class="badge badge-available" style="padding: 6px 12px; font-size: 0.9rem;">
                            <i class="fa-solid fa-circle-check"></i> Available (Accepting Bookings)
                        </span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="availability_status" value="unavailable" <?= $availabilityStatus === 'unavailable' ? 'checked' : '' ?>>
                        <span class="badge badge-unavailable" style="padding: 6px 12px; font-size: 0.9rem;">
                            <i class="fa-solid fa-circle-xmark"></i> Unavailable (Paused)
                        </span>
                    </label>
                </div>
            </div>

            <!-- Breeds Experienced With -->
            <div class="form-group">
                <label class="form-label" for="breeds_experienced">Breeds & Animals Experienced With</label>
                <input type="text" id="breeds_experienced" name="breeds_experienced" class="form-control" value="<?= htmlspecialchars($breedsExperienced ?? '') ?>" placeholder="e.g. Golden Retriever, Labrador, Persian Cats, Senior Dogs">
                <small style="color: var(--text-muted); font-size: 0.8rem;">Separate breed names with commas so owners can find you easily in search.</small>
            </div>

            <!-- Bio -->
            <div class="form-group">
                <label class="form-label" for="bio">Bio & Home Environment Description</label>
                <textarea id="bio" name="bio" rows="4" class="form-control" placeholder="Describe your home, backyard, experience with pets, daily walking schedule, and love for animals..."><?= htmlspecialchars($bio ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px;">
                    <i class="fa-solid fa-floppy-disk"></i> Update Keeper Profile
                </button>
                <a href="<?= BASE_URL ?>/keeper/dashboard.php" class="btn btn-outline" style="padding: 12px 20px;">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
