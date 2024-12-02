<?php

require __DIR__ . "/vendor/autoload.php";

session_start();

$client = new Google\Client;

$client->setClientId("460473723264-qsr6d8fc0p526u7kt69gu4pffh5td7j3.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/MikeLoginActivity/redirect.php");

if (!isset($_GET["code"])) {
    exit("Login failed");
}

$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);

$client->setAccessToken($token["access_token"]);

$oauth = new Google\Service\Oauth2($client);

$userinfo = $oauth->userinfo->get();

// Safely get the name or use email as fallback
$name = isset($userinfo->name) && !empty($userinfo->name) ? $userinfo->name : $userinfo->email;

// Generate activation token
$activation_token = bin2hex(random_bytes(16));
$activation_token_hash = hash("sha256", $activation_token);

// Database connection
$mysqli = require __DIR__ . "/database.php";

// Check if the user already exists
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("SQL error: " . $mysqli->error);
}

$stmt->bind_param("s", $userinfo->email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // User exists, update user data
    $sql = "UPDATE users SET fullname = ?, google_id = ?, account_activation_hash = NULL, role = 'client' WHERE email = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("sss", $name, $userinfo->id, $userinfo->email);
    $stmt->execute();
} else {
    // User does not exist, insert new user
    $sql = "INSERT INTO users (email, fullname, google_id, account_activation_hash, role) VALUES (?, ?, ?, ?, 'client')";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("ssss", $userinfo->email, $name, $userinfo->id, $activation_token_hash);
    $stmt->execute();
}

// Set session variables
$_SESSION['user_id'] = $user ? $user['id'] : $mysqli->insert_id;
$_SESSION['email'] = $userinfo->email;
$_SESSION['name'] = $name;
$_SESSION['role'] = 'client';

// Redirect to a welcome page or dashboard
header("Location: index.php");
exit;