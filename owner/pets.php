<?php
/**
 * PetNest - Manage Pets
 * Purpose: Display and manage pet profiles owned by the current user.
 * Scope: Member 1
 */
$pageTitle = "My Pets - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$ownerId = (int)$_SESSION['user_id'];
$pets = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE owner_id = ? ORDER BY created_at DESC");
    $stmt->execute([$ownerId]);
    $pets = $stmt->fetchAll();
} catch (PDOException $e) {
    $pets = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">My Registered Pets 🐾</h1>
            <p style="color: var(--text-muted);">Manage your pets' health notes, care instructions, and boarding profiles.</p>
        </div>
        <a href="<?= BASE_URL ?>/owner/add_pet.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add New Pet
        </a>
    </div>

    <?php if (empty($pets)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fa-solid fa-paw" style="font-size: 3.5rem; color: var(--border-color); margin-bottom: 16px;"></i>
            <h2 style="font-size: 1.4rem; color: var(--text-dark); margin-bottom: 8px;">No Pets Added Yet</h2>
            <p style="color: var(--text-muted); max-width: 460px; margin: 0 auto 20px;">
                Register your dog, cat, or other pet so you can book trusted keepers and provide specific care routines.
            </p>
            <a href="<?= BASE_URL ?>/owner/add_pet.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add Your First Pet
            </a>
        </div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($pets as $pet): ?>
                <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
                    <!-- Pet Image -->
                    <div style="height: 190px; overflow: hidden; position: relative; background: #EEE;">
                        <img src="<?= BASE_URL ?>/uploads/pets/<?= htmlspecialchars($pet['pet_photo']) ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=400&h=300&fit=crop'"
                             alt="<?= htmlspecialchars($pet['pet_name']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                        <span class="badge" style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.65); color: #FFF;">
                            <?= htmlspecialchars($pet['species']) ?>
                        </span>
                    </div>

                    <!-- Pet Details -->
                    <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                            <h3 style="font-size: 1.3rem; color: var(--primary);"><?= htmlspecialchars($pet['pet_name']) ?></h3>
                            <span style="font-size: 0.85rem; color: var(--text-muted);"><?= $pet['age'] ?> <?= $pet['age'] == 1 ? 'yr' : 'yrs' ?> old</span>
                        </div>

                        <div style="font-size: 0.9rem; color: var(--text-dark); margin-bottom: 12px;">
                            <strong>Breed:</strong> <?= htmlspecialchars($pet['breed']) ?>
                            <?php if (!empty($pet['weight'])): ?>
                                &bull; <strong>Weight:</strong> <?= number_format($pet['weight'], 1) ?> kg
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($pet['medical_notes'])): ?>
                            <div style="background: var(--primary-light); padding: 10px 12px; border-radius: var(--radius-sm); font-size: 0.85rem; color: var(--primary); margin-bottom: 15px; border-left: 3px solid var(--primary);">
                                <strong><i class="fa-solid fa-notes-medical"></i> Medical / Care Notes:</strong><br>
                                <?= nl2br(htmlspecialchars($pet['medical_notes'])) ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 0.85rem; color: var(--text-muted); font-style: italic; margin-bottom: 15px;">No medical notes provided.</p>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div style="margin-top: auto; display: flex; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                            <a href="<?= BASE_URL ?>/search.php?pet_id=<?= $pet['pet_id'] ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                                <i class="fa-solid fa-calendar-plus"></i> Book Stay
                            </a>
                            <a href="<?= BASE_URL ?>/owner/edit_pet.php?id=<?= $pet['pet_id'] ?>" class="btn btn-outline btn-sm" title="Edit Pet">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/owner/delete_pet.php?id=<?= $pet['pet_id'] ?>"
                               class="btn btn-danger btn-sm"
                               title="Delete Pet"
                               data-confirm="Are you sure you want to delete <?= htmlspecialchars($pet['pet_name']) ?>? This action cannot be undone.">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
