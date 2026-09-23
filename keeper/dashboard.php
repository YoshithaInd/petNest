<?php
/**
 * PetNest - Pet Keeper Dashboard
 * Member 2 Module
 */

$pageTitle = "Keeper Dashboard - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('keeper');

$keeperId = (int)$_SESSION['user_id'];
$keeperName = $_SESSION['user_name'] ?? 'Pet Keeper';

$pendingCount = 0;
$activeCount = 0;
$totalEarnings = 0.00;
$isVerified = 0;
$activeStays = [];
$pendingRequests = [];

try {
    // 1. Keeper verification status
    $stmt = $pdo->prepare("SELECT is_verified, availability_status FROM keeper_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$keeperId]);
    $kProfile = $stmt->fetch();
    $isVerified = (int)($kProfile['is_verified'] ?? 0);
    $availability = $kProfile['availability_status'] ?? 'available';

    // 2. Pending Requests count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE keeper_id = ? AND status = 'pending'");
    $stmt->execute([$keeperId]);
    $pendingCount = (int)$stmt->fetchColumn();

    // 3. Active Stays count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE keeper_id = ? AND status = 'active'");
    $stmt->execute([$keeperId]);
    $activeCount = (int)$stmt->fetchColumn();

    // 4. Total Earnings from Completed Bookings
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0.00) FROM bookings WHERE keeper_id = ? AND status = 'completed'");
    $stmt->execute([$keeperId]);
    $totalEarnings = (float)$stmt->fetchColumn();

    // 5. Active & Upcoming Confirmed Bookings
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.check_in_date, b.check_out_date, b.num_days, b.total_price, b.status,
               p.pet_name, p.species, p.breed, p.pet_photo, p.medical_notes,
               u.full_name AS owner_name, u.phone AS owner_phone
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.owner_id = u.user_id
        WHERE b.keeper_id = ? AND b.status IN ('active', 'confirmed')
        ORDER BY b.check_in_date ASC
    ");
    $stmt->execute([$keeperId]);
    $activeStays = $stmt->fetchAll();

    // 6. Recent Pending Requests
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.check_in_date, b.check_out_date, b.num_days, b.total_price,
               p.pet_name, p.species, p.breed,
               u.full_name AS owner_name
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.owner_id = u.user_id
        WHERE b.keeper_id = ? AND b.status = 'pending'
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$keeperId]);
    $pendingRequests = $stmt->fetchAll();

} catch (PDOException $e) {
    // Log exception
}
?>

