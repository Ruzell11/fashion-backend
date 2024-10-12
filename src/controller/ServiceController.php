<?php

class ServiceController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllServices() {
        $sql = "SELECT * FROM salon_services";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function proceedToCheckout($formData) {
    
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return json_encode(['message' => 'Unauthorized: Please login to proceed to checkout.']);
        }
    
        $userId = $_SESSION['user_id'];
    
        // Validate input data
        $customerName = $formData['customer_name'] ?? null;
        $phoneNumber = $formData['phone_number'] ?? null;
        $serviceId = $formData['service_id'] ?? null;
    
        if (empty($customerName) || empty($phoneNumber) || empty($serviceId)) {
            http_response_code(400);
            return json_encode(['message' => 'Please provide customer name, phone number, and service ID.']);
        }
    
        // Fetch service details from the database
        $sql = "SELECT * FROM salon_services WHERE service_id = :service_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':service_id', $serviceId);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$service) {
            return json_encode(['message' => 'Service not found. Please try again.']);
        }
    
        // Check availability (custom logic can be added here)
        if ($this->isServiceAvailable($serviceId)) {
            // Proceed with creating the appointment
            $this->createAppointment($userId, $customerName, $phoneNumber, $service, $appointmentDate);
            return json_encode(['message' => 'Appointment booked successfully for service: ' . $service['service_name']]);
        } else {
            return json_encode(['message' => 'Service "' . $service['service_name'] . '" is not available.']);
        }
    }
    
    private function isServiceAvailable($serviceId) {
        // Custom logic for availability (you can modify this)
        return true;  // Assume the service is available for this example
    }
    
    private function createAppointment($userId, $customerName, $phoneNumber, $service) {
        // Logic to create an appointment in the appointments table
        $sql = "INSERT INTO appointments (user_id, customer_name, phone_number, service_id, appointment_date) VALUES (:user_id, :customer_name, :phone_number, :service_id, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'customer_name' => $customerName,
            'phone_number' => $phoneNumber,
            'service_id' => $service['service_id'],
            'appointment_date' => $appointmentDate  
        ]);
    }
    
    public function getAllAppointments() {
        // Prepare SQL statement to fetch all appointments
        $sql = "SELECT a.id, a.customer_name, a.phone_number, a.appointment_date, 
                       s.service_name, u.username 
                FROM appointments a
                JOIN salon_services s ON a.service_id = s.service_id
                JOIN users u ON a.user_id = u.id"; 
    
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    
        // Fetch all appointments
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if ($appointments) {
            return json_encode($appointments);
        } else {
            return json_encode(['message' => 'No appointments found.']);
        }
    }

    public function getAllAppointmentsByUser($user_id) {
        try {
            // Prepare SQL statement to fetch appointments for a specific user
            $sql = "SELECT a.id, a.customer_name, a.phone_number, a.appointment_date, 
                           s.service_name, u.username 
                    FROM appointments a
                    JOIN salon_services s ON a.service_id = s.service_id
                    JOIN users u ON a.user_id = u.id
                    WHERE a.user_id = :user_id"; 
        
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Bind the user ID parameter
            $stmt->execute();
        
            // Fetch all appointments for the given user
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            if (!empty($appointments)) {
                return json_encode($appointments);
            } else {
                return json_encode(['message' => 'No appointments found for this user.']);
            }
        } catch (PDOException $e) {
            // Log error or return an error message
            error_log("Error fetching appointments for user ID $user_id: " . $e->getMessage());
            return json_encode(['error' => 'Failed to fetch appointments. Please try again later.']);
        }
    }
    
    public function deleteAppointment($appointment_id) {
        try {
            // Prepare SQL statement to delete the appointment
            $sql = "DELETE FROM appointments WHERE id = :appointment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
            $stmt->execute();
    
            // Check if any rows were affected (meaning the appointment was deleted)
            if ($stmt->rowCount() > 0) {
                return json_encode(['message' => 'Appointment deleted successfully']);
            } else {
                return json_encode(['message' => 'No appointment found with the provided ID']);
            }
        } catch (PDOException $e) {
            // Log error or return an error message
            error_log("Error deleting appointment: " . $e->getMessage());
            return json_encode(['error' => 'Failed to delete appointment. Please try again later.']);
        }
    }
    

}