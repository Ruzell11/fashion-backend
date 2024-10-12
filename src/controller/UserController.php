<?php
session_start();

class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function signUp($data) {
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            return json_encode(['message' => 'Username, email, and password are required.']);
        }
    
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT); 
    
        // Prepare SQL statement to prevent SQL injection
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
    
        try {
            // Execute the statement
            $stmt->execute(['username' => $username, 'email' => $email, 'password' => $password]);
            http_response_code(201);
            return json_encode(['message' => 'User signed up successfully.']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // Unique constraint violation
                http_response_code(409);
                return json_encode(['message' => 'Username or email already taken.']);
            } else {
                http_response_code(500);
                return json_encode(['message' => 'Internal server error.']);
            }
        }
    }

    public function login($data) {
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            return json_encode(['message' => 'Username and password are required.']);
        }
    
        $username = $data['email'];
        $password = $data['password'];
    
        // Prepare SQL statement to find the user by username
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        
        try {
            $stmt->execute(['email' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user && password_verify($password, $user['password'])) {
       
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
    
                http_response_code(200);
                return json_encode(['message' => 'Login successful', 'user_id' => $_SESSION['user_id']]);
            } else {
                // Authentication failed
                http_response_code(401);
                return json_encode(['message' => 'Invalid email or password']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['message' => 'Internal server error.']);
        }
    }
    
}
