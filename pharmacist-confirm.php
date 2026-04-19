<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['ph_username']) || !isset($_SESSION['checkout_cart'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired or cart empty.']);
    exit;
}

$app_id = $_POST['app_id'] ?? ($_SESSION['checkout_app_id'] ?? null);

if (!$app_id) {
    echo json_encode(['success' => false, 'message' => 'Missing app_id.']);
    exit;
}

$con = mysqli_connect("localhost", "root", "", "myhmsdb");

// Check if already dispensed
$check = mysqli_query($con, "SELECT dispensed FROM prestb WHERE ID = '$app_id'");
if (!$check || mysqli_fetch_assoc($check)['dispensed'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Already dispensed or not found.']);
    exit;
}

mysqli_begin_transaction($con);

try {
    $cart = $_SESSION['checkout_cart'];
    
    foreach ($cart as $item) {
        $med_id = $item['id'];
        $qty = $item['qty'];
        $cost = $item['cost'];
        
        // Atomic stock quantity update (prevents negative stock due to race conditions)
        $updateQuery = "UPDATE medicationstb SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
        $stmt = mysqli_prepare($con, $updateQuery);
        mysqli_stmt_bind_param($stmt, "iii", $qty, $med_id, $qty);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) == 0) {
            throw new Exception("Insufficient stock for medication " . $item['name']);
        }
        
        // Insert into dispensed log
        $insertLog = "INSERT INTO dispensed_medstb (appointment_id, medication_id, quantity, total_cost, status) VALUES (?, ?, ?, ?, 'Paid')";
        $stmt2 = mysqli_prepare($con, $insertLog);
        mysqli_stmt_bind_param($stmt2, "iiii", $app_id, $med_id, $qty, $cost);
        mysqli_stmt_execute($stmt2);
    }
    
    // Update prescription status
    $updatePres = "UPDATE prestb SET dispensed = 1 WHERE ID = '$app_id'";
    mysqli_query($con, $updatePres);
    
    // Check if appointment fee is already paid
    $check_paid = mysqli_query($con, "SELECT payment FROM appointmenttb WHERE ID = '$app_id'");
    if ($p_row = mysqli_fetch_assoc($check_paid)) {
        if ($p_row['payment'] == 'Paid') {
            // Both conditions met: Meds Dispensed AND Fee Paid
            mysqli_query($con, "UPDATE appointmenttb SET userStatus=2, doctorStatus=2 WHERE ID = '$app_id'");
        }
    }
    
    mysqli_commit($con);
    
    // Clear session details
    unset($_SESSION['checkout_cart']);
    unset($_SESSION['checkout_app_id']);
    unset($_SESSION['checkout_total']);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($con);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($con);
?>
