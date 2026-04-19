<?php
/**
 * mpesa_confirm.php
 * Called after Successful Simulation (or real STK callback).
 * - Sets appointmenttb.payment = 'Paid'
 * - Sets appointmenttb.userStatus = 2 (Completed)
 * - Sets appointmenttb.doctorStatus = 2 (Completed)
 */
session_start();
$con = mysqli_connect("localhost", "root", "", "myhmsdb");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)($_POST['bill_id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing bill ID']);
    exit;
}

// 1. Mark appointment fee as Paid
$query = "UPDATE appointmenttb SET payment='Paid' WHERE ID='$id'";
$result = mysqli_query($con, $query);

// 2. Check for Conditional Completion
// An appointment is 'Completed' ONLY if:
// - The prescription exists AND (it's dispensed OR has no stock meds)
$pres_q = mysqli_query($con, "SELECT dispensed FROM prestb WHERE ID='$id'");
if ($pres_row = mysqli_fetch_assoc($pres_q)) {
    $is_dispensed = ($pres_row['dispensed'] == 1);
    
    // Check if any inventory medications were prescribed
    $meds_q = mysqli_query($con, "SELECT count(*) as count FROM doctor_prescribed_meds WHERE app_id='$id'");
    $has_stock_meds = (mysqli_fetch_assoc($meds_q)['count'] > 0);
    
    if ($is_dispensed || !$has_stock_meds) {
        // Both conditions met: Fee paid AND (Meds dispensed OR No meds to dispense)
        mysqli_query($con, "UPDATE appointmenttb SET userStatus=2, doctorStatus=2 WHERE ID='$id'");
    }
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($con)]);
}
