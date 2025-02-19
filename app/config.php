<?php
$host = "localhost";    // Hvis du er på serveren, kan du bruke 'localhost', eller serverens IP hvis du kobler til eksternt
$dbname = "prosjekt_db"; // Navnet på databasen
$username = "admin";    // Brukernavn for MySQL
$password = "admin";    // Passord for MySQL

// Opprette tilkoblingen
$conn = new mysqli($host, $username, $password, $dbname);

// Sjekk tilkoblingen
if ($conn->connect_error) {
    die("Tilkobling mislyktes: " . $conn->connect_error);
}
?>
