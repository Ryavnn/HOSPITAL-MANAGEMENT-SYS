<?php
session_start();
if(!isset($_SESSION['ph_username'])){
    header("Location: index.php");
    exit();
}
$con = mysqli_connect("localhost","root","","myhmsdb");

$app_id = $_GET['app_id'] ?? ($_SESSION['checkout_app_id'] ?? null);

if(!$app_id) {
    die("Invalid Appointment ID");
}

// Fetch basic info
$query = "SELECT p.*, p.pid as patient_id, pr.contact 
          FROM prestb p 
          LEFT JOIN patreg pr ON p.pid = pr.pid 
          WHERE p.ID = '$app_id'";
$res = mysqli_query($con, $query);
$prescription_details = mysqli_fetch_array($res);

if($prescription_details['dispensed'] == 1) {
    die("<div style='padding:50px;text-align:center;'><h3>Prescription already dispensed.</h3><br><a href='pharmacist-panel.php'>Return to Dashboard</a></div>");
}

$error_msg = "";
$checkout_ready = false;
$total_amount = 0;

if(isset($_POST['prepare_billing'])) {
    $med_qtys = $_POST['med_qty']; // assoc array [med_id] => qty
    $cart = [];
    
    foreach($med_qtys as $med_id => $qty) {
        $qty = (int)$qty;
        if($qty > 0) {
            // Validate stock
            $med_query = mysqli_query($con, "SELECT * FROM medicationstb WHERE id='$med_id'");
            $med = mysqli_fetch_array($med_query);
            if($med['stock_quantity'] < $qty) {
                $error_msg .= "Insufficient stock for {$med['name']}. Available: {$med['stock_quantity']}.<br>";
                break;
            }
            $cost = $med['price'] * $qty;
            $total_amount += $cost;
            $cart[] = [
                'id' => $med_id,
                'name' => $med['name'],
                'qty' => $qty,
                'cost' => $cost
            ];
        }
    }
    
    if(empty($cart)) {
        $error_msg = "Please select at least one medication.";
    }
    
    if(empty($error_msg) && $error_msg == "") {
        $_SESSION['checkout_cart'] = $cart;
        $_SESSION['checkout_app_id'] = $app_id;
        $_SESSION['checkout_total'] = $total_amount;
        $checkout_ready = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pharmacist Checkout</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <style>
        body{ background-color: #f8f9fa; font-family: sans-serif; }
        .card-header{ background-color:#3c50c1; color:white; }
        .checkout-box{ padding:20px; background:#fff; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body style="padding-top: 50px;">
<div class="container">
    <a href="pharmacist-panel.php" class="btn btn-secondary mb-3"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    
    <?php if($error_msg != ""): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="checkout-box mb-4">
                <h4 style="color:#3c50c1;">Patient Details</h4>
                <hr>
                <p><strong>Name:</strong> <?php echo $prescription_details['fname']." ".$prescription_details['lname']; ?></p>
                <p><strong>Contact:</strong> <?php echo $prescription_details['contact']; ?></p>
                <p><strong>Disease:</strong> <?php echo $prescription_details['disease']; ?></p>
                <p><strong>Prescription Text:</strong><br/> 
                <span style="font-style:italic;color:#555;"><?php echo nl2br($prescription_details['prescription']); ?></span></p>
            </div>
            
            <?php if($checkout_ready): ?>
            <div class="checkout-box" style="background:#e8f5e9;border:1px solid #00a651;">
                <h4 style="color:#00a651;">Cart Summary</h4>
                <hr>
                <table class="table table-sm">
                    <tr><th>Medication</th><th>Qty</th><th>Cost</th></tr>
                    <?php foreach($_SESSION['checkout_cart'] as $item): ?>
                        <tr>
                            <td><?php echo $item['name']; ?></td>
                            <td><?php echo $item['qty']; ?></td>
                            <td>KES <?php echo $item['cost']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><th colspan="2">Total Amount</th><th>KES <?php echo $total_amount; ?></th></tr>
                </table>
                <button class="btn btn-success btn-block" onclick="openMpesaModal('<?php echo $app_id; ?>', '<?php echo $total_amount; ?>')">
                    Proceed to M-Pesa Payment
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <?php if(!$checkout_ready): ?>
            <div class="checkout-box">
                <h4 style="color:#3c50c1;">Allocate Medications</h4>
                <hr>
                <form method="post" action="pharmacist-checkout.php?app_id=<?php echo $app_id; ?>">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr><th>Medication Name</th><th>Unit Price</th><th>Stock</th><th>Qty to Dispense</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $presc_med_q = mysqli_query($con, "SELECT med_id, quantity FROM doctor_prescribed_meds WHERE app_id='$app_id'");
                            $doc_qtys = [];
                            if($presc_med_q){
                                while($dpm = mysqli_fetch_array($presc_med_q)){
                                    $doc_qtys[$dpm['med_id']] = $dpm['quantity'];
                                }
                            }

                            $med_res = mysqli_query($con, "SELECT * FROM medicationstb WHERE stock_quantity > 0 ORDER BY name ASC");
                            while($med = mysqli_fetch_array($med_res)){
                                $suggested_qty = isset($doc_qtys[$med['id']]) ? $doc_qtys[$med['id']] : 0;
                                echo "<tr>";
                                echo "<td>{$med['name']}</td>";
                                echo "<td>KES {$med['price']}</td>";
                                echo "<td>{$med['stock_quantity']}</td>";
                                echo "<td><input type='number' name='med_qty[{$med['id']}]' class='form-control form-control-sm' min='0' max='{$med['stock_quantity']}' value='{$suggested_qty}'></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <button type="submit" name="prepare_billing" class="btn btn-primary float-right">Prepare Billing</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== M-PESA STK PUSH MODAL (Reused logic) ===== -->
<div class="modal fade" id="mpesaModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:400px;">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header" style="background:#00a651;color:white;">
        <h5 class="modal-title">M-PESA Payment</h5>
        <button type="button" class="close" data-dismiss="modal" style="color:white;"><span>&times;</span></button>
      </div>

      <div class="modal-body text-center" style="padding:30px;">
        <!-- STEP 1: Enter phone -->
        <div id="mpesa-step1">
          <div style="background:#00a651;border-radius:50%;width:70px;height:70px;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;color:white;">
            <i class="fa fa-mobile fa-2x"></i>
          </div>
          <h5>Pay via M-Pesa</h5>
          <p class="text-muted">Enter registered phone number</p>

          <input type="tel" class="form-control text-center mb-3" id="mpesaPhone" value="<?php echo htmlspecialchars($prescription_details['contact']); ?>">
          <p>Amount: <strong style="color:#00a651;">KES <span id="mpesa-amount">0</span></strong></p>

          <button id="stk-send-btn" class="btn btn-success btn-block mb-2" onclick="simulateSTKPush()">Send STK Push</button>
          
          <div id="mpesa-stk-error" style="display:none;color:red;font-size:13px;"></div>

          <hr>
          <button class="btn btn-warning btn-block btn-sm" onclick="simulateSuccess()">Simulate Success Event</button>
        </div>

        <!-- STEP 2: Waiting -->
        <div id="mpesa-step2" style="display:none;">
          <div style="margin-bottom:20px;">Spinner...</div>
          <h5>STK Push Sent!</h5>
          <p>Check phone <strong id="mpesa-phone-display"></strong></p>
          <hr>
          <button class="btn btn-warning btn-block btn-sm" onclick="simulateSuccess()">Simulate Success Event</button>
        </div>

        <!-- STEP 3: Success -->
        <div id="mpesa-step3" style="display:none;">
          <div style="color:#00a651;margin-bottom:15px;"><i class="fa fa-check-circle fa-3x"></i></div>
          <h5 style="color:#00a651;">Payment Confirmed!</h5>
          <p>Database is being updated...</p>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script>
var mpesaBillId = '';
var mpesaAmount = 0;

function openMpesaModal(id, amount) {
  mpesaBillId = id;
  mpesaAmount = amount;
  $('#mpesa-amount').text(amount);
  $('#mpesa-step1').show();
  $('#mpesa-step2').hide();
  $('#mpesa-step3').hide();
  $('#mpesaModal').modal('show');
}

function simulateSTKPush() {
  $('#mpesa-phone-display').text($('#mpesaPhone').val());
  $('#mpesa-step1').hide();
  $('#mpesa-step2').show();
}

function simulateSuccess() {
  $('#mpesa-step1').hide();
  $('#mpesa-step2').hide();
  $('#mpesa-step3').show();
  
  // Actually confirm in DB
  var formData = new FormData();
  formData.append('app_id', mpesaBillId);

  fetch('pharmacist-confirm.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            setTimeout(() => {
                window.location.href = 'pharmacist-panel.php';
            }, 1500);
        } else {
            alert("Error finalizing transaction: " + data.message);
        }
    }).catch(err => {
        alert("Network Error during confirmation.");
    });
}
</script>
</body>
</html>
