<?php
/**
 * PetNest - Public Keeper Profile Page
 * Member 2 Module
 */

$pageTitle = "Keeper Profile - PetNest";
require_once __DIR__ . '/includes/header.php';

$keeperId = (int)($_GET['id'] ?? 0);
$petId    = (int)($_GET['pet_id'] ?? 0);

if ($keeperId <= 0) {
    setFlash('danger', 'Invalid keeper profile specified.');
    redirect('/search.php');
}

// Fetch keeper information
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.email, u.phone, u.profile_photo, u.created_at AS member_since,
               kp.bio, kp.location, kp.price_per_day, kp.breeds_experienced, 
               kp.availability_status, kp.years_experience, kp.is_verified,
               COALESCE(AVG(r.stars), 5.0) AS avg_rating,
               COUNT(r.rating_id) AS total_reviews
        FROM users u
        JOIN keeper_profiles kp ON u.user_id = kp.user_id
        LEFT JOIN ratings r ON u.user_id = r.keeper_id
        WHERE u.user_id = ? AND u.role = 'keeper' AND u.is_active = 1
        GROUP BY u.user_id, u.full_name, u.email, u.phone, u.profile_photo, u.created_at,
                 kp.bio, kp.location, kp.price_per_day, kp.breeds_experienced, 
                 kp.availability_status, kp.years_experience, kp.is_verified
        LIMIT 1
    ");
    $stmt->execute([$keeperId]);
    $keeper = $stmt->fetch();

    if (!$keeper) {
        setFlash('danger', 'Keeper profile not found.');
        redirect('/search.php');
    }

    // Fetch customer reviews
    $stmtReviews = $pdo->prepare("
        SELECT r.stars, r.feedback_text, r.rated_at,
               u.full_name AS owner_name, u.profile_photo AS owner_photo,
               p.pet_name, p.species
        FROM ratings r
        JOIN users u ON r.owner_id = u.user_id
        JOIN bookings b ON r.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        WHERE r.keeper_id = ?
        ORDER BY r.rated_at DESC
    ");
    $stmtReviews->execute([$keeperId]);
    $reviews = $stmtReviews->fetchAll();

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    redirect('/search.php');
}
?>

