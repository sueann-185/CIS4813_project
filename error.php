<?php
$message = 'Unknown error occurred';

// Get all possible types of errors
if(isset($_GET['login'])) {
    $message = $_GET['login'];  // Directly obtain URL parameter values
} else if(isset($_GET['logout'])) {
    $message = 'You have successfully logged out';
} else if(isset($_GET['timeout'])) {
    $message = 'The session has timed out, please log in again';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>Error page</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; }
        .error-container { max-width: 600px; margin: 100px auto; padding: 30px; text-align: center; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error</h1>
        <p><?= $message ?></p>
        <a href="login.php" class="back-btn">Return to login page</a>
    </div>
</body>
</html>