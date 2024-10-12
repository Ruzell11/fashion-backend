<?php


require_once './vendor/autoload.php';


if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

function connectDatabase() {

      $host = getenv('DB_HOST') ?: 'localhost';
      $db = getenv('DB_NAME') ?: 'fashion_salon';
      $user = getenv('DB_USERNAME') ?: 'root';
      $pass = getenv('DB_PASSWORD') ?: '12345678';
      $charset = 'utf8'; // Change from utf8mb4 to utf8
    
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // 3. Create a new PDO instance using environment variables
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo; // Return the PDO object on success
    } catch (PDOException $e) {
        // Handle the connection error
        echo 'Connection failed: ' . $e->getMessage();
        return null;
    }
}
