<?php
$host = "localhost";
$user = "root";
$password = ""; // Tu contraseña si tienes
$dbname = "colegiodsh";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>