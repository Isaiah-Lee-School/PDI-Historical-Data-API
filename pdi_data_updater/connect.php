<?php

// Connecting MySQL to PHP

// Key values - change them to reflect your environment
$host = "localhost";
$user = "";
$password = "";
$dbname = "pdi_quotes";   // Change this line to the correct db
					
// Set data source name with UTF-8 encoding
$dsn = "mysql:host=".$host.";dbname=".$dbname.";charset=utf8";

try {
    // Create a PDO instance with error handling
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
	
	
} catch (PDOException $e) {
    // Handle connection error
    echo "Connection failed: " . $e->getMessage();
}
?>
