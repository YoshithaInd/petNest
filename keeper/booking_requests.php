<?php
/**
 * PetNest - Keeper Booking Requests Management
 * Member 2 Module
 */

$pageTitle = "Booking Requests - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('keeper');

$keeperId = (int)$_SESSION['user_id'];

// Handle Actions (Confirm / Reject / Mark Active / Mark Completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    $notes     = trim($_POST['keeper_notes'] ?? '');

    if ($bookingId > 0 && in_array($action, ['confirm', 'reject', 'mark_active', 'mark_completed'], true)) {
        try {
            // Check that this booking belongs to this keeper
            $stmt = $pdo->prepare("SELECT booking_id, status FROM bookings WHERE booking_id = ? AND keeper_id = ? LIMIT 1");
            $stmt->execute([$bookingId, $keeperId]);
            $bk = $stmt->fetch();

            if ($bk) {
                if ($action === 'confirm') {
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', keeper_notes = ? WHERE booking_id = ?");
                    $updateStmt->execute([$notes ?: 'Booking accepted. Looking forward to hosting your pet!', $bookingId]);
                    setFlash('success', 'Booking request confirmed successfully! The pet owner has been notified to proceed with payment.');
                } elseif ($action === 'reject') {
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'rejected', keeper_notes = ? WHERE booking_id = ?");
                    $updateStmt->execute([$notes ?: 'Unfortunately, I cannot accommodate this booking at this time.', $bookingId]);
                    setFlash('info', 'Booking request has been declined.');
                } elseif ($action === 'mark_active') {
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'active' WHERE booking_id = ?");
                    $updateStmt->execute([$bookingId]);
                    setFlash('success', 'Pet has been marked as dropped off (Stay is now Active).');
                } elseif ($action === 'mark_completed') {
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE booking_id = ?");
                    $updateStmt->execute([$bookingId]);
                    setFlash('success', 'Pet stay marked as Completed! The owner can now leave a rating and review.');
                }
            } else {
                setFlash('danger', 'Booking record not found or access denied.');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Operation failed: ' . $e->getMessage());
        }
    }
    redirect('/keeper/booking_requests.php');
}

