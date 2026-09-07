<?php
/**
 * PetNest - Pet Owner Bookings Tracker
 * Member 3 Module
 */

$pageTitle = "My Bookings - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$ownerId = (int)$_SESSION['user_id'];

// Handle Booking Cancellation (Only allowed if status is 'pending')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancelId = (int)$_POST['cancel_booking_id'];
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND owner_id = ? AND status = 'pending'");
        $stmt->execute([$cancelId, $ownerId]);
        if ($stmt->rowCount() > 0) {
            setFlash('info', 'Your booking request has been cancelled.');
        } else {
            setFlash('warning', 'Only pending booking requests can be cancelled.');
        }
    } catch (PDOException $e) {
        setFlash('danger', 'Cancellation error: ' . $e->getMessage());
    }
    redirect('/owner/my_bookings.php');
}

// Fetch all bookings for logged-in owner
$bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.pet_name, p.species, p.breed, p.pet_photo,
               u.full_name AS keeper_name, u.phone AS keeper_phone, u.email AS keeper_email, u.profile_photo AS keeper_photo,
               i.feeding_schedule, i.bath_schedule, i.medicine_details, i.special_notes,
               pay.payment_id, pay.payment_status, pay.payment_method,
               r.rating_id, r.stars
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.keeper_id = u.user_id
        LEFT JOIN instructions i ON b.booking_id = i.booking_id
        LEFT JOIN payments pay ON b.booking_id = pay.booking_id
        LEFT JOIN ratings r ON b.booking_id = r.booking_id
        WHERE b.owner_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$ownerId]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">My Pet Bookings 📅</h1>
            <p style="color: var(--text-muted);">Track stay approvals, make payments, and review completed boardings.</p>
        </div>
        <a href="<?= BASE_URL ?>/search.php" class="btn btn-primary">
            <i class="fa-solid fa-magnifying-glass"></i> Book a New Stay
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fa-regular fa-calendar-check" style="font-size: 3.5rem; color: var(--border-color); margin-bottom: 16px;"></i>
            <h2 style="font-size: 1.3rem; margin-bottom: 8px;">No Bookings Found</h2>
            <p style="color: var(--text-muted); max-width: 440px; margin: 0 auto 20px;">
                You haven't requested any pet boarding stays yet. Search for verified keepers and book your pet's first stay!
            </p>
            <a href="<?= BASE_URL ?>/search.php" class="btn btn-primary">Find a Pet Keeper</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($bookings as $bk): ?>
                <div class="card" style="border-left: 5px solid <?= $bk['status'] === 'confirmed' ? 'var(--secondary)' : ($bk['status'] === 'active' ? 'var(--primary)' : ($bk['status'] === 'pending' ? 'var(--accent)' : 'var(--border-color)')) ?>;">
                    
                    <!-- Header Bar -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 14px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Booking #<?= $bk['booking_id'] ?> &bull; Booked on <?= date('M d, Y', strtotime($bk['created_at'])) ?></span>
                            <h3 style="font-size: 1.3rem; color: var(--primary); margin-top: 2px;">
                                <?= htmlspecialchars($bk['pet_name']) ?> with <?= htmlspecialchars($bk['keeper_name']) ?>
                            </h3>
                        </div>

                        <div style="text-align: right;">
                            <span class="badge badge-<?= htmlspecialchars($bk['status']) ?>" style="font-size: 0.85rem;">
                                <?= ucfirst(htmlspecialchars($bk['status'])) ?>
                            </span>
                            <div style="font-size: 1.3rem; font-weight: 800; color: var(--secondary); margin-top: 4px;">
                                Total: $<?= number_format($bk['total_price'], 2) ?>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Column Info -->
                    <div class="grid-3" style="margin-bottom: 16px;">
                        <!-- Keeper Details -->
                        <div>
                            <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 8px;"><i class="fa-solid fa-user-shield"></i> Keeper Contact</h4>
                            <div style="font-size: 0.88rem; line-height: 1.6; color: var(--text-dark);">
                                <div><strong>Name:</strong> <?= htmlspecialchars($bk['keeper_name']) ?></div>
                                <div><strong>Phone:</strong> <?= htmlspecialchars($bk['keeper_phone'] ?: 'Available after confirmation') ?></div>
                                <div><strong>Email:</strong> <?= htmlspecialchars($bk['keeper_email']) ?></div>
                            </div>
                        </div>

                        <!-- Stay Schedule -->
                        <div>
                            <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 8px;"><i class="fa-regular fa-calendar-days"></i> Dates & Duration</h4>
                            <div style="font-size: 0.88rem; line-height: 1.6; color: var(--text-dark);">
                                <div><strong>Check-In:</strong> <?= htmlspecialchars($bk['check_in_date']) ?></div>
                                <div><strong>Check-Out:</strong> <?= htmlspecialchars($bk['check_out_date']) ?></div>
                                <div><strong>Duration:</strong> <?= $bk['num_days'] ?> Days</div>
                            </div>
                        </div>

                        <!-- Payment & Notes -->
                        <div>
                            <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 8px;"><i class="fa-solid fa-credit-card"></i> Payment Status</h4>
                            <div style="font-size: 0.88rem; line-height: 1.6;">
                                <?php if (!empty($bk['payment_status']) && $bk['payment_status'] === 'paid'): ?>
                                    <span class="badge badge-confirmed"><i class="fa-solid fa-check"></i> Paid in Full</span>
                                <?php elseif ($bk['status'] === 'confirmed'): ?>
                                    <span class="badge badge-pending"><i class="fa-solid fa-clock"></i> Payment Due</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#EEE; color:#666;">Awaiting Acceptance</span>
                                <?php endif; ?>

                                <?php if (!empty($bk['keeper_notes'])): ?>
                                    <div style="margin-top: 8px; font-size: 0.82rem; color: var(--primary);">
                                        <strong>Keeper Note:</strong> "<?= htmlspecialchars($bk['keeper_notes']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 14px; flex-wrap: wrap;">
                        
                        <!-- Pay Now button if Confirmed and Not Yet Paid -->
                        <?php if ($bk['status'] === 'confirmed' && (empty($bk['payment_status']) || $bk['payment_status'] !== 'paid')): ?>
                            <a href="<?= BASE_URL ?>/payment/checkout.php?booking_id=<?= $bk['booking_id'] ?>" class="btn btn-primary" style="font-size: 1rem; padding: 10px 24px;">
                                <i class="fa-solid fa-lock"></i> Pay Now ($<?= number_format($bk['total_price'], 2) ?>)
                            </a>
                        <?php endif; ?>

                        <!-- Cancel Request if Pending -->
                        <?php if ($bk['status'] === 'pending'): ?>
                            <form action="<?= BASE_URL ?>/owner/my_bookings.php" method="POST" style="display:inline;">
                                <input type="hidden" name="cancel_booking_id" value="<?= $bk['booking_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" data-confirm="Are you sure you want to cancel this booking request?">
                                    <i class="fa-solid fa-ban"></i> Cancel Request
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- View Invoice / Receipt if Paid -->
                        <?php if (!empty($bk['payment_status']) && $bk['payment_status'] === 'paid'): ?>
                            <a href="<?= BASE_URL ?>/payment/receipt.php?booking_id=<?= $bk['booking_id'] ?>" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-receipt"></i> View Invoice
                            </a>
                        <?php endif; ?>

                        <!-- Rate Keeper if Completed -->
                        <?php if ($bk['status'] === 'completed'): ?>
                            <?php if (!empty($bk['rating_id'])): ?>
                                <span class="badge" style="background: #FEF3C7; color: #92400E; padding: 8px 12px; font-size: 0.85rem;">
                                    Rated: <?= str_repeat('★', (int)$bk['stars']) ?>
                                </span>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/owner/rate_keeper.php?booking_id=<?= $bk['booking_id'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-star"></i> Rate & Review Keeper
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
