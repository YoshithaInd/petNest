<?php
/**
 * PetNest - Review Moderation
 * Purpose: Admin interface to inspect and moderate user reviews and ratings.
 * Scope: Member 4
 */

$pageTitle = "Moderate Reviews - Admin";
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// Handle Review Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rating_id'])) {
    $ratingId = (int)$_POST['delete_rating_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM ratings WHERE rating_id = ?");
        $stmt->execute([$ratingId]);
        setFlash('success', 'Review has been removed from the platform.');
    } catch (PDOException $e) {
        setFlash('danger', 'Failed to remove review: ' . $e->getMessage());
    }
    redirect('/admin/reviews.php');
}

// Fetch all reviews
$reviews = [];
try {
    $stmt = $pdo->query("
        SELECT r.*,
               u_owner.full_name AS owner_name, u_owner.email AS owner_email,
               u_keeper.full_name AS keeper_name,
               p.pet_name, p.species
        FROM ratings r
        JOIN users u_owner ON r.owner_id = u_owner.user_id
        JOIN users u_keeper ON r.keeper_id = u_keeper.user_id
        JOIN bookings b ON r.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        ORDER BY r.rated_at DESC
    ");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}
?>

<div class="container" style="padding: 30px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">Review Moderation 🌟</h1>
            <p style="color: var(--text-muted);">Monitor and moderate user ratings and testimonials across the platform.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Admin Dashboard
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rating & Feedback</th>
                        <th>Pet & Stay</th>
                        <th>Reviewer (Owner)</th>
                        <th>Target Keeper</th>
                        <th>Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No reviews submitted on the platform yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td style="max-width: 320px;">
                                    <div style="color: #F59E0B; font-size: 1rem; margin-bottom: 4px;">
                                        <?= str_repeat('★', (int)$rev['stars']) . str_repeat('☆', 5 - (int)$rev['stars']) ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: var(--text-dark); line-height: 1.4;">
                                        "<?= htmlspecialchars($rev['feedback_text']) ?>"
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($rev['pet_name']) ?></strong> (<?= htmlspecialchars($rev['species']) ?>)<br>
                                    <small style="color: var(--text-muted);">Booking #<?= $rev['booking_id'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($rev['owner_name']) ?></td>
                                <td><?= htmlspecialchars($rev['keeper_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($rev['rated_at'])) ?></td>
                                <td style="text-align: right;">
                                    <form action="<?= BASE_URL ?>/admin/reviews.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_rating_id" value="<?= $rev['rating_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to delete this review permanently?">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
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
