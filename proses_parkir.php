<?php
require_once 'config/database.php';
session_start();

// Function to encrypt plate number
function encryptPlate($plate) {
    $key = "parking_system_secret_key"; // In production, use a secure key storage
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($plate, $cipher, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate input
        if (empty($_POST['plate_number']) || empty($_POST['vehicle_type'])) {
            throw new Exception("Semua field harus diisi!");
        }

        $plate_number = strtoupper(trim($_POST['plate_number']));
        $vehicle_type = $_POST['vehicle_type'];

        // Validate plate number format (example: B 1234 CD)
        if (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}$/', $plate_number)) {
            throw new Exception("Format plat nomor tidak valid!");
        }

        // Start transaction
        $conn->beginTransaction();

        // Check if vehicle already exists
        $stmt = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ?");
        $stmt->execute([$plate_number]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicle) {
            // Check if vehicle is already parked
            $check_parked = $conn->prepare("
                SELECT t.id 
                FROM transactions t 
                WHERE t.vehicle_id = ? AND t.exit_time IS NULL
            ");
            $check_parked->execute([$vehicle['id']]);
            
            if ($check_parked->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Kendaraan ini sudah terparkir!");
            }
            
            $vehicle_id = $vehicle['id'];
        } else {
            // Insert new vehicle
            $encrypted_plate = encryptPlate($plate_number);
            $stmt = $conn->prepare("
                INSERT INTO vehicles (plate_number, encrypted_plate, vehicle_type) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$plate_number, $encrypted_plate, $vehicle_type]);
            $vehicle_id = $conn->lastInsertId();
        }

        // Get available parking slot
        $stmt = $conn->prepare("
            SELECT id, slot_number 
            FROM parking_slots 
            WHERE status = 'available' 
            LIMIT 1
        ");
        $stmt->execute();
        $slot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$slot) {
            throw new Exception("Maaf, tidak ada slot parkir yang tersedia!");
        }

        // Create transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (vehicle_id, slot_id, entry_time) 
            VALUES (?, ?, datetime('now'))
        ");
        $stmt->execute([$vehicle_id, $slot['id']]);

        // Update slot status
        $stmt = $conn->prepare("
            UPDATE parking_slots 
            SET status = 'occupied', vehicle_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$vehicle_id, $slot['id']]);

        // Commit transaction
        $conn->commit();

        // Set success message
        $_SESSION['success_message'] = "Selamat datang! Silakan parkir di slot " . $slot['slot_number'];
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php");
        exit();
    }
} else {
    // If not POST request, redirect to index
    header("Location: index.php");
    exit();
}
?>
