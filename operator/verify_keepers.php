
<?php
/**
 * PetNest - Operator Keeper Verification Center
 * Member 4 Module
 */

$pageTitle = "Verify Keepers - Operator";
require_once __DIR__ . '/../includes/header.php';
requireRole('operator');

// Handle Verification / Revocation Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $targetUserId = (int)$_POST['user_id'];
    $action       = $_POST['action'];

    $newStatus = ($action === 'approve') ? 1 : 0;
    try {
        $stmt = $pdo->prepare("UPDATE keeper_profiles SET is_verified = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $targetUserId]);
        setFlash('success', $newStatus === 1 ? 'Keeper profile verified and approved for public search.' : 'Keeper verification status revoked.');
    } catch (PDOException $e) {
        setFlash('danger', 'Operation failed: ' . $e->getMessage());
    }
    redirect('/operator/verify_keepers.php');
}

// Fetch all keepers
$keepers = [];
try {
    $stmt = $pdo->query("
        SELECT u.user_id, u.full_name, u.email, u.phone, u.profile_photo, u.created_at,
               kp.profile_id, kp.bio, kp.location, kp.price_per_day, kp.breeds_experienced, 
               kp.years_experience, kp.is_verified, kp.availability_status
        FROM users u
        JOIN keeper_profiles kp ON u.user_id = kp.user_id
        WHERE u.role = 'keeper'
        ORDER BY kp.is_verified ASC, u.created_at DESC
    ");
    $keepers = $stmt->fetchAll();
} catch (PDOException $e) {
    $keepers = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">Keeper Verification Center 🛡️</h1>
            <p style="color: var(--text-muted);">Review new pet keeper applications, bios, and verify trustworthy caretakers.</p>
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
                        <th>Keeper Details</th>
                        <th>Location & Rate</th>
                        <th>Experience & Breeds</th>
                        <th>Bio</th>
                        <th>Status</th>
                        <th style="text-align: right;">Verification Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keepers)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No keeper profiles registered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($keepers as $k): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($k['full_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($k['email']) ?> &bull; <?= htmlspecialchars($k['phone'] ?: 'No Phone') ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($k['location']) ?></strong><br>
                                    <span style="color: var(--secondary); font-weight: 700;">$<?= number_format($k['price_per_day'], 2) ?>/day</span>
                                </td>
                                <td>
                                    <strong><?= $k['years_experience'] ?> Years</strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars(mb_strimwidth($k['breeds_experienced'] ?: 'All breeds', 0, 30, '...')) ?></small>
                                </td>
                                <td style="max-width: 220px; font-size: 0.85rem; color: var(--text-dark);">
                                    <?= htmlspecialchars(mb_strimwidth($k['bio'] ?: 'No bio provided.', 0, 80, '...')) ?>
                                </td>
                                <td>
                                    <?php if ((int)$k['is_verified'] === 1): ?>
                                        <span class="badge badge-confirmed"><i class="fa-solid fa-check"></i> Verified</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending"><i class="fa-solid fa-clock"></i> Pending Review</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <form action="<?= BASE_URL ?>/operator/verify_keepers.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $k['user_id'] ?>">
                                        <?php if ((int)$k['is_verified'] === 1): ?>
                                            <input type="hidden" name="action" value="revoke">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Revoke verification badge for <?= htmlspecialchars($k['full_name']) ?>?">
                                                Revoke
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <i class="fa-solid fa-check-circle"></i> Approve & Verify
                                            </button>
                                        <?php endif; ?>
                                    </form>
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
