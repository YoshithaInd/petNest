<?php
/**
 * PetNest - Add Pet
 * Purpose: Form and handler to register a new pet profile.
 * Scope: Member 1
 */
$pageTitle = "Add New Pet - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$ownerId = (int)$_SESSION['user_id'];
$errors = [];

$petName = '';
$species = 'Dog';
$breed = '';
$age = 1;
$weight = '';
$medicalNotes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $petName      = trim($_POST['pet_name'] ?? '');
    $species      = trim($_POST['species'] ?? 'Dog');
    $breed        = trim($_POST['breed'] ?? '');
    $age          = (int)($_POST['age'] ?? 1);
    $weight       = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $medicalNotes = trim($_POST['medical_notes'] ?? '');

    // Validations
    if (empty($petName)) {
        $errors[] = "Pet name is required.";
    }
    if (empty($breed)) {
        $errors[] = "Pet breed is required (e.g. Golden Retriever, Mixed).";
    }
    if ($age < 0 || $age > 30) {
        $errors[] = "Please provide a valid age between 0 and 30 years.";
    }

    // Photo Upload Handling
    $photoFileName = 'default_pet.png';
    if (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pet_photo']['tmp_name'];
        $fileSize    = $_FILES['pet_photo']['size'];
        $fileType    = mime_content_type($fileTmpPath);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

        if (!in_array($fileType, $allowedMimes, true)) {
            $errors[] = "Only JPEG, PNG, or WebP images are allowed for pet photos.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Pet photo size must not exceed 5 MB.";
        } else {
            // Generate unique filename
            $extension = pathinfo($_FILES['pet_photo']['name'], PATHINFO_EXTENSION);
            $photoFileName = 'pet_' . uniqid() . '_' . time() . '.' . strtolower($extension);
            $destination = UPLOAD_PATH_PETS . $photoFileName;

            if (!is_dir(UPLOAD_PATH_PETS)) {
                mkdir(UPLOAD_PATH_PETS, 0777, true);
            }

            if (!move_uploaded_file($fileTmpPath, $destination)) {
                $errors[] = "Failed to save uploaded pet photo. Please try again.";
                $photoFileName = 'default_pet.png';
            }
        }
    }

    // Insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO pets (owner_id, pet_name, breed, species, age, weight, pet_photo, medical_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$ownerId, $petName, $breed, $species, $age, $weight, $photoFileName, $medicalNotes]);

            setFlash('success', "Pet profile for '{$petName}' added successfully!");
            redirect('/owner/pets.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 650px; padding: 40px 20px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-plus-circle"></i> Add New Pet Profile</h2>
            <a href="<?= BASE_URL ?>/owner/pets.php" class="btn btn-outline btn-sm">Cancel</a>
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

        <form action="<?= BASE_URL ?>/owner/add_pet.php" method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
            <!-- Pet Name & Species -->
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="pet_name">Pet Name *</label>
                    <input type="text" id="pet_name" name="pet_name" class="form-control" value="<?= htmlspecialchars($petName) ?>" placeholder="e.g. Charlie" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="species">Species *</label>
                    <select id="species" name="species" class="form-select" required>
                        <option value="Dog" <?= $species === 'Dog' ? 'selected' : '' ?>>Dog 🐕</option>
                        <option value="Cat" <?= $species === 'Cat' ? 'selected' : '' ?>>Cat 🐈</option>
                        <option value="Bird" <?= $species === 'Bird' ? 'selected' : '' ?>>Bird 🦜</option>
                        <option value="Rabbit" <?= $species === 'Rabbit' ? 'selected' : '' ?>>Rabbit 🐇</option>
                        <option value="Other" <?= $species === 'Other' ? 'selected' : '' ?>>Other Pet</option>
                    </select>
                </div>
            </div>

            <!-- Breed & Age & Weight -->
            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="breed">Breed *</label>
                    <input type="text" id="breed" name="breed" class="form-control" value="<?= htmlspecialchars($breed) ?>" placeholder="e.g. Golden Retriever" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="age">Age (Years) *</label>
                    <input type="number" id="age" name="age" class="form-control" value="<?= htmlspecialchars((string)$age) ?>" min="0" max="30" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="weight">Weight (kg)</label>
                    <input type="number" step="0.1" id="weight" name="weight" class="form-control" value="<?= htmlspecialchars((string)$weight) ?>" placeholder="e.g. 15.5">
                </div>
            </div>

            <!-- Pet Photo Upload -->
            <div class="form-group">
                <label class="form-label" for="pet_photo"><i class="fa-solid fa-camera"></i> Pet Photo (JPEG, PNG, WebP up to 5MB)</label>
                <input type="file" id="pet_photo" name="pet_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small style="color: var(--text-muted); font-size: 0.8rem;">Leave blank to use a default avatar.</small>
            </div>

            <!-- Medical & Dietary Notes -->
            <div class="form-group">
                <label class="form-label" for="medical_notes"><i class="fa-solid fa-notes-medical"></i> Medical Notes, Allergies & Special Care</label>
                <textarea id="medical_notes" name="medical_notes" rows="4" class="form-control" placeholder="Any allergies, existing medical conditions, dietary restrictions, or vet clinic details..."><?= htmlspecialchars($medicalNotes) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px;">
                    <i class="fa-solid fa-check"></i> Save Pet Profile
                </button>
                <a href="<?= BASE_URL ?>/owner/pets.php" class="btn btn-outline" style="padding: 12px 20px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
