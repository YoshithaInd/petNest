<?php
/**
 * PetNest - Payment Checkout Gateway
 * Member 3 Module
 */

$pageTitle = "Secure Checkout - PetNest";
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$ownerId   = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    setFlash('danger', 'Invalid booking specified for checkout.');
    redirect('/owner/my_bookings.php');
}

// Fetch booking & validation
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.pet_name, p.species, p.breed,
               u.full_name AS keeper_name, u.email AS keeper_email,
               i.feeding_schedule, i.medicine_details, i.instruction_surcharge,
               pay.payment_status
        FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        JOIN users u ON b.keeper_id = u.user_id
        LEFT JOIN instructions i ON b.booking_id = i.booking_id
        LEFT JOIN payments pay ON b.booking_id = pay.booking_id
        WHERE b.booking_id = ? AND b.owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId, $ownerId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        setFlash('danger', 'Booking not found or access denied.');
        redirect('/owner/my_bookings.php');
    }

    if ($booking['status'] !== 'confirmed') {
        setFlash('warning', 'Only confirmed bookings can be paid for.');
        redirect('/owner/my_bookings.php');
    }

    if (!empty($booking['payment_status']) && $booking['payment_status'] === 'paid') {
        setFlash('info', 'This booking has already been paid in full.');
        redirect('/payment/receipt.php?booking_id=' . $bookingId);
    }

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    redirect('/owner/my_bookings.php');
}

$errors = [];

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? 'stripe';
    $amount = (float)$booking['total_price'];

    if (!in_array($paymentMethod, ['stripe', 'paypal'], true)) {
        $paymentMethod = 'stripe';
    }

    // Generate unique transaction ID
    $prefix = ($paymentMethod === 'stripe') ? 'txn_str_' : 'txn_pp_';
    $transactionId = $prefix . strtoupper(bin2hex(random_bytes(8)));

    try {
        $pdo->beginTransaction();

        // 1. Insert Payment Record
        $stmtPay = $pdo->prepare("
            INSERT INTO payments (booking_id, owner_id, amount, payment_method, transaction_id, payment_status)
            VALUES (?, ?, ?, ?, ?, 'paid')
        ");
        $stmtPay->execute([$bookingId, $ownerId, $amount, $paymentMethod, $transactionId]);

        $pdo->commit();

        setFlash('success', 'Payment processed successfully! Your booking is fully secured.');
        redirect('/payment/receipt.php?booking_id=' . $bookingId);

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Payment processing failed: " . $e->getMessage();
    }
}
?>

