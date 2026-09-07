<?php
/**
 * PetNest - Shared Page Header
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = $pageTitle ?? 'PetNest - Where Pets Feel at Home';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container" style="margin-top: 20px;">
    <?php displayFlash(); ?>
</div>

<main>
