<?php
/**
 * PetNest - Landing Page
 */
$pageTitle = "PetNest - Where Pets Feel at Home";
require_once __DIR__ . '/includes/header.php';

// Fetch featured verified keepers from database
$featuredKeepers = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.profile_photo, kp.location, kp.price_per_day, kp.breeds_experienced, kp.years_experience,
               COALESCE(AVG(r.stars), 5.0) AS avg_rating,
               COUNT(r.rating_id) AS total_reviews
        FROM users u
        JOIN keeper_profiles kp ON u.user_id = kp.user_id
        LEFT JOIN ratings r ON u.user_id = r.keeper_id
        WHERE u.role = 'keeper' AND u.is_active = 1 AND kp.is_verified = 1 AND kp.availability_status = 'available'
        GROUP BY u.user_id, u.full_name, u.profile_photo, kp.location, kp.price_per_day, kp.breeds_experienced, kp.years_experience
        ORDER BY avg_rating DESC, total_reviews DESC
        LIMIT 3
    ");
    $stmt->execute();
    $featuredKeepers = $stmt->fetchAll();
} catch (PDOException $e) {
    // Graceful fallback if database is not yet imported
    $featuredKeepers = [];
}
?>

<!-- 1. Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">Where Pets Feel at Home 🐾</h1>
        <p class="hero-subtitle">
            Find loving, trustworthy, and verified local pet keepers when you travel for work or vacation. Safe, transparent, and trackable pet boarding.
        </p>

        <!-- Search Preview Box -->
        <form action="<?= BASE_URL ?>/search.php" method="GET" class="hero-search-box">
            <input type="text" name="location" class="form-control" placeholder="📍 City / Location (e.g. Colombo, Kandy)">
            <input type="text" name="breed" class="form-control" placeholder="🐕 Breed (e.g. Golden Retriever, Cat)">
            <input type="number" name="max_price" class="form-control" placeholder="💰 Max Price/Day ($)" min="5" step="1">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </div>
</section>

<!-- 2. How It Works Section -->
<section class="container" style="padding: 60px 0;">
    <div style="text-align: center; margin-bottom: 45px;">
        <h2 style="font-size: 2rem; color: var(--primary); margin-bottom: 10px;">How PetNest Works</h2>
        <p style="color: var(--text-muted);">A simple, transparent, and structured way to arrange pet care.</p>
    </div>

    <div class="grid-3">
        <div class="card" style="text-align: center; padding: 30px 20px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 18px;">
                <i class="fa-solid fa-magnifying-glass-location"></i>
            </div>
            <h3 style="margin-bottom: 10px; color: var(--text-dark);">1. Search & Filter</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Browse background-checked keepers based on location, breed experience, price, and real pet owner reviews.
            </p>
        </div>

        <div class="card" style="text-align: center; padding: 30px 20px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--secondary-light); color: var(--secondary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 18px;">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h3 style="margin-bottom: 10px; color: var(--text-dark);">2. Book & Custom Care</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Select check-in dates and provide clear feeding routines, bath times, medical notes, and special instructions.
            </p>
        </div>

        <div class="card" style="text-align: center; padding: 30px 20px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 18px;">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <h3 style="margin-bottom: 10px; color: var(--text-dark);">3. Safe Stay & Alerts</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Enjoy peace of mind with emergency alert safeguards, secure online payment, and transparent post-stay ratings.
            </p>
        </div>
    </div>
</section>

<!-- 3. Featured Keepers Section -->
<section style="background: #F4ECE6; padding: 60px 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="font-size: 2rem; color: var(--primary); margin-bottom: 6px;">Top Verified Pet Keepers</h2>
                <p style="color: var(--text-muted);">Trusted caretakers ready to welcome your pet.</p>
            </div>
            <a href="<?= BASE_URL ?>/search.php" class="btn btn-outline btn-sm">View All Keepers <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <?php if (!empty($featuredKeepers)): ?>
            <div class="grid-3">
                <?php foreach ($featuredKeepers as $keeper): ?>
                    <div class="card keeper-card">
                        <div class="keeper-card-header">
                            <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($keeper['profile_photo']) ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($keeper['full_name']) ?>&background=6B4226&color=fff'" 
                                 alt="<?= htmlspecialchars($keeper['full_name']) ?>" 
                                 class="keeper-avatar">
                            <div>
                                <h4 class="keeper-name"><?= htmlspecialchars($keeper['full_name']) ?></h4>
                                <div class="keeper-location"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($keeper['location']) ?></div>
                                <div class="keeper-rating">
                                    <i class="fa-solid fa-star"></i> <?= number_format($keeper['avg_rating'], 1) ?>
                                    <span style="color: var(--text-muted); font-weight: normal; font-size: 0.8rem;">(<?= $keeper['total_reviews'] ?> reviews)</span>
                                </div>
                            </div>
                        </div>

                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 14px; flex: 1;">
                            <strong>Breeds:</strong> <?= htmlspecialchars($keeper['breeds_experienced'] ?: 'All friendly breeds') ?>
                        </p>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: auto;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Daily Rate</span>
                                <div class="keeper-price">$<?= number_format($keeper['price_per_day'], 2) ?><small style="font-size:0.75rem; color:var(--text-muted);">/day</small></div>
                            </div>
                            <a href="<?= BASE_URL ?>/keeper_profile.php?id=<?= $keeper['user_id'] ?>" class="btn btn-primary btn-sm">View Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <i class="fa-solid fa-database" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 15px;"></i>
                <h3>Welcome to PetNest!</h3>
                <p style="color: var(--text-muted); margin-bottom: 20px;">
                    Import <code>database.sql</code> into your MySQL phpMyAdmin to view sample verified keepers and test data.
                </p>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Create Your Account</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 4. Call to Action Banner -->
<section class="container" style="padding: 70px 0; text-align: center;">
    <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, #462A17 100%); color: #FFFFFF; padding: 45px 20px;">
        <h2 style="font-size: 2.2rem; margin-bottom: 12px; color: #FFFFFF;">Are You a Passionate Pet Lover?</h2>
        <p style="font-size: 1.1rem; max-width: 650px; margin: 0 auto 25px; color: #E8DFD5;">
            Earn income while caring for lovely dogs and cats in your own home. Join PetNest as a verified pet keeper today!
        </p>
        <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/register.php?role=keeper" class="btn btn-secondary btn-lg">Become a Keeper</a>
            <a href="<?= BASE_URL ?>/register.php?role=owner" class="btn btn-outline btn-lg" style="color:#FFF; border-color:#FFF;">Book a Stay for My Pet</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
