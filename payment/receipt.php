<?php
/**
 * PetNest - Digital Invoice & Payment Receipt
 * Member 3 Module
 */

$pageTitle = "Payment Receipt - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireAuth();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$userId    = (int)$_SESSION['user_id'];
$userRole  = $_SESSION['user_role'] ?? '';

if ($bookingId <= 0) {
    setFlash('danger', 'Invalid booking receipt requested.');
    redirect('/index.php');
}

// Fetch payment and booking details
try {
    $stmt = $pdo->prepare("
        SELECT pay.*, 
               b.check_in_date, b.check_out_date, b.num_days, b.base_price, b.surcharge, b.total_price, b.status AS booking_status,
               p.pet_name, p.species, p.breed,
               u_owner.full_name AS owner_name, u_owner.email AS owner_email, u_owner.phone AS owner_phone, u_owner.user_id AS owner_user_id,
               u_keeper.full_name AS keeper_name, u_keeper.email AS keeper_email, u_keeper.phone AS keeper_phone, u_keeper.user_id AS keeper_user_id,
               kp.location AS keeper_location, kp.price_per_day
        FROM payments pay
        JOIN bookings b ON pay.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u_owner ON b.owner_id = u_owner.user_id
        JOIN users u_keeper ON b.keeper_id = u_keeper.user_id
        JOIN keeper_profiles kp ON u_keeper.user_id = kp.user_id
        WHERE pay.booking_id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId]);
    $receipt = $stmt->fetch();

    if (!$receipt) {
        setFlash('warning', 'No payment record found for this booking.');
        redirect('/owner/my_bookings.php');
    }

    // Permission check: only owner, keeper, operator, or admin can view
    if ($userRole === 'owner' && (int)$receipt['owner_user_id'] !== $userId) {
        setFlash('danger', 'Access denied.');
        redirect('/owner/dashboard.php');
    }

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    redirect('/index.php');
}
?>

<div class="container" style="max-width: 750px; padding: 40px 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/owner/my_bookings.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to My Bookings
        </a>
        <button onclick="window.print();" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>
    </div>

    <!-- Official Invoice Card -->
    <div class="card" style="padding: 40px; border: 1px solid var(--border-color); box-shadow: var(--shadow-md); background: #FFF;">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; margin-bottom: 25px;">
            <div>
                <div class="brand-logo" style="margin-bottom: 4px;">
                    <i class="fa-solid fa-house-chimney-paw"></i> PetNest
                </div>
                <small style="color: var(--text-muted);">Where Pets Feel at Home &bull; Official Receipt</small>
            </div>
            <div style="text-align: right;">
                <h2 style="color: var(--secondary); font-size: 1.4rem; text-transform: uppercase; margin-bottom: 4px;">PAID INVOICE</h2>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    <div><strong>Receipt #:</strong> REC-<?= str_pad($receipt['payment_id'], 6, '0', STR_PAD_LEFT) ?></div>
                    <div><strong>Date:</strong> <?= date('F d, Y - h:i A', strtotime($receipt['payment_date'])) ?></div>
                    <div><strong>Transaction ID:</strong> <code><?= htmlspecialchars($receipt['transaction_id']) ?></code></div>
                </div>
            </div>
        </div>

        <!-- Payer & Keeper Details -->
        <div class="grid-2" style="margin-bottom: 25px; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Customer / Pet Owner:</span>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-dark); margin-top: 4px;"><?= htmlspecialchars($receipt['owner_name']) ?></div>
                <div style="color: var(--text-muted);"><?= htmlspecialchars($receipt['owner_email']) ?> &bull; <?= htmlspecialchars($receipt['owner_phone'] ?: 'N/A') ?></div>
            </div>

            <div>
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Service Provider / Keeper:</span>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-dark); margin-top: 4px;"><?= htmlspecialchars($receipt['keeper_name']) ?></div>
                <div style="color: var(--text-muted);"><?= htmlspecialchars($receipt['keeper_location']) ?> &bull; <?= htmlspecialchars($receipt['keeper_phone'] ?: 'N/A') ?></div>
            </div>
        </div>

        <!-- Itemized Table -->
        <div class="table-responsive" style="margin-bottom: 25px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center;">Duration</th>
                        <th style="text-align: right;">Rate / Unit</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Pet Boarding Service</strong><br>
                            <small style="color: var(--text-muted);">
                                Pet: <?= htmlspecialchars($receipt['pet_name']) ?> (<?= htmlspecialchars($receipt['species']) ?> - <?= htmlspecialchars($receipt['breed']) ?>)<br>
                                Stay: <?= htmlspecialchars($receipt['check_in_date']) ?> to <?= htmlspecialchars($receipt['check_out_date']) ?>
                            </small>
                        </td>
                        <td style="text-align: center;"><?= $receipt['num_days'] ?> Days</td>
                        <td style="text-align: right;">$<?= number_format($receipt['price_per_day'], 2) ?></td>
                        <td style="text-align: right;">$<?= number_format($receipt['base_price'], 2) ?></td>
                    </tr>
                    <?php if ($receipt['surcharge'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Special Medical & Daily Care Surcharge</strong><br>
                                <small style="color: var(--text-muted);">Custom medication and dietary handling</small>
                            </td>
                            <td style="text-align: center;">1 Flat Fee</td>
                            <td style="text-align: right;">$<?= number_format($receipt['surcharge'], 2) ?></td>
                            <td style="text-align: right;">$<?= number_format($receipt['surcharge'], 2) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals & Payment Method -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; border-top: 2px solid var(--border-color); padding-top: 18px;">
            <div>
                <span class="badge" style="background: var(--secondary-light); color: var(--secondary); padding: 8px 14px; font-size: 0.9rem;">
                    <i class="fa-solid fa-check-circle"></i> Paid via <?= strtoupper(htmlspecialchars($receipt['payment_method'])) ?>
                </span>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.9rem; color: var(--text-muted);">Grand Total Paid:</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--secondary);">$<?= number_format($receipt['amount'], 2) ?> USD</div>
            </div>
        </div>

        <div style="margin-top: 35px; border-top: 1px dashed var(--border-color); padding-top: 15px; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
            Thank you for using PetNest. Have questions? Contact support at support@petnest.com
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
