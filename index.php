<?php
require __DIR__ . "/vendor/autoload.php";

session_start();

$client = new Google\Client;

$client->setClientId("460473723264-qsr6d8fc0p526u7kt69gu4pffh5td7j3.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/MikeLoginActivity/redirect.php");

$client->addScope("email");
$client->addScope("profile");

$url = $client->createAuthUrl();

if (isset($_SESSION["user_id"])) {
    
    $mysqli = require __DIR__ . "/database.php";
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        die("SQL error: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
    
    <h1>Home</h1>
    
    <?php if (isset($user)): ?>
        
        <p>Hello <?= htmlspecialchars($user["fullname"] ?? 'Guest') ?></p>
        
        <p><a href="logout.php">Log out</a></p>
        
    <?php else: ?>
        
        <p><a href="login.php">Log in</a> or <a href="signup.html">sign up</a></p>
        <p><a href="<?= $url ?>">Sign In with Google</a></p>
        
    <?php endif; ?>
    
</body>
</html>