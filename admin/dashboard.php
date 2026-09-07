
<?php
/**
 * PetNest - Administrator Dashboard
 * Member 4 Module
 */

$pageTitle = "Admin Dashboard - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// Fetch Platform Stats
$totalUsers = 0;
$totalOwners = 0;
$totalKeepers = 0;
$totalBookings = 0;
$totalRevenue = 0.00;
$totalReviews = 0;
$recentUsers = [];
$recentBookings = [];

try {
    // 1. User Counts
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $userStats = $stmt->fetchAll();
    foreach ($userStats as $stat) {
        $totalUsers += (int)$stat['count'];
        if ($stat['role'] === 'owner') $totalOwners = (int)$stat['count'];
        if ($stat['role'] === 'keeper') $totalKeepers = (int)$stat['count'];
    }

    // 2. Bookings & Revenue
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $totalBookings = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0.00) FROM payments WHERE payment_status = 'paid'");
    $totalRevenue = (float)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM ratings");
    $totalReviews = (int)$stmt->fetchColumn();

    // 3. Recent Users
    $stmt = $pdo->query("SELECT user_id, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll();

    // 4. Recent Bookings
    $stmt = $pdo->query("
        SELECT b.booking_id, b.check_in_date, b.check_out_date, b.total_price, b.status,
               p.pet_name, u_owner.full_name AS owner_name, u_keeper.full_name AS keeper_name
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u_owner ON b.owner_id = u_owner.user_id
        JOIN users u_keeper ON b.keeper_id = u_keeper.user_id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recentBookings = $stmt->fetchAll();

} catch (PDOException $e) {
    // Log exception
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">System Administration Panel 📊</h1>
            <p style="color: var(--text-muted);">Overview of users, bookings, financial revenue, and system moderation.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-primary"><i class="fa-solid fa-users-gear"></i> Manage Users</a>
            <a href="<?= BASE_URL ?>/admin/reviews.php" class="btn btn-outline"><i class="fa-solid fa-star-half-stroke"></i> Moderate Reviews</a>
        </div>
    </div>

    <!-- 4 High-Level Metric Cards -->
    <div class="grid-4" style="margin-bottom: 35px;">
        <div class="card" style="border-left: 4px solid var(--primary);">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Users</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin: 4px 0;"><?= $totalUsers ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;"><?= $totalOwners ?> Owners &bull; <?= $totalKeepers ?> Keepers</small>
        </div>

        <div class="card" style="border-left: 4px solid var(--secondary);">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Platform Revenue</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--secondary); margin: 4px 0;">$<?= number_format($totalRevenue, 2) ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;">From paid bookings</small>
        </div>

        <div class="card" style="border-left: 4px solid var(--accent);">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Bookings</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin: 4px 0;"><?= $totalBookings ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;">Lifetime stay requests</small>
        </div>

        <div class="card" style="border-left: 4px solid #3B82F6;">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Reviews</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #3B82F6; margin: 4px 0;"><?= $totalReviews ?></div>
            <small style="color: var(--text-muted); font-size: 0.8rem;">Submitted ratings</small>
        </div>
    </div>

    <!-- 2 Main Grid Columns -->
    <div class="grid-2">
        <!-- Recent Users Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-user-plus"></i> Recently Registered Users</h3>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline btn-sm">View All</a>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($u['full_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td><span class="badge" style="background:#EEE; text-transform:capitalize;"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td>
                                    <?php if ((int)$u['is_active'] === 1): ?>
                                        <span class="badge badge-confirmed">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-calendar-days"></i> Recent System Bookings</h3>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Stay Details</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($b['pet_name']) ?></strong> (<?= htmlspecialchars($b['owner_name']) ?> $\rightarrow$ <?= htmlspecialchars($b['keeper_name']) ?>)<br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($b['check_in_date']) ?> to <?= htmlspecialchars($b['check_out_date']) ?></small>
                                </td>
                                <td><strong>$<?= number_format($b['total_price'], 2) ?></strong></td>
                                <td><span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
