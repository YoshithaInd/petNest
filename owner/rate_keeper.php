
<?php
/**
 * PetNest - Rate & Review Keeper Form
 * Member 4 Module
 */

$pageTitle = "Rate Pet Keeper - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$ownerId   = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    setFlash('danger', 'Invalid booking specified for review.');
    redirect('/owner/my_bookings.php');
}

// Fetch booking & keeper details
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.pet_name, p.species, p.breed,
               u.user_id AS keeper_id, u.full_name AS keeper_name, u.profile_photo AS keeper_photo,
               kp.location,
               r.rating_id
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.keeper_id = u.user_id
        JOIN keeper_profiles kp ON u.user_id = kp.user_id
        LEFT JOIN ratings r ON b.booking_id = r.booking_id
        WHERE b.booking_id = ? AND b.owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId, $ownerId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        setFlash('danger', 'Booking record not found.');
        redirect('/owner/my_bookings.php');
    }

    if ($booking['status'] !== 'completed') {
        setFlash('warning', 'You can only leave a review after your pet stay has been completed.');
        redirect('/owner/my_bookings.php');
    }

    if (!empty($booking['rating_id'])) {
        setFlash('info', 'You have already submitted a review for this booking.');
        redirect('/owner/my_bookings.php');
    }

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    redirect('/owner/my_bookings.php');
}

$errors = [];
$stars = 5;
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stars    = (int)($_POST['stars'] ?? 5);
    $feedback = trim($_POST['feedback_text'] ?? '');

    if ($stars < 1 || $stars > 5) {
        $errors[] = "Please select a star rating between 1 and 5.";
    }
    if (empty($feedback)) {
        $errors[] = "Please provide some written feedback about your experience.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ratings (booking_id, owner_id, keeper_id, stars, feedback_text)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$bookingId, $ownerId, (int)$booking['keeper_id'], $stars, $feedback]);

            setFlash('success', "Thank you! Your {$stars}-star review for " . htmlspecialchars($booking['keeper_name']) . " has been posted.");
            redirect('/owner/my_bookings.php');
        } catch (PDOException $e) {
            $errors[] = "Failed to submit review: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 600px; padding: 40px 20px;">
    
    <!-- Keeper Preview -->
    <div class="card" style="margin-bottom: 20px; padding: 20px; display: flex; align-items: center; gap: 16px; background: #FAF7F2;">
        <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($booking['keeper_photo']) ?>" 
             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($booking['keeper_name']) ?>&background=6B4226&color=fff'" 
             alt="<?= htmlspecialchars($booking['keeper_name']) ?>" 
             style="width: 65px; height: 65px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
        <div>
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Reviewing Host</span>
            <h3 style="font-size: 1.25rem; color: var(--primary); margin: 0;"><?= htmlspecialchars($booking['keeper_name']) ?></h3>
            <small style="color: var(--text-muted);">Hosted <?= htmlspecialchars($booking['pet_name']) ?> (<?= htmlspecialchars($booking['check_in_date']) ?> to <?= htmlspecialchars($booking['check_out_date']) ?>)</small>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-star" style="color: #F59E0B;"></i> Leave a Rating & Review</h2>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="display: block;">
                <strong>Please correct the following:</strong>
                <ul style="margin: 8px 0 0 20px; font-size: 0.9rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/owner/rate_keeper.php?booking_id=<?= $bookingId ?>" method="POST" style="margin-top: 15px;">
            
            <!-- Star Rating Radios -->
            <div class="form-group" style="text-align: center; margin-bottom: 25px;">
                <label class="form-label" style="margin-bottom: 12px; font-size: 1rem;">How was your pet's boarding experience?</label>
                <div style="display: flex; justify-content: center; gap: 15px; font-size: 1.8rem; color: #F59E0B;">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <label style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                            <input type="radio" name="stars" value="<?= $i ?>" <?= $stars === $i ? 'checked' : '' ?> style="cursor: pointer;">
                            <span style="font-size: 0.8rem; color: var(--text-dark); font-weight: 700;"><?= $i ?> ★</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Written Feedback -->
            <div class="form-group">
                <label class="form-label" for="feedback_text"><i class="fa-solid fa-pen"></i> Written Testimonial *</label>
                <textarea id="feedback_text" name="feedback_text" rows="5" class="form-control" placeholder="Describe how well the keeper cared for your pet, their responsiveness, and the cleanliness of their home environment..." required><?= htmlspecialchars($feedback) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                    <i class="fa-solid fa-check"></i> Submit Review
                </button>
                <a href="<?= BASE_URL ?>/owner/my_bookings.php" class="btn btn-outline btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
