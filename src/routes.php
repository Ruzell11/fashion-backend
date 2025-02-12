<?php
 header("Access-Control-Allow-Origin: *");
 header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT, PATCH");
 header("Access-Control-Allow-Headers: Content-Type, Authorization");
 header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
   
    
http_response_code(204); // Respond to OPTIONS requests with 204 No Content
exit;
}

require_once './src/database.php';
require_once './src/controller/UserController.php';
require_once './src/controller/ServiceController.php';
require_once './src/controller/PaymentGateway.php';
require_once './src/controller/SmsGateway.php';


// Connect to the database
$pdo = connectDatabase();
if (!$pdo) {    
http_response_code(500);
echo json_encode(['message' => 'Database connection failed.']);
exit;
}

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}


// Get the full request URI and parse it
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


$basePath = '/fashion-backend';


$uri = str_replace($basePath, '', $uri);
$requestMethod = $_SERVER['REQUEST_METHOD'];


switch ($uri) {
    case '/user':
        if ($requestMethod === 'GET') {
            // Initialize the UserController
            $userController = new UserController($pdo);
            
            // Retrieve query parameters (e.g., ?user_id=1)
            $queryParams = $_GET;
    
            // Check if 'user_id' is provided in the query parameters
            if (isset($queryParams['user_id'])) {
                $userId = intval($queryParams['user_id']); // Ensure 'user_id' is an integer
    
                // Fetch user data using the controller
                $userData = $userController->getUserData($userId);
    
                if ($userData) {
                    // Send the user data as a JSON response
                    echo json_encode($userData);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'User not found.']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required parameter: user_id.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed.']);
        }
        break;
        
        case '/user/edit':
            if ($requestMethod === 'PATCH') {
                // Initialize the UserController
                $userController = new UserController($pdo);
        
                // Get the raw input data (e.g., JSON payload)
                $inputData = json_decode(file_get_contents('php://input'), true);
        
                // Check if input data is valid
                if (is_array($inputData)) {
                    // Call the edit method to update user information
                    $response = $userController->edit($inputData);
                    echo $response; // Return the response from the edit method
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid input data.']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['message' => 'Method not allowed.']);
            }
            break;

            case '/paid':
                if ($requestMethod === 'PATCH') {
                
                    $serviceController = new ServiceController($pdo);
            
               
                    $inputData = json_decode(file_get_contents('php://input'), true);
            
                    // Check if input data is valid
                    if (is_array($inputData)) {
               
                        $response = $serviceController->isPaid($inputData);
                        echo $response;
                    } else {
                        http_response_code(400);
                        echo json_encode(['message' => 'Invalid input data.']);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['message' => 'Method not allowed.']);
                }
                break;
        
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
                // Check if there's a 'category' query parameter
                $category = isset($_GET['category']) ? $_GET['category'] : null;
        
                // Instantiate the ServiceController and call getAllServices with or without a category
                $serviceController = new ServiceController($pdo);
                $response = $serviceController->getAllServices($category);
        
                // Return the response as JSON
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
                        /*if (!isset($_SESSION['user_id'])) {
                            http_response_code(401);
                            echo json_encode(['message' => 'Unauthorized: Please login to access this resource']);
                            exit;
                        }*/
                        
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
                        

                        case '/appointment/edit':
                            if ($requestMethod === 'PATCH') {
                                // Check if user is logged in by checking for a session or query parameter
                                $data = json_decode(file_get_contents('php://input'), true);
                        
                                // if (!$user_id) {
                                //     http_response_code(401);
                                //     echo json_encode(['message' => 'Unauthorized: Please login or provide a user ID to access this resource']);
                                //     exit;
                                // }
                        
         
                                $serviceController = new ServiceController($pdo);
                                $response = $serviceController->editAppointment($data); 
                                echo $response;
                            } else {
                                http_response_code(405);
                                echo json_encode(['message' => 'Method not allowed.']);
                            }
                            break;

                        case '/appointment/cancel':
                            if ($requestMethod === 'DELETE') {
                                // Check if user is logged in
                                // if (!isset($_SESSION['user_id'])) {
                                //     http_response_code(401);
                                //     echo json_encode(['message' => 'Unauthorized: Please login to access this resource']);
                                //     exit;
                                // }
                        
                                // Get the appointment ID from the query string (or body if sent in DELETE payload)
                                $data = json_decode(file_get_contents('php://input'), true);
                                $appointment_id = $data['appointment_id'] ?? null;
                        
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

                            case '/appointment/done':
                                if ($requestMethod === 'PATCH') {
                                    // Get the data from the request body
                                    $data = json_decode(file_get_contents('php://input'), true);
                            
                                    // Check if appointment_id is provided
                                    if (isset($data['appointment_id'])) {
                                        // Create an instance of your controller
                                        $serviceController = new ServiceController($pdo);
                                        $response = $serviceController->markAppointmentAsDone($data['appointment_id']);
                                        echo $response;
                                    } else {
                                        http_response_code(400);
                                        echo json_encode(['message' => 'Bad Request: appointment_id is required.']);
                                    }
                                } else {
                                    http_response_code(405);
                                    echo json_encode(['message' => 'Method not allowed.']);
                                }
                                break;
                                case '/sms':
                                    $smsGateway = new SmsGateway();
                                    $data = json_decode(file_get_contents('php://input'), true);
                                    $response = $smsGateway->sendSmsMessage($data['message'], $data['phone_numbers']);
                                    echo json_encode($response);
                                    
                                    break;
                                
                            case '/payment':
                                if ($requestMethod === 'POST') {
                                    // Get the data from the request body
                                    $data = json_decode(file_get_contents('php://input'), true);

                                    if (isset($data['appointment_id'])) {
                                       
                                        $serviceController = new ServiceController($pdo);
                                        $appointmentDetailsJson = $serviceController->getAppointmentById($data['appointment_id']);
                                        $appointmentDetails = json_decode($appointmentDetailsJson, true);

                                       
                                        if (isset($appointmentDetailsJson)) {
                                            // Prepare payment details
                                            $amount = $data['amount'];
                                            $name = 'Service ' . $appointmentDetails['service_name'] . rand(1, 1000000);
                                            $currency = 'PHP'; 
                                            $description = 'Payment for Appointment ID: ' . $appointmentDetails['service_name'];
                                            $successUrl = 'http://127.0.0.1:5502/success.html?payment_status=success&appointment_id=' . $data['appointment_id']; 
                                            $cancelUrl = 'http://127.0.0.1:5502/cancel.html';
                                        
                                            
                                            // Create an instance of PaymentGateway
                                            $paymentGateway = new PaymentGateway(); 
                                            
                                            // Create a Checkout Session
                                            $checkoutSession = $paymentGateway->createCheckoutSession(
                                                $amount,
                                                $currency,
                                                $description,
                                                $successUrl,
                                                $cancelUrl,
                                                $name
                                            );
                            
                                            // Output the checkout session response
                                            echo json_encode($checkoutSession);
                                        } else {
                                            // Handle unsuccessful appointment marking
                                            http_response_code(400);
                                            echo json_encode(['message' => 'Failed to mark appointment as done.']);
                                        }
                                    } else {
                                        http_response_code(400);
                                        echo json_encode(['message' => 'Bad Request: appointment_id is required.']);
                                    }
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
