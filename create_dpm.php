<?php
$c=mysqli_connect('localhost','root','','myhmsdb');
mysqli_query($c, "CREATE TABLE IF NOT EXISTS `doctor_prescribed_meds` (`app_id` int(11), `med_id` int(11), `quantity` int(11))");
?>
