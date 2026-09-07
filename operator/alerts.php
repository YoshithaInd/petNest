<?php
/**
 * PetNest - Alerts Monitoring
 * Purpose: Review and handle incoming platform alerts and emergency notifications.
 * Scope: Member 4
 */
<?php
/**
 * PetNest - Operator Emergency Alerts Monitoring Desk
 * Member 4 Module
 */

$pageTitle = "Emergency Alerts Monitor - Operator";
require_once __DIR__ . '/../includes/header.php';
requireRole(['operator', 'admin']);

// Handle Alert Resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_id'])) {
    $alertId = (int)$_POST['alert_id'];
    try {
        $stmt = $pdo->prepare("UPDATE emergency_alerts SET is_resolved = 1 WHERE alert_id = ?");
        $stmt->execute([$alertId]);
        setFlash('success', 'Emergency alert marked as resolved.');
    } catch (PDOException $e) {
        setFlash('danger', 'Failed to update alert: ' . $e->getMessage());
    }
    redirect('/operator/alerts.php');
}

// Fetch all emergency alerts
$alerts = [];
try {
    $stmt = $pdo->query("
        SELECT ea.*, 
               p.pet_name, p.species, p.breed, p.medical_notes,
               u_keeper.full_name AS keeper_name, u_keeper.phone AS keeper_phone, u_keeper.email AS keeper_email,
               u_owner.full_name AS owner_name, u_owner.phone AS owner_phone, u_owner.email AS owner_email,
               b.check_in_date, b.check_out_date
        FROM emergency_alerts ea
        JOIN bookings b ON ea.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u_keeper ON ea.keeper_id = u_keeper.user_id
        JOIN users u_owner ON ea.owner_id = u_owner.user_id
        ORDER BY ea.is_resolved ASC, ea.sent_at DESC
    ");
    $alerts = $stmt->fetchAll();
} catch (PDOException $e) {
    $alerts = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: #DC2626;"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Alerts Center</h1>
            <p style="color: var(--text-muted);">Real-time monitoring of urgent pet emergencies and health incidents reported by keepers.</p>
        </div>
        <a href="<?= BASE_URL ?>/operator/dashboard.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Operator Dashboard
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Emergency Incident</th>
                        <th>Pet & Care Notes</th>
                        <th>Keeper (Sender)</th>
                        <th>Pet Owner (Contact)</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alerts)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-shield-heart" style="font-size: 2rem; color: var(--secondary); margin-bottom: 10px; display: block;"></i>
                                No emergency incidents recorded. All stays are running smoothly.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alerts as $al): ?>
                            <tr style="background: <?= (int)$al['is_resolved'] === 0 ? 'rgba(254, 242, 242, 0.7)' : 'transparent' ?>;">
                                <td style="max-width: 280px;">
                                    <div style="color: <?= (int)$al['is_resolved'] === 0 ? '#DC2626' : 'var(--text-dark)' ?>; font-weight: 700; margin-bottom: 4px;">
                                        <i class="fa-solid fa-bell"></i> Alert #<?= $al['alert_id'] ?>
                                    </div>
                                    <div style="font-size: 0.88rem; line-height: 1.4; color: var(--text-dark);">
                                        "<?= htmlspecialchars($al['message']) ?>"
                                    </div>
                                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">
                                        <?= date('M d, Y - h:i A', strtotime($al['sent_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($al['pet_name']) ?></strong> (<?= htmlspecialchars($al['species']) ?> - <?= htmlspecialchars($al['breed']) ?>)<br>
                                    <?php if (!empty($al['medical_notes'])): ?>
                                        <small style="color: #991B1B;"><strong>Med Notes:</strong> <?= htmlspecialchars(mb_strimwidth($al['medical_notes'], 0, 40, '...')) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($al['keeper_name']) ?></strong><br>
                                    <a href="tel:<?= htmlspecialchars($al['keeper_phone']) ?>" style="font-size: 0.85rem; color: var(--primary);">
                                        <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($al['keeper_phone'] ?: 'No Phone') ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($al['owner_name']) ?></strong><br>
                                    <a href="tel:<?= htmlspecialchars($al['owner_phone']) ?>" style="font-size: 0.85rem; color: var(--secondary);">
                                        <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($al['owner_phone'] ?: 'No Phone') ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ((int)$al['is_resolved'] === 0): ?>
                                        <span class="badge" style="background:#FEE2E2; color:#DC2626; font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> Unresolved</span>
                                    <?php else: ?>
                                        <span class="badge badge-confirmed"><i class="fa-solid fa-check"></i> Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ((int)$al['is_resolved'] === 0): ?>
                                        <form action="<?= BASE_URL ?>/operator/alerts.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="alert_id" value="<?= $al['alert_id'] ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <i class="fa-solid fa-check"></i> Mark Resolved
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-check-double"></i> Handled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
