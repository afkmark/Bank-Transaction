<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_db";
$port = 3308;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit();
}

$username = $_POST['username'] ?? '';
$raw_password = $_POST['password'] ?? '';

if (!$username || !$raw_password) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);


$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already exists"]);
    exit();
}

$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hashed_password);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Registration successful!"]);
$conn->close();
