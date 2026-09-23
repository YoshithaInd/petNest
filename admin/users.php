
<?php
/**
 * PetNest - Admin User Management
 * Member 4 Module
 */

$pageTitle = "Manage Users - Admin";
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$adminId = (int)$_SESSION['user_id'];
$roleFilter = $_GET['role'] ?? 'all';

// Handle Activate / Deactivate Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $targetUserId = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($targetUserId === $adminId) {
        setFlash('warning', 'You cannot deactivate your own administrator account.');
    } else {
        $newStatus = ($action === 'activate') ? 1 : 0;
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$newStatus, $targetUserId]);
            setFlash('success', "User account status updated successfully.");
        } catch (PDOException $e) {
            setFlash('danger', "Failed to update user: " . $e->getMessage());
        }
    }
    redirect('/admin/users.php?role=' . urlencode($roleFilter));
}

// Fetch users
$sql = "SELECT user_id, full_name, email, phone, role, profile_photo, is_active, created_at FROM users";
$params = [];

if ($roleFilter !== 'all' && in_array($roleFilter, ['owner', 'keeper', 'operator', 'admin'], true)) {
    $sql .= " WHERE role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">User Management 👥</h1>
            <p style="color: var(--text-muted);">Manage platform users, verify roles, and activate or deactivate accounts.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Admin Dashboard
        </a>
    </div>

    <!-- Filter Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/admin/users.php?role=all" class="btn btn-sm <?= $roleFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All Users</a>
        <a href="<?= BASE_URL ?>/admin/users.php?role=owner" class="btn btn-sm <?= $roleFilter === 'owner' ? 'btn-primary' : 'btn-outline' ?>">Pet Owners</a>
        <a href="<?= BASE_URL ?>/admin/users.php?role=keeper" class="btn btn-sm <?= $roleFilter === 'keeper' ? 'btn-primary' : 'btn-outline' ?>">Pet Keepers</a>
        <a href="<?= BASE_URL ?>/admin/users.php?role=operator" class="btn btn-sm <?= $roleFilter === 'operator' ? 'btn-primary' : 'btn-outline' ?>">Operators</a>
        <a href="<?= BASE_URL ?>/admin/users.php?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-outline' ?>">Admins</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name & Contact</th>
                        <th>Role</th>
                        <th>Registered Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No users found in this category.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>#<?= $u['user_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($u['full_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?> &bull; <?= htmlspecialchars($u['phone'] ?: 'No Phone') ?></small>
                                </td>
                                <td>
                                    <span class="user-badge" style="text-transform: uppercase;">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <?php if ((int)$u['is_active'] === 1): ?>
                                        <span class="badge badge-confirmed">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($u['user_id'] !== $adminId): ?>
                                        <form action="<?= BASE_URL ?>/admin/users.php?role=<?= urlencode($roleFilter) ?>" method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <?php if ((int)$u['is_active'] === 1): ?>
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="btn btn-danger btn-sm" data-confirm="Deactivate account for <?= htmlspecialchars($u['full_name']) ?>? They will be unable to log in.">
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-secondary btn-sm">
                                                    Reactivate
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">You (Current Admin)</span>
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
