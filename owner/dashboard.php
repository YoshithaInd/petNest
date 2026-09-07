<?php
/**
 * PetNest - Pet Owner Dashboard
 * Member 1 Module
 */


$pageTitle = "Owner Dashboard - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$ownerId = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['user_name'] ?? 'Pet Owner';

// Fetch stats
$totalPets = 0;
$activeBookingsCount = 0;
$unresolvedAlerts = [];
$pets = [];
$recentBookings = [];

try {
    // 1. Total Pets
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE owner_id = ?");
    $stmt->execute([$ownerId]);
    $totalPets = (int)$stmt->fetchColumn();

    // 2. Active & Pending Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE owner_id = ? AND status IN ('pending', 'confirmed', 'active')");
    $stmt->execute([$ownerId]);
    $activeBookingsCount = (int)$stmt->fetchColumn();

    // 3. Check for Emergency Alerts
    $stmt = $pdo->prepare("
        SELECT ea.alert_id, ea.message, ea.sent_at, b.booking_id, p.pet_name, u.full_name AS keeper_name, u.phone AS keeper_phone
        FROM emergency_alerts ea
        JOIN bookings b ON ea.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON ea.keeper_id = u.user_id
        WHERE ea.owner_id = ? AND ea.is_resolved = 0
        ORDER BY ea.sent_at DESC
    ");
    $stmt->execute([$ownerId]);
    $unresolvedAlerts = $stmt->fetchAll();

    // 4. Owner's Pets
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE owner_id = ? ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$ownerId]);
    $pets = $stmt->fetchAll();

    // 5. Recent Bookings preview
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.check_in_date, b.check_out_date, b.total_price, b.status,
               p.pet_name, u.full_name AS keeper_name
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.keeper_id = u.user_id
        WHERE b.owner_id = ?
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$ownerId]);
    $recentBookings = $stmt->fetchAll();

} catch (PDOException $e) {
    // Log exception in real world
}
?>

<div class="container" style="padding: 30px 20px;">

    <!-- Emergency Alerts Banner (If Any) -->
    <?php if (!empty($unresolvedAlerts)): ?>
        <?php foreach ($unresolvedAlerts as $alert): ?>
            <div class="emergency-banner">
                <div style="font-size: 1.8rem;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 1.1rem; text-transform: uppercase;">
                        Urgent Emergency Alert for <?= htmlspecialchars($alert['pet_name']) ?>!
                    </div>
                    <div style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Message from Keeper (<?= htmlspecialchars($alert['keeper_name']) ?> - Phone: <?= htmlspecialchars($alert['keeper_phone'] ?: 'N/A') ?>):</strong>
                        "<?= htmlspecialchars($alert['message']) ?>"
                    </div>
                    <small style="opacity: 0.85;">Sent at: <?= htmlspecialchars($alert['sent_at']) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; color: var(--primary);">Hello, <?= htmlspecialchars($ownerName) ?> 👋</h1>
            <p style="color: var(--text-muted);">Manage your pets, search verified keepers, and check active stays.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>/owner/add_pet.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Pet</a>
            <a href="<?= BASE_URL ?>/search.php" class="btn btn-secondary"><i class="fa-solid fa-magnifying-glass"></i> Find Keepers</a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid-3" style="margin-bottom: 35px;">
        <div class="card" style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-paw"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Registered Pets</span>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--text-dark);"><?= $totalPets ?></div>
            </div>
        </div>

        <div class="card" style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--secondary-light); color: var(--secondary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Active Bookings</span>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--text-dark);"><?= $activeBookingsCount ?></div>
            </div>
        </div>

        <div class="card" style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Care Status</span>
                <div style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-top: 4px;">Protected & Verified</div>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Section -->
    <div class="grid-2">
        <!-- My Pets Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-paw"></i> My Pets</h3>
                <a href="<?= BASE_URL ?>/owner/pets.php" class="btn btn-outline btn-sm">View All (<?= $totalPets ?>)</a>
            </div>

            <?php if (empty($pets)): ?>
                <div style="text-align: center; padding: 30px 10px; color: var(--text-muted);">
                    <i class="fa-solid fa-dog" style="font-size: 2.5rem; margin-bottom: 10px; color: var(--border-color);"></i>
                    <p>You haven't added any pets yet.</p>
                    <a href="<?= BASE_URL ?>/owner/add_pet.php" class="btn btn-primary btn-sm" style="margin-top: 12px;">+ Add Your First Pet</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($pets as $pet): ?>
                        <div style="display: flex; align-items: center; gap: 14px; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <img src="<?= BASE_URL ?>/uploads/pets/<?= htmlspecialchars($pet['pet_photo']) ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=100&h=100&fit=crop'"
                                 alt="<?= htmlspecialchars($pet['pet_name']) ?>"
                                 style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($pet['pet_name']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($pet['species']) ?> &bull; <?= htmlspecialchars($pet['breed']) ?> (<?= $pet['age'] ?> yrs)</div>
                            </div>
                            <a href="<?= BASE_URL ?>/owner/edit_pet.php?id=<?= $pet['pet_id'] ?>" class="btn btn-outline btn-sm" title="Edit Pet">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Bookings Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Recent Bookings</h3>
                <a href="<?= BASE_URL ?>/owner/my_bookings.php" class="btn btn-outline btn-sm">All Bookings</a>
            </div>

            <?php if (empty($recentBookings)): ?>
                <div style="text-align: center; padding: 30px 10px; color: var(--text-muted);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; margin-bottom: 10px; color: var(--border-color);"></i>
                    <p>No recent bookings found.</p>
                    <a href="<?= BASE_URL ?>/search.php" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Search Keepers & Book</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($recentBookings as $bk): ?>
                        <div style="padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <strong><?= htmlspecialchars($bk['pet_name']) ?> with <?= htmlspecialchars($bk['keeper_name']) ?></strong>
                                <span class="badge badge-<?= htmlspecialchars($bk['status']) ?>"><?= htmlspecialchars($bk['status']) ?></span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                                <span><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($bk['check_in_date']) ?> to <?= htmlspecialchars($bk['check_out_date']) ?></span>
                                <strong style="color: var(--secondary);">$<?= number_format($bk['total_price'], 2) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

