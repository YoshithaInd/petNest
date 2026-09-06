<?php
/**
 * PetNest - Search Keepers
 * Purpose: Search and filter available pet keepers by location, pet type, dates, and ratings.
 * Scope: Member 1
 */
$pageTitle = "Find Pet Keepers - PetNest";
require_once __DIR__ . '/includes/header.php';

// Get query filters
$location = trim($_GET['location'] ?? '');
$breed    = trim($_GET['breed'] ?? '');
$maxPrice = !empty($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sortBy   = $_GET['sort_by'] ?? 'rating';
$petId    = (int)($_GET['pet_id'] ?? 0);

// Build dynamic SQL query
$sql = "
    SELECT u.user_id, u.full_name, u.profile_photo, u.phone,
           kp.bio, kp.location, kp.price_per_day, kp.breeds_experienced, 
           kp.availability_status, kp.years_experience, kp.is_verified,
           COALESCE(AVG(r.stars), 5.0) AS avg_rating,
           COUNT(r.rating_id) AS total_reviews
    FROM users u
    JOIN keeper_profiles kp ON u.user_id = kp.user_id
    LEFT JOIN ratings r ON u.user_id = r.keeper_id
    WHERE u.role = 'keeper' AND u.is_active = 1
";

$params = [];

if (!empty($location)) {
    $sql .= " AND kp.location LIKE ?";
    $params[] = "%{$location}%";
}

if (!empty($breed)) {
    $sql .= " AND kp.breeds_experienced LIKE ?";
    $params[] = "%{$breed}%";
}

if ($maxPrice !== null && $maxPrice > 0) {
    $sql .= " AND kp.price_per_day <= ?";
    $params[] = $maxPrice;
}

$sql .= " GROUP BY u.user_id, u.full_name, u.profile_photo, u.phone,
                   kp.bio, kp.location, kp.price_per_day, kp.breeds_experienced, 
                   kp.availability_status, kp.years_experience, kp.is_verified";

// Sorting
switch ($sortBy) {
    case 'price_low':
        $sql .= " ORDER BY kp.price_per_day ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY kp.price_per_day DESC";
        break;
    case 'experience':
        $sql .= " ORDER BY kp.years_experience DESC";
        break;
    case 'rating':
    default:
        $sql .= " ORDER BY avg_rating DESC, total_reviews DESC";
        break;
}

$keepers = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $keepers = $stmt->fetchAll();
} catch (PDOException $e) {
    $keepers = [];
}
?>

