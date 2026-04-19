<?php
session_start();
$con = mysqli_connect("localhost","root","","myhmsdb");

if(isset($_POST['phsub'])){
    $username = $_POST['username_ph'];
    $password = $_POST['password_ph'];

    $query = "select * from pharmacisttb where username='$username' and password='$password'";
    $result = mysqli_query($con, $query);

    if(mysqli_num_rows($result) == 1){
        $_SESSION['ph_username'] = $username;
        header("Location: pharmacist-panel.php");
    } else {
        echo "<script>
            alert('Invalid Username or Password');
            window.location.href = 'index.php';
        </script>";
    }
}

if(isset($_POST['logout'])){
    session_destroy();
    header("Location: index.php");
}
?>
