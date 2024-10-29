<?php
header('Content-Type: application/json');

// Function to authenticate and get the user ID
function authenticateAndGetUserId($apiKey, $username, $mysqli) {
    $stmt = $mysqli->prepare("SELECT id, status FROM users WHERE username = ? AND api_key = ?");
    $stmt->bind_param('ss', $username, $apiKey);
    $stmt->execute();
    $stmt->bind_result($userId, $status);
    $stmt->fetch();
    $stmt->close();

    if ($status === 'Active') {
        return $userId;
    }
    return false;
}

// Function to deduct API fee
function deductApiFee($userId, $mysqli) {
    $stmt = $mysqli->prepare("SELECT tin_fee FROM settings WHERE id = 1");
    $stmt->execute();
    $stmt->bind_result($apiFee);
    $stmt->fetch();
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($currentBalance);
    $stmt->fetch();
    $stmt->close();

    if ($currentBalance < $apiFee) {
        throw new Exception('Insufficient balance to process the request.');
    }

    $newBalance = $currentBalance - $apiFee;

    $stmt = $mysqli->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param('di', $newBalance, $userId);
    $stmt->execute();
    $stmt->close();

    return $newBalance;
}

// Function to make the cURL request to the new external API and retrieve response
function makeApiRequest($newTin, $nid) {
    $url = 'https://mysectiondata.onrender.com/get_certificate';

    $data = json_encode([
        "NEW_TIN" => $newTin,
        "NID" => $nid
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['response' => $response, 'http_code' => $httpCode];
}

// Function to log request details in the tin_logs table
function logTinRequest($userId, $nid, $newTin, $mysqli) {
    // Replace null or empty values with 'N/A'
    $nid = $nid ?: 'N/A';
    $newTin = $newTin ?: 'N/A';

    $stmt = $mysqli->prepare("INSERT INTO tin_logs (user_id, NID, NEW_TIN) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $nid, $newTin);
    $stmt->execute();
    $stmt->close();
}

// Database connection
$mysqli = new mysqli('203.26.151.171', 'protigga_mydatabase', 'protigga_mydatabase', 'protigga_mydatabase');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Collect input from the user (via POST request)
$apiKey = $_POST['api_key'] ?? null;
$username = $_POST['username'] ?? null;
$newTin = $_POST['NEW_TIN'] ?? null;
$nid = $_POST['NID'] ?? null;

if (!$apiKey || !$username) {
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

try {
    // Authenticate the user
    $userId = authenticateAndGetUserId($apiKey, $username, $mysqli);
    if (!$userId) {
        echo json_encode(['error' => 'Authentication failed.']);
        exit;
    }

    // Make the API request to the external service
    $apiResult = makeApiRequest($newTin, $nid);
    $apiResponse = $apiResult['response'];
    $httpCode = $apiResult['http_code'];

    // Handle HTTP status code 400
    if ($httpCode === 400 && strpos($apiResponse, 'TIN not found') !== false) {
        // Do not log or deduct fee
        echo $apiResponse;
        exit;
    }

    // Deduct the API fee
    deductApiFee($userId, $mysqli);

    // Log the request in the tin_logs table
    logTinRequest($userId, $nid, $newTin, $mysqli);

    // Return the external API response
    echo $apiResponse;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// Close the database connection
$mysqli->close();
?>
