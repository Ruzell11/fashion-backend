<?php


class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function signUp($data) {
        // Check if necessary fields are provided
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            return json_encode(['message' => 'Username, email, and password are required.']);
        }
    
        // Extract values from the input data
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
    

       
    
        // Generate a random user ID (e.g., between 100000 and 999999)
        $userId = random_int(100000, 999999);
    
        // Prepare SQL statement to prevent SQL injection
        $stmt = $this->pdo->prepare('INSERT INTO users (id, username, email, password) VALUES (:id, :username, :email, :password)');
    
        try {
            // Execute the statement with the random user ID and is_admin flag
            $stmt->execute([
                'id' => $userId, 
                'username' => $username, 
                'email' => $email, 
                'password' => $password, 
           
            ]);
            
            // Respond with success message
            http_response_code(201);
            return json_encode(['message' => 'User signed up successfully.', 'user_id' => $userId]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // Unique constraint violation
                http_response_code(409);
                return json_encode(['message' => 'Username, email, or ID already taken.']);
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

    public function isAuthenticated($user_id, $pdo) {

        var_dump($user_id);
        // Prepare SQL statement to find the user by user_id
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id LIMIT 1');
    
        try {
            // Execute the statement with the provided user_id
            $stmt->execute(['user_id' => (int) $user_id]);
            
            // Fetch the result
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            var_dump(!empty($user));
            
            // Check if a user was found
            if (!empty($user)) {
                return true; // User exists
            } else {
                return false; // User does not exist
            }
        } catch (PDOException $e) {
            // Handle any potential errors (e.g., log error)
            error_log("Error in isAuthenticated: " . $e->getMessage());
            return false; // Return false if an error occurs
        }
    }

    public function getUserData($user_id)
{
    try {
        // Prepare the SQL query to fetch user data by ID
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :user_id');
        
        // Bind the parameter to prevent SQL injection
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        // Execute the query
        $stmt->execute();
        
        // Fetch the user data
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user data exists
        if ($userData) {
            return $userData; // Return the user data as an associative array
        } else {
            return null; // No user found
        }
    } catch (PDOException $e) {
        // Handle any errors
        error_log('Error fetching user data: ' . $e->getMessage());
        return false; // Indicate failure
    }
}

    
}


