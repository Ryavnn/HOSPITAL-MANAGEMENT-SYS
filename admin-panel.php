<!DOCTYPE html>
<?php
include('func.php');
include('newfunc.php');
$con = mysqli_connect("localhost", "root", "", "myhmsdb");


$pid = $_SESSION['pid'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$fname = $_SESSION['fname'];
$gender = $_SESSION['gender'];
$lname = $_SESSION['lname'];
$contact = $_SESSION['contact'];



if (isset($_POST['app-submit'])) {
  $pid = $_SESSION['pid'];
  $username = $_SESSION['username'];
  $email = $_SESSION['email'];
  $fname = $_SESSION['fname'];
  $lname = $_SESSION['lname'];
  $gender = $_SESSION['gender'];
  $contact = $_SESSION['contact'];
  $doctor = $_POST['doctor'];
  $email = $_SESSION['email'];
  # $fees=$_POST['fees'];
  $docFees = $_POST['docFees'];

  $appdate = $_POST['appdate'];
  $apptime = $_POST['apptime'];
  $cur_date = date("Y-m-d");
  date_default_timezone_set('Asia/Kolkata');
  $cur_time = date("H:i:s");
  $apptime1 = strtotime($apptime);
  $appdate1 = strtotime($appdate);

  if (date("Y-m-d", $appdate1) >= $cur_date) {
    if ((date("Y-m-d", $appdate1) == $cur_date and date("H:i:s", $apptime1) > $cur_time) or date("Y-m-d", $appdate1) > $cur_date) {
      $check_query = mysqli_query($con, "select apptime from appointmenttb where doctor='$doctor' and appdate='$appdate' and apptime='$apptime'");

      if (mysqli_num_rows($check_query) == 0) {
        $query = mysqli_query($con, "insert into appointmenttb(pid,fname,lname,gender,email,contact,doctor,docFees,appdate,apptime,userStatus,doctorStatus) values('$pid','$fname','$lname','$gender','$email','$contact','$doctor','$docFees','$appdate','$apptime','1','1')");

        if ($query) {
          echo "<script>alert('Your appointment successfully booked');</script>";
        } else {
          echo "<script>alert('Unable to process your request. Please try again!');</script>";
        }
      } else {
        echo "<script>alert('We are sorry to inform that the doctor is not available in this time or date. Please choose different time or date!');</script>";
      }
    } else {
      echo "<script>alert('Select a time or date in the future!');</script>";
    }
  } else {
    echo "<script>alert('Select a time or date in the future!');</script>";
  }

}

if (isset($_GET['cancel'])) {
  $query = mysqli_query($con, "update appointmenttb set userStatus='0' where ID = '" . $_GET['ID'] . "'");
  if ($query) {
    echo "<script>alert('Your appointment successfully cancelled');</script>";
  }
}





function generate_bill()
{
  $con = mysqli_connect("localhost", "root", "", "myhmsdb");
  $app_id = $_GET['ID'];
  
  // Fetch Appointment & Prescription Info
  $query = mysqli_query($con, "SELECT a.ID, a.pid, a.fname, a.lname, a.doctor, a.appdate, a.apptime, a.docFees, p.disease, p.allergy, p.prescription 
                               FROM appointmenttb a 
                               LEFT JOIN prestb p ON a.ID = p.ID 
                               WHERE a.ID = '$app_id'");
  $row = mysqli_fetch_array($query);
  
  if(!$row) return "<h4>No records found.</h4>";
  
  $output = '
    <table cellpadding="5" width="100%">
      <tr><td width="55%"><b>Patient ID:</b> '.$row["pid"].'</td><td width="45%"><b>Date:</b> '.$row["appdate"].'</td></tr>
      <tr><td><b>Appointment ID:</b> '.$row["ID"].'</td><td><b>Time:</b> '.$row["apptime"].'</td></tr>
      <tr><td><b>Patient Name:</b> '.$row["fname"].' '.$row["lname"].'</td><td></td></tr>
      <tr><td><b>Doctor:</b> '.$row["doctor"].'</td><td></td></tr>
    </table>
    <hr>
    <h4>Clinical Summary</h4>
    <p><b>Diagnosis/Disease:</b> '.$row["disease"].'</p>
    <p><b>Known Allergies:</b> '.$row["allergy"].'</p>
    <p><b>Medical Notes:</b><br/>'.nl2br($row["prescription"]).'</p>
    <hr>
    <h4>Billing Details</h4>
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
      <tr style="background-color:#f2f2f2;">
        <th width="50%"><b>Description</b></th>
        <th width="20%" align="center"><b>Qty</b></th>
        <th width="30%" align="right"><b>Amount</b></th>
      </tr>
      <tr>
        <td>Consultation Fee (Dr. '.$row["doctor"].')</td>
        <td align="center">1</td>
        <td align="right">KES '.number_format($row["docFees"], 2).'</td>
      </tr>';
      
  $grand_total = $row["docFees"];
  
  // Fetch Medications from dispensed logs
  $med_q = mysqli_query($con, "SELECT m.name, d.quantity, d.total_cost FROM dispensed_medstb d JOIN medicationstb m ON d.medication_id = m.id WHERE d.appointment_id = '$app_id'");
  while($m = mysqli_fetch_array($med_q)){
      $output .= '<tr>
                    <td>Medication: '.$m["name"].'</td>
                    <td align="center">'.$m["quantity"].'</td>
                    <td align="right">KES '.number_format($m["total_cost"], 2).'</td>
                  </tr>';
      $grand_total += $m["total_cost"];
  }
  
  $output .= '
      <tr style="background-color:#e8f5e9;">
        <td colspan="2" align="right"><b>Grand Total Paid:</b></td>
        <td align="right"><b>KES '.number_format($grand_total, 2).'</b></td>
      </tr>
    </table>
    <br/><br/>
    <p align="center" style="font-size:10px; color:#555;"><i>This is a system-generated universal receipt for consultation and medication.<br/>Global Hospitals Management System</i></p>
  ';
  
  return $output;
}


if (isset($_GET["generate_bill"])) {
  require_once("TCPDF/tcpdf.php");
  $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
  $obj_pdf->SetCreator(PDF_CREATOR);
  $obj_pdf->SetTitle("Generate Bill");
  $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
  $obj_pdf->SetHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
  $obj_pdf->SetFooterFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
  $obj_pdf->SetDefaultMonospacedFont('helvetica');
  $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
  $obj_pdf->SetMargins(PDF_MARGIN_LEFT, '5', PDF_MARGIN_RIGHT);
  $obj_pdf->SetPrintHeader(false);
  $obj_pdf->SetPrintFooter(false);
  $obj_pdf->SetAutoPageBreak(TRUE, 10);
  $obj_pdf->SetFont('helvetica', '', 12);
  $obj_pdf->AddPage();

  $content = '';

  $content .= '
      <br/>
      <h2 align ="center"> Global Hospitals</h2></br>
      <h3 align ="center"> Universal Payment Receipt</h3>
      

  ';

  $content .= generate_bill();
  $obj_pdf->writeHTML($content);
  ob_end_clean();
  $obj_pdf->Output("bill.pdf", 'I');

}

function get_specs()
{
  $con = mysqli_connect("localhost", "root", "", "myhmsdb");
  $query = mysqli_query($con, "select username,spec from doctb");
  $docarray = array();
  while ($row = mysqli_fetch_assoc($query)) {
    $docarray[] = $row;
  }
  return json_encode($docarray);
}

?>
<html lang="en">

<head>


  <!-- Required meta tags -->
  <meta charset="utf-8">
  <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" type="text/css" href="font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="style.css">
  <!-- Bootstrap CSS -->

  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">








  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css"
    integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

  <link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans&display=swap" rel="stylesheet">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <a class="navbar-brand" href="#"><i class="fa fa-user-plus" aria-hidden="true"></i> Global Hospital </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <style>
      .bg-primary {
        background: -webkit-linear-gradient(left, #3931af, #00c6ff);
      }

      .list-group-item.active {
        z-index: 2;
        color: #fff;
        background-color: #342ac1;
        border-color: #007bff;
      }

      .text-primary {
        color: #342ac1 !important;
      }

      .btn-primary {
        background-color: #3c50c1;
        border-color: #3c50c1;
      }
    </style>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item">
          <a class="nav-link" href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i>Logout</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#"></a>
        </li>
      </ul>
    </div>
  </nav>
</head>
<style type="text/css">
  button:hover {
    cursor: pointer;
  }

  #inputbtn:hover {
    cursor: pointer;
  }
</style>

<body style="padding-top:50px;">

  <div class="container-fluid" style="margin-top:50px;">
    <h3 style="margin-left: 40%;  padding-bottom: 20px; font-family: 'IBM Plex Sans', sans-serif;"> Welcome
      &nbsp<?php echo $username ?>
    </h3>
    <div class="row">
      <div class="col-md-4" style="max-width:25%; margin-top: 3%">
        <div class="list-group" id="list-tab" role="tablist">
          <a class="list-group-item list-group-item-action active" id="list-dash-list" data-toggle="list"
            href="#list-dash" role="tab" aria-controls="home">Dashboard</a>
          <a class="list-group-item list-group-item-action" id="list-home-list" data-toggle="list" href="#list-home"
            role="tab" aria-controls="home">Book Appointment</a>
          <a class="list-group-item list-group-item-action" href="#app-hist" id="list-pat-list" role="tab"
            data-toggle="list" aria-controls="home">Appointment History</a>
          <a class="list-group-item list-group-item-action" href="#list-pres" id="list-pres-list" role="tab"
            data-toggle="list" aria-controls="home">Prescriptions</a>

        </div><br>
      </div>
      <div class="col-md-8" style="margin-top: 3%;">
        <div class="tab-content" id="nav-tabContent" style="width: 950px;">


          <div class="tab-pane fade  show active" id="list-dash" role="tabpanel" aria-labelledby="list-dash-list">
            <div class="container-fluid container-fullw bg-white">
              <div class="row">
                <div class="col-sm-4" style="left: 5%">
                  <div class="panel panel-white no-radius text-center">
                    <div class="panel-body">
                      <span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i
                          class="fa fa-terminal fa-stack-1x fa-inverse"></i> </span>
                      <h4 class="StepTitle" style="margin-top: 5%;"> Book My Appointment</h4>
                      <script>
                        function clickDiv(id) {
                          document.querySelector(id).click();
                        }
                      </script>
                      <p class="links cl-effect-1">
                        <a href="#list-home" onclick="clickDiv('#list-home-list')">
                          Book Appointment
                        </a>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-sm-4" style="left: 10%">
                  <div class="panel panel-white no-radius text-center">
                    <div class="panel-body">
                      <span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i
                          class="fa fa-paperclip fa-stack-1x fa-inverse"></i> </span>
                      <h4 class="StepTitle" style="margin-top: 5%;">My Appointments</h2>

                        <p class="cl-effect-1">
                          <a href="#app-hist" onclick="clickDiv('#list-pat-list')">
                            View Appointment History
                          </a>
                        </p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-4" style="left: 20%;margin-top:5%">
                <div class="panel panel-white no-radius text-center">
                  <div class="panel-body">
                    <span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i
                        class="fa fa-list-ul fa-stack-1x fa-inverse"></i> </span>
                    <h4 class="StepTitle" style="margin-top: 5%;">Prescriptions</h2>

                      <p class="cl-effect-1">
                        <a href="#list-pres" onclick="clickDiv('#list-pres-list')">
                          View Prescription List
                        </a>
                      </p>
                  </div>
                </div>
              </div>


            </div>
          </div>





          <div class="tab-pane fade" id="list-home" role="tabpanel" aria-labelledby="list-home-list">
            <div class="container-fluid">
              <div class="card">
                <div class="card-body">
                  <center>
                    <h4>Create an appointment</h4>
                  </center><br>
                  <form class="form-group" method="post" action="admin-panel.php">
                    <div class="row">

                      <!-- <?php

                      $con = mysqli_connect("localhost", "root", "", "myhmsdb");
                      $query = mysqli_query($con, "select username,spec from doctb");
                      $docarray = array();
                      while ($row = mysqli_fetch_assoc($query)) {
                        $docarray[] = $row;
                      }
                      echo json_encode($docarray);

                      ?> -->


                      <div class="col-md-4">
                        <label for="spec">Specialization:</label>
                      </div>
                      <div class="col-md-8">
                        <select name="spec" class="form-control" id="spec">
                          <option value="" disabled selected>Select Specialization</option>
                          <?php
                          display_specs();
                          ?>
                        </select>
                      </div>

                      <br><br>

                      <script>
                        document.getElementById('spec').onchange = function foo() {
                          let spec = this.value;
                          console.log(spec)
                          let docs = [...document.getElementById('doctor').options];

                          docs.forEach((el, ind, arr) => {
                            arr[ind].setAttribute("style", "");
                            if (el.getAttribute("data-spec") != spec) {
                              arr[ind].setAttribute("style", "display: none");
                            }
                          });
                        };

                      </script>

                      <div class="col-md-4"><label for="doctor">Doctors:</label></div>
                      <div class="col-md-8">
                        <select name="doctor" class="form-control" id="doctor" required="required">
                          <option value="" disabled selected>Select Doctor</option>

                          <?php display_docs(); ?>
                        </select>
                      </div><br /><br />


                      <script>
                        document.getElementById('doctor').onchange = function updateFees(e) {
                          var selectedOption = this.options[this.selectedIndex];
                          var fee = selectedOption.getAttribute('data-value');
                          document.getElementById('docFees').value = fee ? 'KES ' + fee : '';
                          document.getElementById('docFees-raw').value = fee || '';
                        };
                      </script>





                      <!-- <div class="col-md-4"><label for="doctor">Doctors:</label></div>
                                <div class="col-md-8">
                                    <select name="doctor" class="form-control" id="doctor1" required="required">
                                      <option value="" disabled selected>Select Doctor</option>
                                      
                                    </select>
                                </div>
                                <br><br> -->

                      <!-- <script>
                                  document.getElementById("spec").onchange = function updateSpecs(event) {
                                      var selected = document.querySelector(`[data-value=${this.value}]`).getAttribute("value");
                                      console.log(selected);

                                      var options = document.getElementById("doctor1").querySelectorAll("option");

                                      for (i = 0; i < options.length; i++) {
                                        var currentOption = options[i];
                                        var category = options[i].getAttribute("data-spec");

                                        if (category == selected) {
                                          currentOption.style.display = "block";
                                        } else {
                                          currentOption.style.display = "none";
                                        }
                                      }
                                    }
                                </script> -->


                      <!-- <script>
                    let data = 
                
              document.getElementById('spec').onchange = function updateSpecs(e) {
                let values = data.filter(obj => obj.spec == this.value).map(o => o.username);   
                document.getElementById('doctor1').value = document.querySelector(`[value=${values}]`).getAttribute('data-value');
              };
            </script> -->



                      <div class="col-md-4"><label for="consultancyfees">
                          Consultancy Fees
                        </label></div>
                      <div class="col-md-8">
                        <input class="form-control" type="text" id="docFees" readonly="readonly"
                          placeholder="Select a doctor to see the fee" style="background:#e9ecef;" />
                        <input type="hidden" name="docFees" id="docFees-raw" />
                      </div><br><br>

                      <div class="col-md-4"><label>Appointment Date</label></div>
                      <div class="col-md-8"><input type="date" class="form-control datepicker" name="appdate"></div>
                      <br><br>

                      <div class="col-md-4"><label>Appointment Time</label></div>
                      <div class="col-md-8">
                        <!-- <input type="time" class="form-control" name="apptime"> -->
                        <select name="apptime" class="form-control" id="apptime" required="required">
                          <option value="" disabled selected>Select Time</option>
                          <option value="08:00:00">8:00 AM</option>
                          <option value="10:00:00">10:00 AM</option>
                          <option value="12:00:00">12:00 PM</option>
                          <option value="14:00:00">2:00 PM</option>
                          <option value="16:00:00">4:00 PM</option>
                        </select>

                      </div><br><br>

                      <div class="col-md-4">
                        <input type="submit" name="app-submit" value="Create new entry" class="btn btn-primary"
                          id="inputbtn">
                      </div>
                      <div class="col-md-8"></div>
                    </div>
                  </form>
                </div>
              </div>
            </div><br>
          </div>

          <div class="tab-pane fade" id="app-hist" role="tabpanel" aria-labelledby="list-pat-list">

            <table class="table table-hover">
              <thead>
                <tr>

                  <th scope="col">Doctor Name</th>
                  <th scope="col">Consultancy Fees</th>
                  <th scope="col">Appointment Date</th>
                  <th scope="col">Appointment Time</th>
                  <th scope="col">Current Status</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php

                $con = mysqli_connect("localhost", "root", "", "myhmsdb");
                global $con;

                $query = "select ID,doctor,docFees,appdate,apptime,userStatus,doctorStatus,payment from appointmenttb where fname ='$fname' and lname='$lname';";
                $result = mysqli_query($con, $query);
                while ($row = mysqli_fetch_array($result)) {

                  #$fname = $row['fname'];
                  #$lname = $row['lname'];
                  #$email = $row['email'];
                  #$contact = $row['contact'];
                  ?>
                  <tr>
                    <td><?php echo $row['doctor']; ?></td>
                    <td><?php echo $row['docFees']; ?></td>
                    <td><?php echo $row['appdate']; ?></td>
                    <td><?php echo $row['apptime']; ?></td>

                    <td>
                      <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) {
                        echo "Active";
                      }
                      if (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                        echo "Cancelled by You";
                      }

                      if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                        echo "Cancelled by Doctor";
                      }

                      if (($row['userStatus'] == 2) && ($row['doctorStatus'] == 2)) {
                        echo "<span style='color:#00a651;font-weight:600;'>&#10003; Completed</span>";
                      }
                      ?>
                    </td>

                    <td>
                      <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                        <a href="admin-panel.php?ID=<?php echo $row['ID'] ?>&cancel=update"
                          onClick="return confirm('Are you sure you want to cancel this appointment ?')"
                          title="Cancel Appointment" tooltip-placement="top" tooltip="Remove" style="margin-right:5px;">
                          <button class="btn btn-danger btn-sm">Cancel</button>
                        </a>
                        <?php if ($row['payment'] == 'Paid') { ?>
                          <button class="btn btn-secondary btn-sm" disabled style="cursor:default;">
                            <i class="fa fa-check"></i> PAID (Awaiting Completion)
                          </button>
                        <?php } else { ?>
                          <button id="paybtn-<?php echo $row['ID'] ?>" class="btn btn-success btn-sm"
                            onclick="openMpesaModal('<?php echo $row['ID'] ?>', '<?php echo $row['docFees'] ?>')">
                            <i class="fa fa-mobile"></i> Pay Bill
                          </button>
                        <?php } ?>

                      <?php } elseif (($row['userStatus'] == 2) && ($row['doctorStatus'] == 2)) { ?>
                        <span style="color:#00a651;font-weight:600;margin-right:10px;">&#10003; Completed</span>
                        <button class="btn btn-secondary btn-sm" disabled style="cursor:default;">
                          <i class="fa fa-check"></i> PAID
                        </button>
                        <a href="admin-panel.php?ID=<?php echo $row['ID'] ?>&generate_bill=1" class="btn btn-info btn-sm" style="margin-left:5px;">
                          <i class="fa fa-download"></i> Receipt
                        </a>
                      <?php } else {
                        echo "Cancelled";
                      } ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
            <br>
          </div>



          <div class="tab-pane fade" id="list-pres" role="tabpanel" aria-labelledby="list-pres-list">

            <table class="table table-hover">
              <thead>
                <tr>

                  <th scope="col">Doctor Name</th>
                  <th scope="col">Appointment ID</th>
                  <th scope="col">Appointment Date</th>
                  <th scope="col">Appointment Time</th>
                  <th scope="col">Diseases</th>
                  <th scope="col">Allergies</th>
                  <th scope="col">Prescriptions</th>
                </tr>
              </thead>
              <tbody>
                <?php

                $con = mysqli_connect("localhost", "root", "", "myhmsdb");
                global $con;

                $query = "select p.doctor, p.ID, p.appdate, p.apptime, p.disease, p.allergy, p.prescription, COALESCE(a.docFees, 0) as docFees, COALESCE(a.payment,'') as payment, COALESCE(a.userStatus,1) as userStatus, COALESCE(a.doctorStatus,1) as doctorStatus from prestb p left join appointmenttb a on p.ID = a.ID where p.fname='$fname' and p.lname='$lname';";

                $result = mysqli_query($con, $query);
                if (!$result) {
                  echo mysqli_error($con);
                }


                while ($row = mysqli_fetch_array($result)) {
                  ?>
                  <tr>
                    <td><?php echo $row['doctor']; ?></td>
                    <td><?php echo $row['ID']; ?></td>
                    <td><?php echo $row['appdate']; ?></td>
                    <td><?php echo $row['apptime']; ?></td>
                    <td><?php echo $row['disease']; ?></td>
                    <td><?php echo $row['allergy']; ?></td>
                    <td>
                      <?php
                      $app_id = $row['ID'];
                      $med_str = '';
                      $med_q = mysqli_query($con, "SELECT m.name, d.quantity FROM doctor_prescribed_meds d JOIN medicationstb m ON d.med_id = m.id WHERE d.app_id = '$app_id'");
                      if ($med_q) {
                        while ($m = mysqli_fetch_array($med_q)) {
                          $med_str .= "- {$m['name']} (x{$m['quantity']})<br>";
                        }
                      }
                      if ($med_str != '') {
                        echo "<b>From Stock:</b><br>" . $med_str;
                      }
                      if (!empty($row['prescription'])) {
                        echo "<b>Notes / Custom:</b><br>" . nl2br($row['prescription']);
                      }
                      ?>
                    </td>
                    </td>


                  </tr>
                <?php }
                ?>
              </tbody>
            </table>
            <br>
          </div>




          <div class="tab-pane fade" id="list-messages" role="tabpanel" aria-labelledby="list-messages-list">...</div>
          <div class="tab-pane fade" id="list-settings" role="tabpanel" aria-labelledby="list-settings-list">
            <form class="form-group" method="post" action="func.php">
              <label>Doctors name: </label>
              <input type="text" name="name" placeholder="Enter doctors name" class="form-control">
              <br>
              <input type="submit" name="doc_sub" value="Add Doctor" class="btn btn-primary">
            </form>
          </div>
          <div class="tab-pane fade" id="list-attend" role="tabpanel" aria-labelledby="list-attend-list">...</div>
        </div>
      </div>
    </div>
  </div>
  <!-- Optional JavaScript -->
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
    integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
    crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"
    integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4"
    crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js"
    integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1"
    crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.10.1/sweetalert2.all.min.js">
  </script>




  <!-- ===== M-PESA STK PUSH MODAL ===== -->
  <div class="modal fade" id="mpesaModal" tabindex="-1" role="dialog" aria-labelledby="mpesaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:400px;">
      <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

        <!-- Header -->
        <div class="modal-header" style="background:linear-gradient(135deg,#00a651,#007a3d);color:white;border:none;">
          <h5 class="modal-title" id="mpesaModalLabel">
            <span style="font-size:22px;font-weight:900;letter-spacing:1px;">M</span><span
              style="color:#fff;font-weight:400;">-PESA</span>
            &nbsp; Payment
          </h5>
          <button type="button" class="close" data-dismiss="modal"
            style="color:white;opacity:1;"><span>&times;</span></button>
        </div>

        <div class="modal-body text-center" style="padding:30px 25px;">

          <!-- STEP 1: Enter phone -->
          <div id="mpesa-step1">
            <div
              style="background:#00a651;border-radius:50%;width:72px;height:72px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;">
              <i class="fa fa-mobile fa-2x" style="color:white;"></i>
            </div>
            <h5 style="font-weight:700;margin-bottom:4px;">Pay via M-Pesa</h5>
            <p class="text-muted" style="font-size:13px;">Enter your M-Pesa registered phone number</p>

            <div class="form-group" style="margin-bottom:10px;">
              <input type="tel" class="form-control text-center" id="mpesaPhone" placeholder="e.g. 0712345678"
                maxlength="10" style="border-radius:8px;font-size:16px;letter-spacing:2px;"
                value="<?php echo isset($_SESSION['contact']) ? htmlspecialchars($_SESSION['contact']) : ''; ?>" />
            </div>

            <p style="font-size:15px;margin-bottom:18px;">
              Amount: <strong style="color:#00a651;">KES <span id="mpesa-amount">0</span></strong>
            </p>

            <button id="stk-send-btn" class="btn btn-block" onclick="simulateSTKPush()"
              style="background:#00a651;color:white;border-radius:8px;font-weight:600;padding:10px;margin-bottom:8px;">
              <i class="fa fa-paper-plane"></i>&nbsp; Send STK Push
            </button>

            <div id="mpesa-stk-error"
              style="display:none;color:#c0392b;font-size:13px;margin-bottom:8px;padding:6px 10px;background:#fdecea;border-radius:6px;">
            </div>

            <hr style="margin:10px 0;">
            <p style="font-size:12px;color:#999;margin-bottom:8px;">For presentation purposes:</p>
            <button class="btn btn-warning btn-block" onclick="simulateSuccess()"
              style="border-radius:8px;font-weight:600;padding:10px;">
              <i class="fa fa-check-circle"></i>&nbsp; Successful Simulation
            </button>
          </div>

          <!-- STEP 2: Waiting for PIN -->
          <div id="mpesa-step2" style="display:none;">
            <div style="margin-bottom:20px;">
              <div class="mpesa-spinner"></div>
            </div>
            <h5 style="font-weight:700;">STK Push Sent!</h5>
            <p>Check your phone <strong id="mpesa-phone-display" style="color:#00a651;"></strong><br>for the M-Pesa
              prompt.</p>
            <p class="text-muted" style="font-size:13px;">Enter your M-Pesa PIN on your phone to complete the payment.
            </p>
            <hr>
            <p style="font-size:12px;color:#999;margin-bottom:8px;">For presentation purposes:</p>
            <button class="btn btn-warning btn-block" onclick="simulateSuccess()"
              style="border-radius:8px;font-weight:600;padding:10px;">
              <i class="fa fa-check-circle"></i>&nbsp; Successful Simulation
            </button>
          </div>

          <!-- STEP 3: Success -->
          <div id="mpesa-step3" style="display:none;">
            <div
              style="background:#e8f5e9;border-radius:50%;width:80px;height:80px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;">
              <i class="fa fa-check-circle fa-3x" style="color:#00a651;"></i>
            </div>
            <h5 style="font-weight:700;color:#00a651;">Payment Successful!</h5>
            <p>Your M-Pesa transaction has been confirmed.<br>Generating your bill...</p>
            <div class="progress" style="height:6px;border-radius:3px;">
              <div class="progress-bar bg-success" id="bill-progress" style="width:0%;transition:width 2s ease;"></div>
            </div>
          </div>

        </div><!-- /modal-body -->
      </div>
    </div>
  </div>

  <style>
    .mpesa-spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #e0e0e0;
      border-top-color: #00a651;
      border-radius: 50%;
      animation: spin 0.9s linear infinite;
      margin: 0 auto;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>

  <script>
    var mpesaBillId = '';
    var mpesaAmount = 0;

    function openMpesaModal(id, amount) {
      mpesaBillId = id;
      mpesaAmount = amount;
      document.getElementById('mpesa-amount').textContent = amount;
      document.getElementById('mpesa-step1').style.display = 'block';
      document.getElementById('mpesa-step2').style.display = 'none';
      document.getElementById('mpesa-step3').style.display = 'none';
      document.getElementById('mpesa-stk-error').style.display = 'none';
      $('#mpesaModal').modal('show');
    }

    /* ── Real STK Push via AJAX ─────────────────────────────────────────────── */
    function simulateSTKPush() {
      var phone = document.getElementById('mpesaPhone').value.trim();
      if (!phone || phone.length < 9) {
        alert('Please enter a valid M-Pesa phone number (e.g. 0712345678).');
        return;
      }

      /* Show spinner on the button */
      var btn = document.getElementById('stk-send-btn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';

      var formData = new FormData();
      formData.append('phone', phone);
      formData.append('amount', mpesaAmount);
      formData.append('bill_id', mpesaBillId);

      fetch('mpesa_stk.php', { method: 'POST', body: formData })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-paper-plane"></i>&nbsp; Send STK Push';

          if (data.success) {
            /* Move to waiting-for-PIN screen */
            document.getElementById('mpesa-phone-display').textContent = phone;
            document.getElementById('mpesa-step1').style.display = 'none';
            document.getElementById('mpesa-step2').style.display = 'block';
          } else {
            /* Show inline error */
            var errEl = document.getElementById('mpesa-stk-error');
            errEl.textContent = data.message || 'STK Push failed. Use Simulation button below.';
            errEl.style.display = 'block';
          }
        })
        .catch(function (err) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-paper-plane"></i>&nbsp; Send STK Push';
          var errEl = document.getElementById('mpesa-stk-error');
          errEl.textContent = 'Network error. Use Simulation button below.';
          errEl.style.display = 'block';
        });
    }

    /* ── Simulation bypass (always works — for demo / offline use) ────────────── */
    function simulateSuccess() {
      document.getElementById('mpesa-step1').style.display = 'none';
      document.getElementById('mpesa-step2').style.display = 'none';
      document.getElementById('mpesa-step3').style.display = 'block';
      setTimeout(function () {
        document.getElementById('bill-progress').style.width = '100%';
      }, 100);

      /* POST to mpesa_confirm.php to mark DB as Paid + Completed */
      var formData = new FormData();
      formData.append('bill_id', mpesaBillId);

      fetch('mpesa_confirm.php', { method: 'POST', body: formData })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          /* Close modal and update button after short delay */
          setTimeout(function () {
            $('#mpesaModal').modal('hide');
            var btn = document.getElementById('paybtn-' + mpesaBillId);
            if (btn) {
              btn.outerHTML = '<button class="btn btn-secondary btn-sm" disabled style="cursor:default;">'
                + '<i class="fa fa-check"></i> PAID</button>';
            }
          }, 2200);
        })
        .catch(function () {
          /* Even on network error, still update UI */
          setTimeout(function () {
            $('#mpesaModal').modal('hide');
            var btn = document.getElementById('paybtn-' + mpesaBillId);
            if (btn) {
              btn.outerHTML = '<button class="btn btn-secondary btn-sm" disabled style="cursor:default;">'
                + '<i class="fa fa-check"></i> PAID</button>';
            }
          }, 2200);
        });
    }
  </script>

</body>

</html>