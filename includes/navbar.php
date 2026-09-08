<?php
/**
 * PetNest - Dynamic Role-Based Navbar
 */
$user = currentUser();
?>
<header class="site-header">
    <div class="container navbar">
        <a href="<?= BASE_URL ?>/index.php" class="brand-logo">
            <img src="<?= BASE_URL ?>/assets/images/logo.jpeg" alt="PetNest - Where Pets Feel at Home">
        </a>

        <button class="mobile-toggle" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <ul class="nav-menu">
            <li><a href="<?= BASE_URL ?>/index.php" class="nav-link"><i class="fa-solid fa-house"></i> Home</a></li>
            <li><a href="<?= BASE_URL ?>/search.php" class="nav-link"><i class="fa-solid fa-magnifying-glass"></i> Find Keepers</a></li>

            <?php if (!$user): ?>
                <!-- Guest Links -->
                <li><a href="<?= BASE_URL ?>/login.php" class="nav-link"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
                <li><a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Register</a></li>

            <?php elseif ($user['role'] === 'owner'): ?>
                <!-- Pet Owner Links -->
                <li><a href="<?= BASE_URL ?>/owner/dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/owner/pets.php" class="nav-link"><i class="fa-solid fa-paw"></i> My Pets</a></li>
                <li><a href="<?= BASE_URL ?>/owner/my_bookings.php" class="nav-link"><i class="fa-solid fa-calendar-check"></i> My Bookings</a></li>
                <li class="nav-user">
                    <span class="user-badge">Owner</span>
                    <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </li>

            <?php elseif ($user['role'] === 'keeper'): ?>
                <!-- Pet Keeper Links -->
                <li><a href="<?= BASE_URL ?>/keeper/dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/keeper/booking_requests.php" class="nav-link"><i class="fa-solid fa-bell"></i> Requests</a></li>
                <li><a href="<?= BASE_URL ?>/keeper/profile.php" class="nav-link"><i class="fa-solid fa-id-card"></i> My Profile</a></li>
                <li class="nav-user">
                    <span class="user-badge" style="background:#E0E7FF; color:#3730A3;">Keeper</span>
                    <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </li>

            <?php elseif ($user['role'] === 'admin'): ?>
                <!-- Admin Links -->
                <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> Admin Panel</a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php" class="nav-link"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="<?= BASE_URL ?>/admin/reviews.php" class="nav-link"><i class="fa-solid fa-star-half-stroke"></i> Reviews</a></li>
                <li class="nav-user">
                    <span class="user-badge" style="background:#FEE2E2; color:#991B1B;">Admin</span>
                    <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </li>

            <?php elseif ($user['role'] === 'operator'): ?>
                <!-- Operator Links -->
                <li><a href="<?= BASE_URL ?>/operator/dashboard.php" class="nav-link"><i class="fa-solid fa-headset"></i> Operator Panel</a></li>
                <li><a href="<?= BASE_URL ?>/operator/verify_keepers.php" class="nav-link"><i class="fa-solid fa-user-check"></i> Verifications</a></li>
                <li><a href="<?= BASE_URL ?>/operator/alerts.php" class="nav-link"><i class="fa-solid fa-triangle-exclamation"></i> Alerts</a></li>
                <li class="nav-user">
                    <span class="user-badge" style="background:#FEF3C7; color:#92400E;">Operator</span>
                    <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</header>