// Fetch all bookings for this keeper
$allBookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.pet_name, p.species, p.breed, p.age AS pet_age, p.weight AS pet_weight, p.pet_photo, p.medical_notes,
               u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone,
               i.feeding_schedule, i.bath_schedule, i.medicine_details, i.special_notes, i.instruction_surcharge,
               pay.payment_status, pay.payment_method
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.owner_id = u.user_id
        LEFT JOIN instructions i ON b.booking_id = i.booking_id
        LEFT JOIN payments pay ON b.booking_id = pay.booking_id
        WHERE b.keeper_id = ?
        ORDER BY 
            CASE b.status
                WHEN 'pending' THEN 1
                WHEN 'confirmed' THEN 2
                WHEN 'active' THEN 3
                WHEN 'completed' THEN 4
                ELSE 5
            END,
            b.created_at DESC
    ");
    $stmt->execute([$keeperId]);
    $allBookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $allBookings = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">Booking Requests & Boarding Schedule 📋</h1>
            <p style="color: var(--text-muted);">Review pet details, special dietary instructions, and confirm or manage stays.</p>
        </div>
        <a href="<?= BASE_URL ?>/keeper/dashboard.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Keeper Dashboard
        </a>
    </div>

    <?php if (empty($allBookings)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fa-regular fa-calendar-xmark" style="font-size: 3.5rem; color: var(--border-color); margin-bottom: 16px;"></i>
            <h2 style="font-size: 1.3rem; color: var(--text-dark); margin-bottom: 8px;">No Booking Requests Yet</h2>
            <p style="color: var(--text-muted); max-width: 460px; margin: 0 auto 20px;">
                When pet owners search for keepers and select your profile, their booking requests and custom care instructions will appear here.
            </p>
            <a href="<?= BASE_URL ?>/keeper/profile.php" class="btn btn-primary">Make Sure Your Profile is Complete</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($allBookings as $bk): ?>
                <div class="card" style="border-left: 5px solid <?= $bk['status'] === 'pending' ? 'var(--accent)' : ($bk['status'] === 'active' ? 'var(--primary)' : ($bk['status'] === 'confirmed' ? 'var(--secondary)' : 'var(--border-color)')) ?>;">
                    
                    <!-- Booking Header -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Booking #<?= $bk['booking_id'] ?> &bull; Requested on <?= date('M d, Y', strtotime($bk['created_at'])) ?></span>
                            <h3 style="font-size: 1.3rem; color: var(--primary); margin-top: 2px;">
                                <?= htmlspecialchars($bk['pet_name']) ?> (<?= htmlspecialchars($bk['species']) ?> - <?= htmlspecialchars($bk['breed']) ?>)
                            </h3>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-<?= htmlspecialchars($bk['status']) ?>" style="font-size: 0.85rem;">
                                Status: <?= ucfirst(htmlspecialchars($bk['status'])) ?>
                            </span>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--secondary); margin-top: 4px;">
                                Total: $<?= number_format($bk['total_price'], 2) ?>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Column Content Details -->
                    <div class="grid-3" style="margin-bottom: 20px;">
                        
                        <!-- 1. Pet & Owner Info -->
                        <div>
                            <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 10px;"><i class="fa-solid fa-user"></i> Owner & Pet Details</h4>
                            <div style="font-size: 0.88rem; line-height: 1.6; color: var(--text-dark);">
                                <div><strong>Owner:</strong> <?= htmlspecialchars($bk['owner_name']) ?></div>
                                <div><strong>Phone:</strong> <?= htmlspecialchars($bk['owner_phone'] ?: 'N/A') ?></div>
                                <div><strong>Email:</strong> <?= htmlspecialchars($bk['owner_email']) ?></div>
                                <div style="margin-top: 6px;"><strong>Pet Age:</strong> <?= $bk['pet_age'] ?> yrs &bull; <strong>Weight:</strong> <?= $bk['pet_weight'] ? $bk['pet_weight'] . ' kg' : 'N/A' ?></div>
                            </div>
                        </div>

                        <!-- 2. Stay Dates & Pricing Breakdown -->
                        <div>
                            <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 10px;"><i class="fa-regular fa-calendar-days"></i> Stay Schedule</h4>
                            <div style="font-size: 0.88rem; line-height: 1.6; color: var(--text-dark);">
                                <div><strong>Check-In:</strong> <?= htmlspecialchars($bk['check_in_date']) ?></div>
                                <div><strong>Check-Out:</strong> <?= htmlspecialchars($bk['check_out_date']) ?></div>
                                <div><strong>Duration:</strong> <?= $bk['num_days'] ?> Days</div>
                                <div><strong>Base Price:</strong> $<?= number_format($bk['base_price'], 2) ?></div>
                                <?php if ($bk['surcharge'] > 0): ?>
                                    <div><strong>Care Surcharge:</strong> +$<?= number_format($bk['surcharge'], 2) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 3. Payment Status -->
                        <div>
                            <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 10px;"><i class="fa-solid fa-credit-card"></i> Payment Status</h4>
                            <div style="font-size: 0.88rem; line-height: 1.6;">
                                <?php if (!empty($bk['payment_status']) && $bk['payment_status'] === 'paid'): ?>
                                    <span class="badge" style="background: var(--secondary-light); color: var(--secondary);"><i class="fa-solid fa-check"></i> Paid via <?= strtoupper(htmlspecialchars($bk['payment_method'])) ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--accent-light); color: var(--accent);"><i class="fa-solid fa-clock"></i> Payment Pending</span>
                                <?php endif; ?>

                                <?php if (!empty($bk['keeper_notes'])): ?>
                                    <div style="margin-top: 10px; font-size: 0.82rem; color: var(--text-muted);">
                                        <strong>Your Note to Owner:</strong><br>
                                        "<?= htmlspecialchars($bk['keeper_notes']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Care Instructions Panel -->
                    <?php if (!empty($bk['feeding_schedule']) || !empty($bk['medicine_details']) || !empty($bk['bath_schedule']) || !empty($bk['special_notes'])): ?>
                        <div style="background: #F9F7F4; border-radius: var(--radius-md); padding: 16px; margin-bottom: 18px;">
                            <h4 style="font-size: 0.9rem; color: var(--primary); margin-bottom: 10px; text-transform: uppercase;">
                                <i class="fa-solid fa-clipboard-list"></i> Owner's Care Instructions:
                            </h4>
                            <div class="grid-2" style="font-size: 0.85rem;">
                                <?php if (!empty($bk['feeding_schedule'])): ?>
                                    <div><strong>🍽️ Feeding:</strong> <?= htmlspecialchars($bk['feeding_schedule']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($bk['bath_schedule'])): ?>
                                    <div><strong>🛁 Bathing:</strong> <?= htmlspecialchars($bk['bath_schedule']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($bk['medicine_details'])): ?>
                                    <div><strong>💊 Medicine:</strong> <?= htmlspecialchars($bk['medicine_details']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($bk['special_notes'])): ?>
                                    <div><strong>📝 Notes:</strong> <?= htmlspecialchars($bk['special_notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Keeper Action Buttons -->
                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 14px; flex-wrap: wrap;">
                        
                        <?php if ($bk['status'] === 'pending'): ?>
                            <form action="<?= BASE_URL ?>/keeper/booking_requests.php" method="POST" style="display: flex; gap: 10px; align-items: center; width: 100%; justify-content: flex-end; flex-wrap: wrap;">
                                <input type="hidden" name="booking_id" value="<?= $bk['booking_id'] ?>">
                                <input type="text" name="keeper_notes" class="form-control" style="max-width: 320px; font-size: 0.85rem;" placeholder="Optional note to owner (e.g. Please bring leash)">
                                
                                <button type="submit" name="action" value="confirm" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-check"></i> Accept Booking
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to decline this booking request?">
                                    <i class="fa-solid fa-xmark"></i> Decline
                                </button>
                            </form>

                        <?php elseif ($bk['status'] === 'confirmed'): ?>
                            <form action="<?= BASE_URL ?>/keeper/booking_requests.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?= $bk['booking_id'] ?>">
                                <input type="hidden" name="action" value="mark_active">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-play"></i> Mark Pet Dropped Off (Active)
                                </button>
                            </form>

                        <?php elseif ($bk['status'] === 'active'): ?>
                            <a href="<?= BASE_URL ?>/keeper/send_alert.php?booking_id=<?= $bk['booking_id'] ?>" class="btn btn-outline btn-sm" style="color:#DC2626; border-color:#DC2626;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Trigger Emergency Alert
                            </a>
                            <form action="<?= BASE_URL ?>/keeper/booking_requests.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?= $bk['booking_id'] ?>">
                                <input type="hidden" name="action" value="mark_completed">
                                <button type="submit" class="btn btn-primary btn-sm" data-confirm="Confirm that the pet has been picked up by the owner and the stay is complete?">
                                    <i class="fa-solid fa-check-double"></i> Mark Pet Picked Up (Complete)
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
