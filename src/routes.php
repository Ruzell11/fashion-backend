<?php
header("Access-Control-Allow-Origin: http://localhost:5501");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

http_response_code(204); // Respond to OPTIONS requests with 204 No Content
exit;
}

require_once './src/database.php';
require_once './src/controller/UserController.php';
require_once './src/controller/ServiceController.php';

// Connect to the database
$pdo = connectDatabase();
if (!$pdo) {    
http_response_code(500);
echo json_encode(['message' => 'Database connection failed.']);
exit;
}


// Get the full request URI and parse it
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


$basePath = '/fashion-backend';


$uri = str_replace($basePath, '', $uri);
$requestMethod = $_SERVER['REQUEST_METHOD'];


switch ($uri) {
case '/sign-up':
    if ($requestMethod === 'POST') {
        
        $userController = new UserController($pdo);
        $data = json_decode(file_get_contents('php://input'), true);

        $response = $userController->signUp($data);

        echo $response;
    } else {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
    }
    break;

    case '/login':
        if ($requestMethod === 'POST') {
            
            $userController = new UserController($pdo);
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $userController->login($data);
            

            echo $response;
        } else {
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed.']);
        }
        break;

    case '/services':
        if ($requestMethod === 'GET') {
      
                $serviceController = new ServiceController($pdo);
                $response = $serviceController->getAllServices();
                echo json_encode($response);
            } else {
                http_response_code(405);
                echo json_encode(['message' => 'Method not allowed.']);
            }
            break;

            case '/checkout':
                if ($requestMethod === 'POST') {

                    $data = json_decode(file_get_contents('php://input'), true);
                    $userController = new UserController($pdo);

                    if($userController->isAuthenticated($data['user_id'], $pdo)){
                        var_dump($data);
        
                        $serviceController = new ServiceController($pdo);
                        $response = $serviceController->proceedToCheckout($data); 
                        echo $response;
                    }
                    
                    

                    
                } else {
                    http_response_code(405);
                    echo json_encode(['message' => 'Method not allowed.']);
                }
                break;

                case '/appointments':
                    if ($requestMethod === 'GET') {
                        if (!isset($_SESSION['user_id'])) {
                            http_response_code(401);
                            echo json_encode(['message' => 'Unauthorized: Please login to access this resource']);
                            exit;
                        }
                        
                        $serviceController = new ServiceController($pdo);
                        $response = $serviceController->getAllAppointments();
                        echo $response;
                    } else {
                        http_response_code(405);
                        echo json_encode(['message' => 'Method not allowed.']);
                    }
                    break;

                    
                    case '/appointment':
                        if ($requestMethod === 'GET') {
                            // Check if user is logged in by checking for a session or query parameter
                            $user_id = $_GET['user_id'];
                    
                            // if (!$user_id) {
                            //     http_response_code(401);
                            //     echo json_encode(['message' => 'Unauthorized: Please login or provide a user ID to access this resource']);
                            //     exit;
                            // }
                    
                            // Initialize the service controller and fetch appointments based on the user ID
                            $serviceController = new ServiceController($pdo);
                            $response = $serviceController->getAllAppointmentsByUser($user_id); // Pass user_id to the method
                            echo $response;
                        } else {
                            http_response_code(405);
                            echo json_encode(['message' => 'Method not allowed.']);
                        }
                        break;

                        case '/appointment/cancel':
                            if ($requestMethod === 'DELETE') {
                                // Check if user is logged in
                                if (!isset($_SESSION['user_id'])) {
                                    http_response_code(401);
                                    echo json_encode(['message' => 'Unauthorized: Please login to access this resource']);
                                    exit;
                                }
                        
                                // Get the appointment ID from the query string (or body if sent in DELETE payload)
                                $appointment_id = $_GET['appointment_id'] ?? null;
                        
                                if (!$appointment_id) {
                                    http_response_code(400);
                                    echo json_encode(['message' => 'Bad Request: Appointment ID is required']);
                                    exit;
                                }
                        
                                // Initialize the service controller and delete the appointment
                                $serviceController = new ServiceController($pdo);
                                $response = $serviceController->deleteAppointment($appointment_id);
                        
                                echo $response;
                            } else {
                                http_response_code(405);
                                echo json_encode(['message' => 'Method not allowed.']);
                            }
                            break;
        
// Add other routes here
case '/':
    
    echo json_encode(['message' => 'Welcome to the API!']);
    break;

default:
    http_response_code(404);
    echo json_encode(['message' => '404 - Page Not Found']);
    break;
}