<div class="container" style="padding: 35px 20px;">
    <!-- Page Header -->
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 2rem; color: var(--primary);">Find Verified Pet Keepers 🐕🐈</h1>
        <p style="color: var(--text-muted);">Browse trusted caretakers in your area and filter by breed experience, price, and ratings.</p>
    </div>

    <!-- Main Layout: Sidebar Filters + Results Grid -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 30px; align-items: start;">
        
        <!-- Filter Sidebar -->
        <div class="card" style="padding: 22px; position: sticky; top: 90px;">
            <div class="card-header" style="padding-bottom: 10px; margin-bottom: 14px;">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-filter"></i> Filter Keepers</h3>
                <a href="<?= BASE_URL ?>/search.php" style="font-size: 0.8rem; color: var(--text-muted);">Reset</a>
            </div>

            <form action="<?= BASE_URL ?>/search.php" method="GET">
                <?php if ($petId > 0): ?>
                    <input type="hidden" name="pet_id" value="<?= $petId ?>">
                <?php endif; ?>

                <!-- Location Filter -->
                <div class="form-group">
                    <label class="form-label" for="location"><i class="fa-solid fa-location-dot"></i> City / Location</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($location) ?>" placeholder="e.g. Colombo, Kandy">
                </div>

                <!-- Breed Experience Filter -->
                <div class="form-group">
                    <label class="form-label" for="breed"><i class="fa-solid fa-paw"></i> Breed Experience</label>
                    <input type="text" id="breed" name="breed" class="form-control" value="<?= htmlspecialchars($breed) ?>" placeholder="e.g. Retriever, Persian">
                </div>

                <!-- Max Price Filter -->
                <div class="form-group">
                    <label class="form-label" for="max_price"><i class="fa-solid fa-dollar-sign"></i> Max Daily Rate ($)</label>
                    <input type="number" id="max_price" name="max_price" class="form-control" value="<?= htmlspecialchars((string)($maxPrice ?? '')) ?>" placeholder="e.g. 35" min="5" step="1">
                </div>

                <!-- Sort By Filter -->
                <div class="form-group">
                    <label class="form-label" for="sort_by"><i class="fa-solid fa-arrow-down-short-wide"></i> Sort By</label>
                    <select id="sort_by" name="sort_by" class="form-select">
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rating ⭐</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Lowest Price First ($)</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Highest Price First ($)</option>
                        <option value="experience" <?= $sortBy === 'experience' ? 'selected' : '' ?>>Most Experience (Years)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-magnifying-glass"></i> Apply Filters
                </button>
            </form>
        </div>

        <!-- Search Results Grid -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.15rem; color: var(--text-dark);">
                    Showing <strong><?= count($keepers) ?></strong> Pet Keeper<?= count($keepers) === 1 ? '' : 's' ?>
                </h3>
            </div>

            <?php if (empty($keepers)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <i class="fa-solid fa-user-xmark" style="font-size: 3rem; color: var(--border-color); margin-bottom: 15px;"></i>
                    <h2 style="font-size: 1.3rem; margin-bottom: 8px;">No Keepers Match Your Criteria</h2>
                    <p style="color: var(--text-muted); max-width: 440px; margin: 0 auto 20px;">
                        Try adjusting your location, clearing the breed keyword, or increasing the max daily price.
                    </p>
                    <a href="<?= BASE_URL ?>/search.php" class="btn btn-primary btn-sm">Clear All Filters</a>
                </div>
            <?php else: ?>
                <div class="grid-2">
                    <?php foreach ($keepers as $keeper): ?>
                        <div class="card keeper-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <!-- Header -->
                                <div class="keeper-card-header">
                                    <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($keeper['profile_photo']) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($keeper['full_name']) ?>&background=6B4226&color=fff'" 
                                         alt="<?= htmlspecialchars($keeper['full_name']) ?>" 
                                         class="keeper-avatar">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <h4 class="keeper-name"><?= htmlspecialchars($keeper['full_name']) ?></h4>
                                            <?php if ((int)$keeper['is_verified'] === 1): ?>
                                                <span title="Verified Keeper" style="color: var(--secondary); font-size: 0.95rem;"><i class="fa-solid fa-circle-check"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="keeper-location"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($keeper['location']) ?> &bull; <?= $keeper['years_experience'] ?> yrs exp.</div>
                                        <div class="keeper-rating">
                                            <i class="fa-solid fa-star"></i> <?= number_format($keeper['avg_rating'], 1) ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">(<?= $keeper['total_reviews'] ?> reviews)</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bio Preview -->
                                <p style="font-size: 0.88rem; color: var(--text-dark); margin-bottom: 12px; line-height: 1.5;">
                                    <?= htmlspecialchars(mb_strimwidth($keeper['bio'] ?: 'Experienced caretaker ready to host your pet.', 0, 110, '...')) ?>
                                </p>

                                <!-- Breeds Badge -->
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;">
                                    <strong>Experienced in:</strong> <?= htmlspecialchars($keeper['breeds_experienced'] ?: 'All friendly breeds') ?>
                                </div>
                            </div>

                            <!-- Footer / Pricing & Actions -->
                            <div style="border-top: 1px solid var(--border-color); padding-top: 14px; margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Rate</span>
                                    <div class="keeper-price">$<?= number_format($keeper['price_per_day'], 2) ?><small style="font-size:0.75rem; color:var(--text-muted);">/day</small></div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="<?= BASE_URL ?>/keeper_profile.php?id=<?= $keeper['user_id'] ?><?= $petId > 0 ? '&pet_id=' . $petId : '' ?>" class="btn btn-outline btn-sm">
                                        View Profile
                                    </a>
                                    <a href="<?= BASE_URL ?>/book.php?keeper_id=<?= $keeper['user_id'] ?><?= $petId > 0 ? '&pet_id=' . $petId : '' ?>" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-calendar-check"></i> Book
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 850px) {
    div[style*="grid-template-columns: 280px 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
