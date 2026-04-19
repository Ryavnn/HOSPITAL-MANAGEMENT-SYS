<?php
$con = mysqli_connect("localhost","root","","myhmsdb");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$queries = [
    "CREATE TABLE IF NOT EXISTS `pharmacisttb` (
        `username` varchar(50) NOT NULL,
        `password` varchar(50) NOT NULL,
        `email` varchar(50) NOT NULL,
        PRIMARY KEY (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;",

    "INSERT IGNORE INTO `pharmacisttb` (`username`, `password`, `email`) VALUES
    ('pharmacist1', 'pharm123', 'pharmacist@globalhospitals.com');",

    "CREATE TABLE IF NOT EXISTS `medicationstb` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `price` int(11) NOT NULL,
        `stock_quantity` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;",

    "CREATE TABLE IF NOT EXISTS `dispensed_medstb` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `appointment_id` int(11) NOT NULL,
        `medication_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `total_cost` int(11) NOT NULL,
        `status` varchar(20) NOT NULL DEFAULT 'Pending',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;",
];

foreach ($queries as $q) {
    if (mysqli_query($con, $q)) {
        echo "Successfully executed: " . substr($q, 0, 50) . "...\n";
    } else {
        echo "Error: " . mysqli_error($con) . "\n";
    }
}

// Add dispensed flag to prestb if it doesn't exist
$checkCol = mysqli_query($con, "SHOW COLUMNS FROM `prestb` LIKE 'dispensed'");
if (mysqli_num_rows($checkCol) == 0) {
    if (mysqli_query($con, "ALTER TABLE `prestb` ADD `dispensed` BOOLEAN NOT NULL DEFAULT FALSE")) {
        echo "Added 'dispensed' column to prestb\n";
    } else {
        echo "Error adding column: " . mysqli_error($con) . "\n";
    }
} else {
    echo "'dispensed' column already exists in prestb\n";
}

mysqli_close($con);
echo "DB Update Complete.\n";
?>
