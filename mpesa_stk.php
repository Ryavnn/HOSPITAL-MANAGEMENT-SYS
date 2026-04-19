<?php
/**
 * mpesa_stk.php
 * Handles STK Push initiation via AJAX.
 * Called by the Pay Bill modal in admin-panel.php.
 */

session_start();
require_once __DIR__ . '/mpesa_config.php';

header('Content-Type: application/json');

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$rawPhone = trim($_POST['phone']  ?? '');
$amount   = (int) ($_POST['amount']   ?? 0);
$billId   = trim($_POST['bill_id'] ?? '');

// ── Validate inputs ───────────────────────────────────────────────────────────
if (!$rawPhone || !$amount || !$billId) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ── Normalise phone to 2547XXXXXXXX ──────────────────────────────────────────
$phone = preg_replace('/\D/', '', $rawPhone);           // strip non-digits
if (strlen($phone) === 9)  $phone = '254' . $phone;    // 7XXXXXXXX  → 2547XXXXXXXX
if (str_starts_with($phone, '0')) $phone = '254' . substr($phone, 1); // 07XX → 2547XX
if (!str_starts_with($phone, '254') || strlen($phone) !== 12) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number. Use format 07XXXXXXXX']);
    exit;
}

// ── Step 1: Obtain OAuth access token ────────────────────────────────────────
function getMpesaToken(): ?string {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    $ch = curl_init(MPESA_BASE_URL . '/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return null;
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// ── Step 2: Initiate STK Push ─────────────────────────────────────────────────
function initiateSTKPush(string $token, string $phone, int $amount, string $billId): array {
    $timestamp = date('YmdHis');
    $password  = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => $amount,
        'PartyA'            => $phone,
        'PartyB'            => MPESA_SHORTCODE,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => MPESA_CALLBACK_URL,
        'AccountReference'  => MPESA_ACCOUNT_REF,
        'TransactionDesc'   => 'Consultation Bill #' . $billId,
    ];

    $ch = curl_init(MPESA_BASE_URL . '/mpesa/stkpush/v1/processrequest');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return ['errorMessage' => 'cURL error: ' . $err];
    return json_decode($response, true) ?? ['errorMessage' => 'Empty response'];
}

// ── Execute ───────────────────────────────────────────────────────────────────
$token = getMpesaToken();
if (!$token) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to authenticate with M-Pesa. Check your CONSUMER_KEY and CONSUMER_SECRET.',
    ]);
    exit;
}

$result = initiateSTKPush($token, $phone, $amount, $billId);

if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
    // Store for later status polling
    $_SESSION['mpesa_checkout_id'] = $result['CheckoutRequestID'];
    $_SESSION['mpesa_bill_id']     = $billId;

    echo json_encode([
        'success'     => true,
        'message'     => 'STK Push sent. Enter your M-Pesa PIN on your phone.',
        'checkout_id' => $result['CheckoutRequestID'],
    ]);
} else {
    $msg = $result['errorMessage']
        ?? $result['CustomerMessage']
        ?? ($result['ResultDesc'] ?? 'STK Push failed. Check your credentials and shortcode.');

    echo json_encode(['success' => false, 'message' => $msg, 'raw' => $result]);
}
