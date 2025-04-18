<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_db";
$port = 3308;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection Failed"]));
}

$action = $_POST['action'] ?? '';

// LOGIN
if ($action === "login") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            echo json_encode(["success" => true, "message" => "Login successful"]);
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
    exit();
}

//  LOGOUT
if ($action === "logout") {
    session_unset();
    session_destroy();
    echo json_encode(["success" => true, "message" => "Logged out successfully"]);
    exit();
}

//  AUTH CHECK for all other actions
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in."]);
    exit();
}

// DEPOSIT / WITHDRAW
if ($action === "deposit" || $action === "withdraw") {
    $account_number = $_POST['account_number'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid amount"]);
        exit();
    }

    $result = $conn->query("SELECT balance FROM transactions WHERE account_number='$account_number' ORDER BY id DESC LIMIT 1");
    $row = $result->fetch_assoc();
    $current_balance = $row ? floatval($row['balance']) : 0;

    if ($action === "withdraw" && $current_balance < $amount) {
        echo json_encode(["status" => "error", "message" => "Insufficient balance"]);
        exit();
    }

    $new_balance = ($action === "deposit") ? ($current_balance + $amount) : ($current_balance - $amount);

    $stmt = $conn->prepare("INSERT INTO transactions (account_number, transaction_type, amount, balance) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", $account_number, $action, $amount, $new_balance);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => ucfirst($action) . " successful", "new_balance" => $new_balance]);
}
//  BALANCE CHECK
elseif ($action === "balance") {
    $account_number = $_POST['account_number'] ?? '';

    $result = $conn->query("SELECT balance FROM transactions WHERE account_number='$account_number' ORDER BY id DESC LIMIT 1");
    $row = $result->fetch_assoc();
    $balance = $row ? floatval($row['balance']) : 0;

    echo json_encode(["status" => "success", "balance" => $balance]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}

$conn->close();