<div class="container" style="padding: 35px 20px;">
    
    <!-- Top Profile Header Card -->
    <div class="card" style="margin-bottom: 30px; padding: 30px;">
        <div style="display: flex; gap: 30px; align-items: center; flex-wrap: wrap;">
            <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($keeper['profile_photo']) ?>" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($keeper['full_name']) ?>&background=6B4226&color=fff&size=150'" 
                 alt="<?= htmlspecialchars($keeper['full_name']) ?>" 
                 style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); box-shadow: var(--shadow-md);">

            <div style="flex: 1; min-width: 260px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                    <h1 style="font-size: 2rem; color: var(--primary); margin: 0;"><?= htmlspecialchars($keeper['full_name']) ?></h1>
                    <?php if ((int)$keeper['is_verified'] === 1): ?>
                        <span class="badge" style="background: var(--secondary-light); color: var(--secondary); font-size: 0.85rem;">
                            <i class="fa-solid fa-circle-check"></i> Verified Keeper
                        </span>
                    <?php endif; ?>

                    <?php if ($keeper['availability_status'] === 'available'): ?>
                        <span class="badge badge-available"><i class="fa-solid fa-circle"></i> Available Now</span>
                    <?php else: ?>
                        <span class="badge badge-unavailable"><i class="fa-solid fa-circle"></i> Currently Unavailable</span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 20px; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 12px; flex-wrap: wrap;">
                    <span><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?= htmlspecialchars($keeper['location']) ?></span>
                    <span><i class="fa-solid fa-award" style="color: var(--primary);"></i> <?= $keeper['years_experience'] ?> Years Animal Experience</span>
                    <span><i class="fa-regular fa-clock"></i> Member since <?= date('M Y', strtotime($keeper['member_since'])) ?></span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #F59E0B; font-size: 1.2rem; font-weight: 700;">
                        ★ <?= number_format($keeper['avg_rating'], 1) ?>
                    </span>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">
                        (<?= $keeper['total_reviews'] ?> customer review<?= $keeper['total_reviews'] == 1 ? '' : 's' ?>)
                    </span>
                </div>
            </div>

            <!-- Booking Rate & Call to Action -->
            <div style="text-align: right; min-width: 200px; border-left: 1px solid var(--border-color); padding-left: 25px;">
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Boarding Rate</span>
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--secondary); margin-bottom: 14px;">
                    $<?= number_format($keeper['price_per_day'], 2) ?><small style="font-size:0.9rem; color:var(--text-muted); font-weight:normal;">/day</small>
                </div>

                <?php if ($keeper['availability_status'] === 'available'): ?>
                    <a href="<?= BASE_URL ?>/book.php?keeper_id=<?= $keeper['user_id'] ?><?= $petId > 0 ? '&pet_id=' . $petId : '' ?>" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fa-solid fa-calendar-check"></i> Book Now
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline btn-lg" style="width: 100%; cursor: not-allowed; opacity: 0.6;" disabled>
                        Not Available
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details Grid: Bio + Breeds + Reviews -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        
        <!-- Left: About & Home Environment -->
        <div>
            <div class="card" style="margin-bottom: 25px;">
                <h3 class="card-title" style="margin-bottom: 14px;"><i class="fa-solid fa-house-chimney-user"></i> About My Home & Pet Care</h3>
                <p style="font-size: 1rem; line-height: 1.7; color: var(--text-dark); white-space: pre-line;">
                    <?= htmlspecialchars($keeper['bio'] ?: 'Hello! I am a passionate and attentive pet keeper. I love hosting pets and ensuring they feel safe, exercised, and cherished while their owners are away.') ?>
                </p>
            </div>

            <!-- Customer Reviews Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-comments"></i> Pet Owner Reviews (<?= count($reviews) ?>)</h3>
                </div>

                <?php if (empty($reviews)): ?>
                    <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                        <i class="fa-regular fa-comment-dots" style="font-size: 2.5rem; margin-bottom: 10px; color: var(--border-color);"></i>
                        <p>No written reviews yet for this keeper.</p>
                        <small>Reviews are submitted by verified pet owners after completed bookings.</small>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($reviews as $rev): ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($rev['owner_name']) ?></div>
                                        <small style="color: var(--text-muted);">(Host for <?= htmlspecialchars($rev['pet_name']) ?> - <?= htmlspecialchars($rev['species']) ?>)</small>
                                    </div>
                                    <div style="color: #F59E0B; font-size: 0.95rem;">
                                        <?= str_repeat('★', (int)$rev['stars']) . str_repeat('☆', 5 - (int)$rev['stars']) ?>
                                    </div>
                                </div>
                                <p style="font-size: 0.95rem; color: var(--text-dark); line-height: 1.5; margin-bottom: 6px;">
                                    "<?= htmlspecialchars($rev['feedback_text']) ?>"
                                </p>
                                <small style="color: var(--text-muted); font-size: 0.8rem;"><?= date('M d, Y', strtotime($rev['rated_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Breeds Experienced & Safety Badges -->
        <div>
            <div class="card" style="margin-bottom: 25px;">
                <h3 class="card-title" style="margin-bottom: 14px;"><i class="fa-solid fa-dog"></i> Experienced With</h3>
                <?php
                $breeds = array_filter(array_map('trim', explode(',', $keeper['breeds_experienced'] ?? '')));
                if (!empty($breeds)):
                ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($breeds as $b): ?>
                            <span style="background: var(--primary-light); color: var(--primary); padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <?= htmlspecialchars($b) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Comfortable with all friendly dogs and cats.</p>
                <?php endif; ?>
            </div>

            <div class="card" style="background: #FBF9F5;">
                <h3 class="card-title" style="margin-bottom: 14px;"><i class="fa-solid fa-shield-halved" style="color: var(--secondary);"></i> PetNest Trust & Safety</h3>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 12px; font-size: 0.9rem; color: var(--text-dark);">
                    <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Emergency Alert safeguards enabled</li>
                    <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Structured feeding & medication schedules</li>
                    <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Secure escrow payments</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 850px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
