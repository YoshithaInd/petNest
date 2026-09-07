<?php
/**
 * PetNest - Send Alert
 * Purpose: Dispatch emergency or update alerts regarding pet stays to owners and operators.
 * Scope: Member 4
 */
<?php
/**
 * PetNest - Send Emergency Alert
 * Member 4 Module
 */

$pageTitle = "Trigger Emergency Alert - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('keeper');

$keeperId = (int)$_SESSION['user_id'];
$preselectedBookingId = (int)($_GET['booking_id'] ?? 0);

// Fetch active or confirmed bookings for this keeper
$activeBookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.owner_id, b.check_in_date, b.check_out_date,
               p.pet_name, p.species, p.breed,
               u.full_name AS owner_name, u.phone AS owner_phone
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.owner_id = u.user_id
        WHERE b.keeper_id = ? AND b.status IN ('active', 'confirmed')
        ORDER BY b.check_in_date ASC
    ");
    $stmt->execute([$keeperId]);
    $activeBookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $activeBookings = [];
}

$errors = [];
$message = '';
$selectedBookingId = $preselectedBookingId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedBookingId = (int)($_POST['booking_id'] ?? 0);
    $message           = trim($_POST['message'] ?? '');

    if ($selectedBookingId <= 0) {
        $errors[] = "Please select the active booking/pet for this alert.";
    }
    if (empty($message)) {
        $errors[] = "Please describe the emergency incident.";
    }

    if (empty($errors)) {
        try {
            // Find owner_id
            $stmt = $pdo->prepare("SELECT owner_id FROM bookings WHERE booking_id = ? AND keeper_id = ? LIMIT 1");
            $stmt->execute([$selectedBookingId, $keeperId]);
            $bk = $stmt->fetch();

            if ($bk) {
                $ownerId = (int)$bk['owner_id'];
                $stmtAlert = $pdo->prepare("
                    INSERT INTO emergency_alerts (booking_id, keeper_id, owner_id, message, is_resolved)
                    VALUES (?, ?, ?, ?, 0)
                ");
                $stmtAlert->execute([$selectedBookingId, $keeperId, $ownerId, $message]);

                setFlash('danger', 'Emergency alert broadcasted! The pet owner and platform operators have been notified immediately.');
                redirect('/keeper/dashboard.php');
            } else {
                $errors[] = "Invalid booking selected or access denied.";
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to broadcast alert: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 650px; padding: 40px 20px;">
    
    <div class="card" style="border: 2px solid #DC2626;">
        <div class="card-header" style="background: #FEF2F2; margin: -24px -24px 20px -24px; padding: 20px 24px; border-bottom: 2px solid #FECACA; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
            <h2 class="card-title" style="color: #DC2626; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Trigger Pet Emergency Alert
            </h2>
            <p style="color: #991B1B; font-size: 0.85rem; margin-top: 4px;">
                Use this form only for urgent medical issues, runaway incidents, or critical emergencies during a stay.
            </p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="display: block;">
                <strong>Please fix the following:</strong>
                <ul style="margin: 8px 0 0 20px; font-size: 0.9rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($activeBookings)): ?>
            <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                <i class="fa-solid fa-bed" style="font-size: 2.5rem; color: var(--border-color); margin-bottom: 10px;"></i>
                <p>You have no active or confirmed stays right now to send alerts for.</p>
                <a href="<?= BASE_URL ?>/keeper/dashboard.php" class="btn btn-outline btn-sm" style="margin-top: 10px;">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <form action="<?= BASE_URL ?>/keeper/send_alert.php" method="POST">
                
                <div class="form-group">
                    <label class="form-label" for="booking_id"><i class="fa-solid fa-paw"></i> Select Stay / Pet in Care *</label>
                    <select name="booking_id" id="booking_id" class="form-select" required>
                        <option value="">-- Choose active booking --</option>
                        <?php foreach ($activeBookings as $b): ?>
                            <option value="<?= $b['booking_id'] ?>" <?= $selectedBookingId === (int)$b['booking_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['pet_name']) ?> (Owner: <?= htmlspecialchars($b['owner_name']) ?> - Tel: <?= htmlspecialchars($b['owner_phone'] ?: 'N/A') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message"><i class="fa-solid fa-comment-medical"></i> Emergency Description *</label>
                    <textarea id="message" name="message" rows="5" class="form-control" placeholder="Clearly describe what happened (e.g. Pet injured paw in backyard, vomiting, fever, taken to City Vet Hospital at 0771234567)..." required><?= htmlspecialchars($message) ?></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 25px;">
                    <button type="submit" class="btn btn-danger btn-lg" style="flex: 1;" data-confirm="Broadcast this emergency alert to the owner and platform operators immediately?">
                        <i class="fa-solid fa-tower-broadcast"></i> Send Urgent Alert
                    </button>
                    <a href="<?= BASE_URL ?>/keeper/dashboard.php" class="btn btn-outline btn-lg">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
