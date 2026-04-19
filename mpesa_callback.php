<?php
/**
 * mpesa_callback.php
 * Safaricom posts payment results to this URL after the customer enters their PIN.
 * Must be publicly reachable (use ngrok for local testing).
 */

require_once __DIR__ . '/mpesa_config.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// ── Log every callback for debugging ─────────────────────────────────────────
$logFile = __DIR__ . '/mpesa_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " CALLBACK: $raw\n\n", FILE_APPEND | LOCK_EX);

// ── Parse callback ────────────────────────────────────────────────────────────
$callback    = $data['Body']['stkCallback']            ?? [];
$resultCode  = $callback['ResultCode']                 ?? -1;
$resultDesc  = $callback['ResultDesc']                 ?? 'Unknown';
$checkoutId  = $callback['CheckoutRequestID']          ?? '';
$metadata    = $callback['CallbackMetadata']['Item']   ?? [];

if ((int)$resultCode === 0) {
    // ── Payment successful ────────────────────────────────────────────────────
    $mpesaRef = $amount = $phone = $transDate = '';
    foreach ($metadata as $item) {
        switch ($item['Name']) {
            case 'MpesaReceiptNumber': $mpesaRef  = $item['Value']; break;
            case 'Amount':             $amount    = $item['Value']; break;
            case 'PhoneNumber':        $phone     = $item['Value']; break;
            case 'TransactionDate':    $transDate = $item['Value']; break;
        }
    }

    // Optional: update payment status in DB
    // $con = mysqli_connect("localhost","root","","myhmsdb");
    // mysqli_query($con, "UPDATE appointmenttb SET payment='Paid', mpesa_ref='$mpesaRef'
    //                     WHERE ID = '$checkoutId'");

    file_put_contents($logFile,
        date('Y-m-d H:i:s') . " SUCCESS: Ref=$mpesaRef Amount=$amount Phone=$phone\n\n",
        FILE_APPEND | LOCK_EX
    );
} else {
    // ── Payment failed / cancelled ────────────────────────────────────────────
    file_put_contents($logFile,
        date('Y-m-d H:i:s') . " FAILED: Code=$resultCode Desc=$resultDesc\n\n",
        FILE_APPEND | LOCK_EX
    );
}

// ── Acknowledge Safaricom (required) ─────────────────────────────────────────
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
