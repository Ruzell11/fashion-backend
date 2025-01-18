<?php

class ServiceController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllServices($category = null) {
        // Start with the base SQL query
        $sql = "SELECT * FROM salon_services";
        
        // If a category is passed, add a WHERE clause to filter by category
        if ($category) {
            $sql .= " WHERE service_category = :category";
        }
    
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare($sql);
    
        // If category is provided, bind the parameter
        if ($category) {
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        }
    
        // Execute the query
        $stmt->execute();
    
        // Fetch and return the results as an associative array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function proceedToCheckout($formData) {

    
        // Validate input data
        $userId = $formData['user_id'];
        $customerName = $formData['customer_name'] ?? null;
        $phoneNumber = $formData['phone_number'] ?? null;
        $serviceId = $formData['id'] ?? null;
        $appointmentDate = $formData['appointment_date'] ?? null;
    
        if (empty($customerName) || empty($phoneNumber) || empty($serviceId)) {
            http_response_code(400);
            return json_encode(['message' => 'Please provide customer name, phone number, and service ID.']);
        }
    
        // Fetch service details from the database
        $sql = "SELECT * FROM salon_services WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $serviceId);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$service) {
            return json_encode(['message' => 'Service not found. Please try again.']);
        }
    
        // Check availability (custom logic can be added here)
        if ($this->isServiceAvailable($serviceId)) {
            // Proceed with creating the appointment
            $this->createAppointment($userId, $customerName, $phoneNumber, $serviceId, $appointmentDate);
            return json_encode(['message' => 'Appointment booked successfully for service: ' . $service['service_name']]);
        } else {
            return json_encode(['message' => 'Service "' . $service['service_name'] . '" is not available.']);
        }
    }
    
    private function isServiceAvailable($serviceId) {
        // Custom logic for availability (you can modify this)
        return true;  // Assume the service is available for this example
    }
    
    private function createAppointment($userId, $customerName, $phoneNumber, $serviceId, $appointmentDate) {
        // Logic to create an appointment in the appointments table
        $sql = "INSERT INTO appointments (user_id, customer_name, phone_number, service_id, appointment_date) VALUES (:user_id, :customer_name, :phone_number, :service_id, :appointment_date)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'customer_name' => $customerName,
            'phone_number' => $phoneNumber,
            'service_id' => $serviceId,
            'appointment_date' => $appointmentDate 
        ]);
    }
    
    public function getAllAppointments() {
        // Prepare SQL statement to fetch all appointments
        $sql = "SELECT a.id, a.customer_name, a.phone_number, a.appointment_date, 
                       s.service_name, u.username 
                FROM appointments a
                JOIN salon_services s ON a.id = s.id
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
            $sql = "SELECT a.id AS appointment_id, 
            a.customer_name, 
            a.phone_number, 
            a.appointment_date, 
            s.service_name, 
            s.id AS service_id, 
            u.username, 
            s.price
     FROM appointments a
     JOIN salon_services s ON a.service_id = s.id
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


    public function getAppointmentById($appointment_id) {
        try {
            // Prepare SQL statement to fetch a specific appointment by its ID
            $sql = "SELECT a.id, a.customer_name, a.phone_number, a.appointment_date, a.service_id, 
                           s.service_name, s.id, u.username, s.price 
                    FROM appointments a
                    JOIN salon_services s ON a.service_id = s.id
                    JOIN users u ON a.user_id = u.id
                    WHERE a.id = :appointment_id"; 
    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT); // Bind the appointment ID parameter
            $stmt->execute();
    
            // Fetch the appointment for the given ID
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($appointment) {
                return json_encode($appointment);
            } else {
                return json_encode(['message' => 'No appointment found with this ID.']);
            }
        } catch (PDOException $e) {
            // Log error or return an error message
            error_log("Error fetching appointment ID $appointment_id: " . $e->getMessage());
            return json_encode(['error' => 'Failed to fetch appointment. Please try again later.']);
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


    public function editAppointment($data) {

        var_dump($data);
        // Extract data from the input array
        $customerName = $data['customer_name'];
        $phoneNumber = $data['phone_number'];
        $serviceId = (int)$data['service_id'];
        $appointmentDate = $data['appointment_date'];
        $appointmentId =(int)$data['appointment_id'];
    
        // SQL query to update the appointment
        $sql = "UPDATE appointments 
                SET phone_number = :phone_number, 
                    customer_name = :customer_name,
                    service_id = :service_id, 
                    appointment_date = :appointment_date 
                WHERE id = :appointment_id";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Execute the statement with the extracted data
        $stmt->execute([
            'customer_name' => $customerName,
            'phone_number' => $phoneNumber,
            'service_id' => $serviceId,
            'appointment_date' => $appointmentDate,
            'appointment_id' => $appointmentId
        ]);
    
        // Optionally, return a success message or the number of rows affected
        return json_encode(['message' => 'Appointment updated successfully.']);
    }

    public function markAppointmentAsDone($appointmentId) {
        // Logic to update the is_done status in the appointments table
        $sql = "UPDATE appointments SET is_done = 1 WHERE id = :id";
    
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $appointmentId]);
    
        // Check if the row was updated
        if ($stmt->rowCount() > 0) {
            return json_encode(['message' => 'Appointment marked as done.']);
        } else {
            return json_encode(['message' => 'No appointment found with the provided ID.']);
        }
    }
    
}