<div class="container" style="max-width: 800px; padding: 40px 20px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="font-size: 2.2rem; color: var(--secondary); margin-bottom: 6px;">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1 style="font-size: 1.8rem; color: var(--primary);">Secure Online Checkout</h1>
        <p style="color: var(--text-muted);">256-Bit Encrypted Payment Processing for Pet Boarding</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="display: block;">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <!-- Left: Order Summary -->
        <div class="card" style="background: #FAF8F5;">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-receipt"></i> Booking Summary</h3>
            </div>

            <div style="font-size: 0.9rem; line-height: 1.8; color: var(--text-dark);">
                <div style="display: flex; justify-content: space-between;">
                    <span><strong>Pet:</strong></span>
                    <span><?= htmlspecialchars($booking['pet_name']) ?> (<?= htmlspecialchars($booking['breed']) ?>)</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><strong>Pet Keeper:</strong></span>
                    <span><?= htmlspecialchars($booking['keeper_name']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><strong>Stay Dates:</strong></span>
                    <span><?= htmlspecialchars($booking['check_in_date']) ?> to <?= htmlspecialchars($booking['check_out_date']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><strong>Duration:</strong></span>
                    <span><?= $booking['num_days'] ?> Days</span>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 14px 0;">

                <div style="display: flex; justify-content: space-between;">
                    <span>Base Boarding Fee:</span>
                    <span>$<?= number_format($booking['base_price'], 2) ?></span>
                </div>

                <?php if ($booking['surcharge'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; color: var(--primary);">
                        <span>Special Care / Medication:</span>
                        <span>+$<?= number_format($booking['surcharge'], 2) ?></span>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; color: var(--secondary); margin-top: 12px; border-top: 2px solid var(--border-color); padding-top: 10px;">
                    <span>Total Amount:</span>
                    <span>$<?= number_format($booking['total_price'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Right: Payment Gateway Selection Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1.15rem;"><i class="fa-solid fa-credit-card"></i> Payment Method</h3>
            </div>

            <form action="<?= BASE_URL ?>/payment/checkout.php?booking_id=<?= $bookingId ?>" method="POST" id="checkoutForm">
                
                <!-- Payment Method Tabs -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label style="cursor: pointer;">
                            <input type="radio" name="payment_method" value="stripe" checked style="display:none;" onchange="switchMethod('stripe')">
                            <div id="stripeTab" style="padding: 12px; border: 2px solid var(--primary); border-radius: var(--radius-md); text-align: center; background: var(--primary-light);">
                                <i class="fa-solid fa-credit-card" style="font-size: 1.3rem; color: var(--primary); display: block; margin-bottom: 4px;"></i>
                                <strong style="font-size: 0.85rem;">Stripe Card</strong>
                            </div>
                        </label>

                        <label style="cursor: pointer;">
                            <input type="radio" name="payment_method" value="paypal" style="display:none;" onchange="switchMethod('paypal')">
                            <div id="paypalTab" style="padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius-md); text-align: center; background: #FFF;">
                                <i class="fa-brands fa-paypal" style="font-size: 1.3rem; color: #0079C1; display: block; margin-bottom: 4px;"></i>
                                <strong style="font-size: 0.85rem;">PayPal</strong>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Stripe Card Fields Container -->
                <div id="cardFields">
                    <div class="form-group">
                        <label class="form-label" for="card_num">Card Number (Test Mode)</label>
                        <input type="text" id="card_num" class="form-control" value="4242 •••• •••• 4242" placeholder="4242 4242 4242 4242" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="card_exp">Expiry</label>
                            <input type="text" id="card_exp" class="form-control" value="12/28" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="card_cvc">CVC</label>
                            <input type="text" id="card_cvc" class="form-control" value="123" placeholder="123" required>
                        </div>
                    </div>
                </div>

                <!-- PayPal Info Box -->
                <div id="paypalFields" style="display: none; background: #F0F8FF; border: 1px solid #BCE8F1; padding: 14px; border-radius: var(--radius-md); margin-bottom: 15px; font-size: 0.85rem; color: #31708F;">
                    <i class="fa-brands fa-paypal"></i> You will be connected to PayPal Sandbox to authorize payment of <strong>$<?= number_format($booking['total_price'], 2) ?></strong>.
                </div>

                <button type="submit" class="btn btn-secondary btn-lg" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-lock"></i> Pay $<?= number_format($booking['total_price'], 2) ?> Now
                </button>
            </form>
        </div>

    </div>
</div>

<script>
function switchMethod(method) {
    const stripeTab = document.getElementById('stripeTab');
    const paypalTab = document.getElementById('paypalTab');
    const cardFields = document.getElementById('cardFields');
    const paypalFields = document.getElementById('paypalFields');

    if (method === 'stripe') {
        stripeTab.style.borderColor = 'var(--primary)';
        stripeTab.style.background = 'var(--primary-light)';
        paypalTab.style.borderColor = 'var(--border-color)';
        paypalTab.style.background = '#FFF';
        cardFields.style.display = 'block';
        paypalFields.style.display = 'none';
    } else {
        paypalTab.style.borderColor = '#0079C1';
        paypalTab.style.background = '#F0F8FF';
        stripeTab.style.borderColor = 'var(--border-color)';
        stripeTab.style.background = '#FFF';
        cardFields.style.display = 'none';
        paypalFields.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
