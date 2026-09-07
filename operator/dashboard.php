
<?php
/**
 * PetNest - Operator Dashboard
 * Member 4 Module
 */

$pageTitle = "Operator Dashboard - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('operator');

$pendingVerificationsCount = 0;
$activeAlertsCount = 0;
$activeBookingsCount = 0;
$pendingKeepers = [];
$activeAlerts = [];

try {
    // 1. Unverified Keepers
    $stmt = $pdo->query("SELECT COUNT(*) FROM keeper_profiles WHERE is_verified = 0");
    $pendingVerificationsCount = (int)$stmt->fetchColumn();

    // 2. Unresolved Alerts
    $stmt = $pdo->query("SELECT COUNT(*) FROM emergency_alerts WHERE is_resolved = 0");
    $activeAlertsCount = (int)$stmt->fetchColumn();

    // 3. Active Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'");
    $activeBookingsCount = (int)$stmt->fetchColumn();

    // 4. Pending Keepers list preview
    $stmt = $pdo->query("
        SELECT kp.profile_id, kp.user_id, kp.location, kp.price_per_day, kp.years_experience, kp.breeds_experienced,
               u.full_name, u.email, u.phone, u.profile_photo, u.created_at
        FROM keeper_profiles kp
        JOIN users u ON kp.user_id = u.user_id
        WHERE kp.is_verified = 0
        ORDER BY u.created_at DESC
        LIMIT 4
    ");
    $pendingKeepers = $stmt->fetchAll();

    // 5. Active Alerts list preview
    $stmt = $pdo->query("
        SELECT ea.*, 
               p.pet_name, 
               u_keeper.full_name AS keeper_name, u_keeper.phone AS keeper_phone,
               u_owner.full_name AS owner_name, u_owner.phone AS owner_phone
        FROM emergency_alerts ea
        JOIN bookings b ON ea.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u_keeper ON ea.keeper_id = u_keeper.user_id
        JOIN users u_owner ON ea.owner_id = u_owner.user_id
        WHERE ea.is_resolved = 0
        ORDER BY ea.sent_at DESC
        LIMIT 4
    ");
    $activeAlerts = $stmt->fetchAll();

} catch (PDOException $e) {
    // Log exception
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">Operator Support Center 🎧</h1>
            <p style="color: var(--text-muted);">Manage keeper background verifications, monitor active stays, and handle emergency alerts.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>/operator/verify_keepers.php" class="btn btn-primary"><i class="fa-solid fa-user-check"></i> Verification Queue (<?= $pendingVerificationsCount ?>)</a>
            <a href="<?= BASE_URL ?>/operator/alerts.php" class="btn btn-danger btn-sm" style="display: flex; align-items: center;"><i class="fa-solid fa-triangle-exclamation"></i> Alerts Desk (<?= $activeAlertsCount ?>)</a>
        </div>
    </div>

    <!-- 3 Stat Cards -->
    <div class="grid-3" style="margin-bottom: 35px;">
        <div class="card" style="border-left: 4px solid <?= $pendingVerificationsCount > 0 ? 'var(--accent)' : 'var(--secondary)' ?>;">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Pending Keepers</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin: 4px 0;"><?= $pendingVerificationsCount ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;">Awaiting verification</small>
        </div>

        <div class="card" style="border-left: 4px solid <?= $activeAlertsCount > 0 ? '#DC2626' : 'var(--secondary)' ?>;">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Active Emergencies</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #DC2626; margin: 4px 0;"><?= $activeAlertsCount ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;">Unresolved urgent alerts</small>
        </div>

        <div class="card" style="border-left: 4px solid var(--primary);">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Active Boardings</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin: 4px 0;"><?= $activeBookingsCount ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;">Pets currently in care</small>
        </div>
    </div>

    <!-- 2 Grids: Pending Verifications + Active Alerts -->
    <div class="grid-2">
        <!-- Pending Keeper Verifications -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-user-clock"></i> New Keeper Verifications</h3>
                <a href="<?= BASE_URL ?>/operator/verify_keepers.php" class="btn btn-outline btn-sm">Queue</a>
            </div>

            <?php if (empty($pendingKeepers)): ?>
                <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                    <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; color: var(--secondary); margin-bottom: 10px;"></i>
                    <p>All pet keeper profiles are verified!</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($pendingKeepers as $pk): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); flex-wrap: wrap; gap: 10px;">
                            <div>
                                <strong><?= htmlspecialchars($pk['full_name']) ?></strong> (<?= htmlspecialchars($pk['location']) ?>)<br>
                                <small style="color: var(--text-muted);"><?= $pk['years_experience'] ?> yrs exp. &bull; $<?= number_format($pk['price_per_day'], 2) ?>/day</small>
                            </div>
                            <form action="<?= BASE_URL ?>/operator/verify_keepers.php" method="POST">
                                <input type="hidden" name="user_id" value="<?= $pk['user_id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-check"></i> Verify</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Alerts Desk -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-bell"></i> Live Emergency Alerts</h3>
                <a href="<?= BASE_URL ?>/operator/alerts.php" class="btn btn-outline btn-sm">View All</a>
            </div>

            <?php if (empty($activeAlerts)): ?>
                <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                    <i class="fa-solid fa-shield-cat" style="font-size: 2.5rem; color: var(--secondary); margin-bottom: 10px;"></i>
                    <p>No active emergencies. All stays normal.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($activeAlerts as $al): ?>
                        <div style="padding: 12px; border: 1px solid #FECACA; background: #FEF2F2; border-radius: var(--radius-md);">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                                <strong style="color: #991B1B;">Alert: <?= htmlspecialchars($al['pet_name']) ?></strong>
                                <small style="color: #991B1B;"><?= htmlspecialchars($al['sent_at']) ?></small>
                            </div>
                            <p style="font-size: 0.85rem; color: #7F1D1D; margin-bottom: 8px;">"<?= htmlspecialchars($al['message']) ?>"</p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #991B1B;">
                                <span>Keeper: <?= htmlspecialchars($al['keeper_name']) ?> (<?= htmlspecialchars($al['keeper_phone'] ?: 'N/A') ?>)</span>
                                <form action="<?= BASE_URL ?>/operator/alerts.php" method="POST">
                                    <input type="hidden" name="alert_id" value="<?= $al['alert_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 4px 8px;">Mark Resolved</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
