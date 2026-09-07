<?php
/**
 * PetNest - Create Booking Form
 * Member 3 Module
 */

$pageTitle = "Book Pet Care - PetNest";
require_once __DIR__ . '/includes/header.php';
requireRole('owner');

$ownerId  = (int)$_SESSION['user_id'];
$keeperId = (int)($_GET['keeper_id'] ?? 0);
$petId    = (int)($_GET['pet_id'] ?? 0);

if ($keeperId <= 0) {
    setFlash('warning', 'Please select a pet keeper first to make a booking.');
    redirect('/search.php');
}

// Fetch keeper details
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.profile_photo, u.phone,
               kp.location, kp.price_per_day, kp.breeds_experienced, kp.availability_status
        FROM users u
        JOIN keeper_profiles kp ON u.user_id = kp.user_id
        WHERE u.user_id = ? AND u.role = 'keeper' AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$keeperId]);
    $keeper = $stmt->fetch();

    if (!$keeper) {
        setFlash('danger', 'Selected pet keeper is not found or inactive.');
        redirect('/search.php');
    }

    if ($keeper['availability_status'] !== 'available') {
        setFlash('warning', 'This keeper is currently not accepting new bookings. Please select another keeper.');
        redirect('/search.php');
    }

    // Fetch owner's registered pets
    $stmtPets = $pdo->prepare("SELECT pet_id, pet_name, species, breed, medical_notes FROM pets WHERE owner_id = ? ORDER BY pet_name ASC");
    $stmtPets->execute([$ownerId]);
    $myPets = $stmtPets->fetchAll();

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    redirect('/search.php');
}