<div class="container" style="padding: 30px 20px;">
    
    <!-- Operator Verification Banner -->
    <?php if ($isVerified === 0): ?>
        <div class="alert alert-warning" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fa-solid fa-clock" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Profile Pending Operator Verification:</strong>
                    Your keeper profile will be displayed in public search once approved by a PetNest operator.
                </div>
            </div>
            <a href="<?= BASE_URL ?>/keeper/profile.php" class="btn btn-primary btn-sm">Complete Profile</a>
        </div>
    <?php endif; ?>

    <!-- Welcome & Quick Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">Keeper Dashboard 🏡</h1>
            <p style="color: var(--text-muted);">Welcome back, <?= htmlspecialchars($keeperName) ?>. Here is your pet boarding overview.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/keeper/booking_requests.php" class="btn btn-primary">
                <i class="fa-solid fa-bell"></i> View Requests (<?= $pendingCount ?>)
            </a>
            <a href="<?= BASE_URL ?>/keeper/profile.php" class="btn btn-outline">
                <i class="fa-solid fa-gear"></i> Edit Profile
            </a>
            <a href="<?= BASE_URL ?>/keeper/send_alert.php" class="btn btn-danger btn-sm" style="display: flex; align-items: center;">
                <i class="fa-solid fa-triangle-exclamation"></i> Emergency Alert
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid-3" style="margin-bottom: 35px;">
        <div class="card" style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Pending Requests</span>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--text-dark);"><?= $pendingCount ?></div>
            </div>
        </div>

        <div class="card" style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--secondary-light); color: var(--secondary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-house-chimney-paw"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Active Boarding Stays</span>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--text-dark);"><?= $activeCount ?></div>
            </div>
        </div>

        <div class="card" style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Completed Earnings</span>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--secondary);">$<?= number_format($totalEarnings, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Active Stays & Pending Requests -->
    <div class="grid-2">
        <!-- Active & Confirmed Boarding Stays -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-paw"></i> Active & Confirmed Stays</h3>
            </div>

            <?php if (empty($activeStays)): ?>
                <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                    <i class="fa-solid fa-bed" style="font-size: 2.5rem; margin-bottom: 10px; color: var(--border-color);"></i>
                    <p>No active boarding stays right now.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($activeStays as $stay): ?>
                        <div style="padding: 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #FAF7F2;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong style="font-size: 1.1rem; color: var(--primary);">
                                    <?= htmlspecialchars($stay['pet_name']) ?> (<?= htmlspecialchars($stay['species']) ?>)
                                </strong>
                                <span class="badge badge-<?= htmlspecialchars($stay['status']) ?>"><?= htmlspecialchars($stay['status']) ?></span>
                            </div>

                            <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 8px;">
                                <div><strong>Owner:</strong> <?= htmlspecialchars($stay['owner_name']) ?> &bull; <strong>Phone:</strong> <?= htmlspecialchars($stay['owner_phone'] ?: 'N/A') ?></div>
                                <div><strong>Dates:</strong> <?= htmlspecialchars($stay['check_in_date']) ?> to <?= htmlspecialchars($stay['check_out_date']) ?> (<?= $stay['num_days'] ?> days)</div>
                            </div>

                            <?php if (!empty($stay['medical_notes'])): ?>
                                <div style="background: #FFF; padding: 8px 12px; border-radius: var(--radius-sm); font-size: 0.82rem; color: var(--text-dark); margin-bottom: 10px; border-left: 3px solid var(--accent);">
                                    <strong>Care Alert:</strong> <?= htmlspecialchars($stay['medical_notes']) ?>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 10px;">
                                <?php if ($stay['status'] === 'confirmed'): ?>
                                    <form action="<?= BASE_URL ?>/keeper/booking_requests.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $stay['booking_id'] ?>">
                                        <input type="hidden" name="action" value="mark_active">
                                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-play"></i> Mark Pet Dropped Off (Active)</button>
                                    </form>
                                <?php elseif ($stay['status'] === 'active'): ?>
                                    <form action="<?= BASE_URL ?>/keeper/booking_requests.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $stay['booking_id'] ?>">
                                        <input type="hidden" name="action" value="mark_completed">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check-double"></i> Mark Pet Picked Up (Completed)</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Incoming Requests -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-inbox"></i> Incoming Requests</h3>
                <a href="<?= BASE_URL ?>/keeper/booking_requests.php" class="btn btn-outline btn-sm">Manage All</a>
            </div>

            <?php if (empty($pendingRequests)): ?>
                <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                    <i class="fa-regular fa-bell-slash" style="font-size: 2.5rem; margin-bottom: 10px; color: var(--border-color);"></i>
                    <p>No new pending booking requests.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($pendingRequests as $req): ?>
                        <div style="padding: 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                                <strong><?= htmlspecialchars($req['pet_name']) ?> (<?= htmlspecialchars($req['breed']) ?>)</strong>
                                <strong style="color: var(--secondary);">$<?= number_format($req['total_price'], 2) ?></strong>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">
                                Requested by <?= htmlspecialchars($req['owner_name']) ?> for <?= htmlspecialchars($req['check_in_date']) ?> to <?= htmlspecialchars($req['check_out_date']) ?> (<?= $req['num_days'] ?> days)
                            </div>
                            <a href="<?= BASE_URL ?>/keeper/booking_requests.php" class="btn btn-primary btn-sm" style="width: 100%;">
                                Review & Confirm
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
