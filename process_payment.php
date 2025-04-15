<?php
require_once 'config/database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate input
        if (empty($_POST['transaction_id']) || empty($_POST['payment_method'])) {
            throw new Exception("Data pembayaran tidak lengkap!");
        }

        $transaction_id = $_POST['transaction_id'];
        $payment_method = $_POST['payment_method'];

        // Start transaction
        $conn->begin_transaction();

        // Get transaction details
        $stmt = $conn->prepare("
            SELECT 
                t.*, 
                v.vehicle_type,
                ps.id as slot_id
            FROM transactions t
            JOIN vehicles v ON t.vehicle_id = v.id
            JOIN parking_slots ps ON t.slot_id = ps.id
            WHERE t.id = ? AND t.exit_time IS NULL
        ");
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Transaksi tidak ditemukan atau sudah selesai!");
        }

        $transaction = $result->fetch_assoc();

        // Calculate parking fee
        $entry_time = new DateTime($transaction['entry_time']);
        $exit_time = new DateTime();
        $duration = $entry_time->diff($exit_time);
        $hours = $duration->h + ($duration->days * 24);
        if ($duration->i > 0) $hours++; // Round up to the next hour

        // Get rate for vehicle type
        $stmt = $conn->prepare("SELECT rate_per_hour FROM rates WHERE vehicle_type = ?");
        $stmt->bind_param("s", $transaction['vehicle_type']);
        $stmt->execute();
        $rate_result = $stmt->get_result();
        $rate = $rate_result->fetch_assoc()['rate_per_hour'];

        $total_amount = $hours * $rate;

        // Update transaction with payment details
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET 
                exit_time = NOW(),
                amount = ?,
                payment_method = ?,
                payment_status = 'paid'
            WHERE id = ?
        ");
        $stmt->bind_param("dsi", $total_amount, $payment_method, $transaction_id);
        $stmt->execute();

        // Update parking slot status
        $stmt = $conn->prepare("
            UPDATE parking_slots 
            SET 
                status = 'available',
                vehicle_id = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $transaction['slot_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Set success message
        $_SESSION['success_message'] = "Pembayaran berhasil! Silakan keluar dari area parkir.";
        
        // Return JSON response for AJAX handling
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Pembayaran berhasil! Silakan keluar dari area parkir.',
            'amount' => $total_amount,
            'duration' => $hours,
            'payment_method' => $payment_method
        ]);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Set error message
        $_SESSION['error_message'] = $e->getMessage();
        
        // Return JSON response for AJAX handling
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
} else {
    // If not POST request, redirect to payment page
    header("Location: payment.php");
    exit();
}
?>