$errors = [];
$checkInDate  = $_POST['check_in_date'] ?? '';
$checkOutDate = $_POST['check_out_date'] ?? '';
$selectedPet  = (int)($_POST['pet_id'] ?? $petId);
$feeding      = trim($_POST['feeding_schedule'] ?? '');
$bath         = trim($_POST['bath_schedule'] ?? '');
$medicine     = trim($_POST['medicine_details'] ?? '');
$specialNotes = trim($_POST['special_notes'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPet  = (int)($_POST['pet_id'] ?? 0);
    $checkInDate  = $_POST['check_in_date'] ?? '';
    $checkOutDate = $_POST['check_out_date'] ?? '';

    // Validations
    if ($selectedPet <= 0) {
        $errors[] = "Please select a pet to board.";
    }
    if (empty($checkInDate) || empty($checkOutDate)) {
        $errors[] = "Please select both check-in and check-out dates.";
    } else {
        $inTime  = strtotime($checkInDate);
        $outTime = strtotime($checkOutDate);
        $today   = strtotime(date('Y-m-d'));

        if ($inTime < $today) {
            $errors[] = "Check-in date cannot be in the past.";
        }
        if ($outTime <= $inTime) {
            $errors[] = "Check-out date must be after the check-in date.";
        }
    }

    if (empty($errors)) {
        $numDays   = (int)ceil(($outTime - $inTime) / (60 * 60 * 24));
        $dailyRate = (float)$keeper['price_per_day'];
        $basePrice = $numDays * $dailyRate;
        
        // Surcharge calculation: +$10.00 if special medication or complex care is required
        $surcharge = !empty($medicine) ? 10.00 : 0.00;
        $totalPrice = $basePrice + $surcharge;

        try {
            $pdo->beginTransaction();

            // 1. Insert Booking record
            $stmtBooking = $pdo->prepare("
                INSERT INTO bookings (pet_id, owner_id, keeper_id, check_in_date, check_out_date, num_days, base_price, surcharge, total_price, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmtBooking->execute([$selectedPet, $ownerId, $keeperId, $checkInDate, $checkOutDate, $numDays, $basePrice, $surcharge, $totalPrice]);
            $bookingId = (int)$pdo->lastInsertId();

            // 2. Insert Care Instructions
            $stmtInstructions = $pdo->prepare("
                INSERT INTO instructions (booking_id, feeding_schedule, bath_schedule, medicine_details, special_notes, instruction_surcharge)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInstructions->execute([$bookingId, $feeding, $bath, $medicine, $specialNotes, $surcharge]);

            $pdo->commit();

            setFlash('success', "Booking request submitted! The keeper has been notified to review and confirm your stay.");
            redirect('/owner/my_bookings.php');

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to submit booking: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 850px; padding: 35px 20px;">
    
    <!-- Keeper Header Summary -->
    <div class="card" style="margin-bottom: 25px; padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; background: #FAF7F2;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($keeper['profile_photo']) ?>" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($keeper['full_name']) ?>&background=6B4226&color=fff'" 
                 alt="<?= htmlspecialchars($keeper['full_name']) ?>" 
                 style="width: 65px; height: 65px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Booking with Keeper</span>
                <h3 style="font-size: 1.3rem; color: var(--primary); margin: 0;"><?= htmlspecialchars($keeper['full_name']) ?></h3>
                <small style="color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($keeper['location']) ?></small>
            </div>
        </div>
        <div style="text-align: right;">
            <span style="font-size: 0.8rem; color: var(--text-muted);">Daily Rate</span>
            <div style="font-size: 1.6rem; font-weight: 800; color: var(--secondary);">$<?= number_format($keeper['price_per_day'], 2) ?>/day</div>
        </div>
    </div>

    <?php if (empty($myPets)): ?>
        <div class="card" style="text-align: center; padding: 50px 20px;">
            <i class="fa-solid fa-paw" style="font-size: 3rem; color: var(--border-color); margin-bottom: 12px;"></i>
            <h2>You Have No Registered Pets Yet</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Please add your pet profile first before booking a stay.</p>
            <a href="<?= BASE_URL ?>/owner/add_pet.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Pet Profile First</a>
        </div>
    <?php else: ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-calendar-check"></i> Book Stay & Provide Care Details</h2>
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

            <form action="<?= BASE_URL ?>/book.php?keeper_id=<?= $keeperId ?>" method="POST" id="bookingForm" style="margin-top: 15px;">
                
                <!-- 1. Select Pet -->
                <div class="form-group">
                    <label class="form-label" for="pet_id"><i class="fa-solid fa-paw"></i> Select Your Pet *</label>
                    <select name="pet_id" id="pet_id" class="form-select" required>
                        <option value="">-- Choose which pet to board --</option>
                        <?php foreach ($myPets as $p): ?>
                            <option value="<?= $p['pet_id'] ?>" <?= $selectedPet === (int)$p['pet_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['pet_name']) ?> (<?= htmlspecialchars($p['species']) ?> - <?= htmlspecialchars($p['breed']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Stay Dates -->
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="check_in_date"><i class="fa-solid fa-calendar-day"></i> Check-In Date (Drop Off) *</label>
                        <input type="date" id="check_in_date" name="check_in_date" class="form-control" value="<?= htmlspecialchars($checkInDate) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="check_out_date"><i class="fa-solid fa-calendar-day"></i> Check-Out Date (Pick Up) *</label>
                        <input type="date" id="check_out_date" name="check_out_date" class="form-control" value="<?= htmlspecialchars($checkOutDate) ?>" required>
                    </div>
                </div>

                <!-- 3. Care Instructions (4 Sections) -->
                <div style="background: #FAF8F5; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; margin: 25px 0;">
                    <h3 style="font-size: 1.1rem; color: var(--primary); margin-bottom: 14px;">
                        <i class="fa-solid fa-clipboard-list"></i> Daily Care Instructions for the Keeper
                    </h3>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="feeding_schedule">🍽️ Feeding Times & Portions</label>
                            <textarea id="feeding_schedule" name="feeding_schedule" rows="2" class="form-control" placeholder="e.g. 1 cup dry food at 8am and 6pm. Fresh water always."><?= htmlspecialchars($feeding) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="bath_schedule">🛁 Bath & Grooming Preferences</label>
                            <textarea id="bath_schedule" name="bath_schedule" rows="2" class="form-control" placeholder="e.g. Brush fur every 2 days. Bath once if muddy."><?= htmlspecialchars($bath) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="medicine_details">💊 Medication & Dosage (+$10 Surcharge)</label>
                            <textarea id="medicine_details" name="medicine_details" rows="2" class="form-control" placeholder="e.g. 1 tablet heartgard on 3rd day with morning meal."><?= htmlspecialchars($medicine) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="special_notes">📝 Behavior & Special Routines</label>
                            <textarea id="special_notes" name="special_notes" rows="2" class="form-control" placeholder="e.g. Loves daily 20min fetch. Friendly with cats."><?= htmlspecialchars($specialNotes) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Real-Time Price Calculation Box -->
                <div class="card" style="background: var(--primary-light); border: 1px solid var(--primary); padding: 18px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary); font-size: 1.1rem;">Estimated Booking Total:</div>
                            <small id="priceBreakdown" style="color: var(--text-dark);">Select valid dates to calculate pricing.</small>
                        </div>
                        <div id="totalDisplay" style="font-size: 2rem; font-weight: 800; color: var(--primary);">$0.00</div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                        <i class="fa-solid fa-paper-plane"></i> Submit Booking Request
                    </button>
                    <a href="<?= BASE_URL ?>/keeper_profile.php?id=<?= $keeperId ?>" class="btn btn-outline btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
// Real-time price calculation
const dailyRate = <?= (float)$keeper['price_per_day'] ?>;
const inInput = document.getElementById('check_in_date');
const outInput = document.getElementById('check_out_date');
const medInput = document.getElementById('medicine_details');
const breakdownEl = document.getElementById('priceBreakdown');
const totalEl = document.getElementById('totalDisplay');

function calculateTotal() {
    if (!inInput.value || !outInput.value) {
        breakdownEl.innerText = "Please choose check-in and check-out dates.";
        totalEl.innerText = "$0.00";
        return;
    }

    const start = new Date(inInput.value);
    const end = new Date(outInput.value);
    const diffTime = end - start;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays <= 0) {
        breakdownEl.innerText = "Check-out date must be after check-in.";
        totalEl.innerText = "$0.00";
        return;
    }

    const base = diffDays * dailyRate;
    const surcharge = (medInput && medInput.value.trim().length > 0) ? 10.00 : 0.00;
    const total = base + surcharge;

    let text = `${diffDays} days × $${dailyRate.toFixed(2)}`;
    if (surcharge > 0) {
        text += ` + $10.00 care surcharge`;
    }
    breakdownEl.innerText = text;
    totalEl.innerText = `$${total.toFixed(2)}`;
}

if (inInput && outInput) {
    inInput.addEventListener('change', calculateTotal);
    outInput.addEventListener('change', calculateTotal);
    if (medInput) {
        medInput.addEventListener('input', calculateTotal);
    }
    // Calculate on initial load if prefilled
    calculateTotal();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
