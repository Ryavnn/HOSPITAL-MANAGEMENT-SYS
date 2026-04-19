<?php
session_start();
if(!isset($_SESSION['ph_username'])){
    header("Location: index.php");
    exit();
}
$con = mysqli_connect("localhost","root","","myhmsdb");

if(isset($_GET["generate_receipt"])){
  require_once("TCPDF/tcpdf.php");
  $obj_pdf = new TCPDF('P',PDF_UNIT,PDF_PAGE_FORMAT,true,'UTF-8',false);
  $obj_pdf -> SetCreator(PDF_CREATOR);
  $obj_pdf -> SetTitle("Pharmacy Receipt");
  $obj_pdf -> SetHeaderData('','',PDF_HEADER_TITLE,PDF_HEADER_STRING);
  $obj_pdf -> SetHeaderFont(Array(PDF_FONT_NAME_MAIN,'',PDF_FONT_SIZE_MAIN));
  $obj_pdf -> SetFooterFont(Array(PDF_FONT_NAME_MAIN,'',PDF_FONT_SIZE_MAIN));
  $obj_pdf -> SetDefaultMonospacedFont('helvetica');
  $obj_pdf -> SetFooterMargin(PDF_MARGIN_FOOTER);
  $obj_pdf -> SetMargins(PDF_MARGIN_LEFT,'5',PDF_MARGIN_RIGHT);
  $obj_pdf -> SetPrintHeader(false);
  $obj_pdf -> SetPrintFooter(false);
  $obj_pdf -> SetAutoPageBreak(TRUE, 10);
  $obj_pdf -> SetFont('helvetica','',12);
  $obj_pdf -> AddPage();

  $content = '';
  $content .= '
      <br/>
      <h2 align ="center"> Global Hospitals - Pharmacy</h2></br>
      <h3 align ="center"> Payment Receipt</h3><hr/>
  ';
  
  $app_id = $_GET['ID'];
  $query = mysqli_query($con, "SELECT p.fname, p.lname, p.doctor, SUM(d.total_cost) as total 
                               FROM prestb p 
                               JOIN dispensed_medstb d ON p.ID = d.appointment_id 
                               WHERE p.ID = '$app_id' GROUP BY p.ID");
  $row = mysqli_fetch_array($query);
  if($row) {
      $content .= '<h4>Patient Name: '.$row["fname"].' '.$row["lname"].'</h4>';
      $content .= '<h4>Prescribing Doctor: '.$row["doctor"].'</h4><br>';
      
      $content .= '<table border="1" cellspacing="0" cellpadding="5" width="100%">';
      $content .= '<tr style="background-color:#f2f2f2;"><th><b>Medication</b></th><th width="20%"><b>Quantity</b></th><th width="30%"><b>Subtotal</b></th></tr>';
      
      $med_q = mysqli_query($con, "SELECT m.name, d.quantity, d.total_cost FROM dispensed_medstb d JOIN medicationstb m ON d.medication_id = m.id WHERE d.appointment_id = '$app_id'");
      while($m = mysqli_fetch_array($med_q)){
          $content .= '<tr><td>'.$m['name'].'</td><td>'.$m['quantity'].'</td><td>KES '.$m['total_cost'].'</td></tr>';
      }
      
      $content .= '<tr style="background-color:#e8f5e9;"><td colspan="2" align="right"><b>Total Paid:</b></td><td><b>KES '.$row["total"].'</b></td></tr>';
      $content .= '</table>';
  } else {
      $content .= '<p>No records found.</p>';
  }
  
  $obj_pdf -> writeHTML($content);
  ob_end_clean();
  $obj_pdf -> Output("pharmacy_receipt.pdf",'I');
  exit;
}

/* Handle Add Medication */
if(isset($_POST['add_med'])) {
    $med_name = $_POST['med_name'];
    $med_price = $_POST['med_price'];
    $med_qty = $_POST['med_qty'];
    $insert = "INSERT INTO medicationstb (name, price, stock_quantity) VALUES ('$med_name', '$med_price', '$med_qty')";
    mysqli_query($con, $insert);
    echo "<script>alert('Medication added successfully.');</script>";
}

/* Handle Update Medication Stock */
if(isset($_POST['update_med'])) {
    $med_id = $_POST['med_id'];
    $med_price = $_POST['med_price'];
    $med_qty = $_POST['med_qty'];
    $update = "UPDATE medicationstb SET price='$med_price', stock_quantity='$med_qty' WHERE id='$med_id'";
    mysqli_query($con, $update);
    echo "<script>alert('Medication updated successfully.');</script>";
}

/* Handle Delete Medication */
if(isset($_POST['delete_med'])) {
    $med_id = $_POST['med_id'];
    $delete = "DELETE FROM medicationstb WHERE id='$med_id'";
    mysqli_query($con, $delete);
    echo "<script>alert('Medication deleted successfully.');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" type="text/css" href="font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans&display=swap" rel="stylesheet">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <a class="navbar-brand" href="#"><i class="fa fa-user-plus" aria-hidden="true"></i> Global Hospital Pharmacist Panel </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
         <ul class="navbar-nav mr-auto">
         <li class="nav-item">
            <a class="nav-link" href="logout1.php"><i class="fa fa-sign-out" aria-hidden="true"></i>Logout</a>
         </li>
         <li class="nav-item">
            <a class="nav-link" href="#"></a>
         </li>
         </ul>
    </div>
  </nav>
</head>
<style type="text/css">
    button:hover{cursor:pointer;}
    #inputbtn:hover{cursor:pointer;}
    body{
        font-family: 'IBM Plex Sans', sans-serif;
        background-color: #f8f9fa;
        padding-top: 50px;
    }
</style>
<body>
<div class="container-fluid" style="margin-top:50px;">
    <div class="row">
      <div class="col-md-3">
        <div class="list-group" id="list-tab" role="tablist">
          <a class="list-group-item list-group-item-action active" id="list-pres-list" data-toggle="list" href="#list-pres" role="tab" aria-controls="home">Pending Prescriptions</a>
          <a class="list-group-item list-group-item-action" id="list-comp-list" data-toggle="list" href="#list-comp" role="tab" aria-controls="profile">Completed Prescriptions</a>
          <a class="list-group-item list-group-item-action" id="list-inv-list" data-toggle="list" href="#list-inv" role="tab" aria-controls="profile">Inventory Management</a>
          <a class="list-group-item list-group-item-action" id="list-add-list" data-toggle="list" href="#list-add" role="tab" aria-controls="messages">Add Medication</a>
        </div>
      </div>
      <div class="col-md-9">
        <div class="tab-content" id="nav-tabContent">
          <!-- Pending Prescriptions -->
          <div class="tab-pane fade show active" id="list-pres" role="tabpanel" aria-labelledby="list-pres-list">
             <table class="table table-hover">
                <thead class="thead-dark">
                  <tr>
                    <th scope="col">Patient ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Disease</th>
                    <th scope="col">Prescription Details</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $query = "SELECT p.*, p.ID as app_id, p.pid as patient_id FROM prestb p 
                            WHERE p.dispensed = 0 
                            AND EXISTS (SELECT 1 FROM doctor_prescribed_meds d WHERE d.app_id = p.ID)";
                  $res = mysqli_query($con, $query);
                  while($row = mysqli_fetch_array($res)){
                      $app_id = $row['app_id'];
                      echo "<tr>";
                      echo "<td>{$row['patient_id']}</td>";
                      echo "<td>{$row['fname']} {$row['lname']}</td>";
                      echo "<td>{$row['disease']}</td>";
                      echo "<td>{$row['prescription']}</td>";
                      echo "<td><a href='pharmacist-checkout.php?app_id=$app_id' class='btn btn-primary btn-sm'>Allocate & Bill</a></td>";
                      echo "</tr>";
                  }
                  ?>
                </tbody>
             </table>
          </div>

          <!-- Completed Prescriptions -->
          <div class="tab-pane fade" id="list-comp" role="tabpanel" aria-labelledby="list-comp-list">
             <table class="table table-hover">
                <thead class="thead-dark">
                  <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Allocated Medication</th>
                    <th scope="col">Bill Amount</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $comp_query = "
                    SELECT p.*, p.ID as app_id, p.pid as patient_id, p.doctor, SUM(d.total_cost) as grand_total 
                    FROM prestb p 
                    INNER JOIN dispensed_medstb d ON p.ID = d.appointment_id 
                    WHERE p.dispensed = 1 AND d.status = 'Paid'
                    GROUP BY p.ID 
                    ORDER BY p.appdate DESC";
                  $comp_res = mysqli_query($con, $comp_query);
                  if($comp_res) {
                    while($row = mysqli_fetch_array($comp_res)){
                      echo "<tr>";
                      echo "<td>{$row['fname']} {$row['lname']}</td>";
                      
                      // Fetch individual medications
                      $app_id = $row['app_id'];
                      $med_str = '';
                      $med_q = mysqli_query($con, "SELECT m.name, d.quantity FROM dispensed_medstb d JOIN medicationstb m ON d.medication_id = m.id WHERE d.appointment_id = '$app_id'");
                      if($med_q) {
                          while($m = mysqli_fetch_array($med_q)){
                              $med_str .= "- {$m['name']} (x{$m['quantity']})<br>";
                          }
                      }
                      if($med_str == "") { $med_str = "None Recorded<br>"; }

                      echo "<td>" . $med_str . "</td>";
                      echo "<td><b>KES " . ($row['grand_total'] ?? 0) . "</b></td>";
                      echo "<td><span class='badge badge-success' style='font-size:14px; padding:8px;'><i class='fa fa-check-circle'></i> Paid & Dispensed</span></td>";
                      echo "<td><a href='pharmacist-panel.php?ID={$app_id}&generate_receipt=1' class='btn btn-info btn-sm' target='_blank'><i class='fa fa-file-pdf-o'></i> Receipt</a></td>";
                      echo "</tr>";
                    }
                  }
                  ?>
                </tbody>
             </table>
          </div>

          <!-- Inventory Management -->
          <div class="tab-pane fade" id="list-inv" role="tabpanel" aria-labelledby="list-inv-list">
             <table class="table table-hover">
                <thead class="thead-dark">
                  <tr>
                    <th scope="col">Medication Name</th>
                    <th scope="col">Price (KSH)</th>
                    <th scope="col">Stock Quantity</th>
                    <th scope="col">Update</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $inv_query = "SELECT * FROM medicationstb ORDER BY name ASC";
                  $inv_res = mysqli_query($con, $inv_query);
                  while($row = mysqli_fetch_array($inv_res)){
                      echo "<tr>";
                      echo "<form method='post' action='pharmacist-panel.php'>";
                      echo "<td>{$row['name']}<input type='hidden' name='med_id' value='{$row['id']}'></td>";
                      echo "<td><input type='number' class='form-control' name='med_price' value='{$row['price']}' required></td>";
                      echo "<td><input type='number' class='form-control' name='med_qty' value='{$row['stock_quantity']}' required></td>";
                      echo "<td>
                              <button type='submit' name='update_med' class='btn btn-success btn-sm'>Update</button>
                              <button type='submit' name='delete_med' class='btn btn-danger btn-sm'>Delete</button>
                            </td>";
                      echo "</form>";
                      echo "</tr>";
                  }
                  ?>
                </tbody>
             </table>
          </div>

          <!-- Add Medication -->
          <div class="tab-pane fade" id="list-add" role="tabpanel" aria-labelledby="list-add-list">
            <form class="form-group" method="post" action="pharmacist-panel.php">
                <div class="row">
                  <div class="col-md-6"><label>Medication Name:</label></div>
                  <div class="col-md-6"><input type="text" class="form-control" name="med_name" required></div><br><br>
                  <div class="col-md-6"><label>Price (KSH):</label></div>
                  <div class="col-md-6"><input type="number" class="form-control" name="med_price" required></div><br><br>
                  <div class="col-md-6"><label>Initial Stock Quantity:</label></div>
                  <div class="col-md-6"><input type="number" class="form-control" name="med_qty" required></div><br><br>
                  <div class="col-md-6"></div>
                  <div class="col-md-6">
                    <button type="submit" name="add_med" class="btn btn-primary">Add Medication</button>
                  </div>
                </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
