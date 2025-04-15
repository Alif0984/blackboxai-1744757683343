<?php
// Database configuration
$db_file = __DIR__ . '/../database/parking.sqlite';
$db_dir = dirname($db_file);

// Create database directory if it doesn't exist
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

try {
    // Create PDO connection to SQLite
    $conn = new PDO("sqlite:$db_file");
    
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable foreign key support
    $conn->exec('PRAGMA foreign_keys = ON');

    // Create tables
    $conn->exec("
        -- Users table
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT CHECK(role IN ('admin', 'petugas', 'pengemudi')) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Vehicles table
        CREATE TABLE IF NOT EXISTS vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plate_number TEXT NOT NULL,
            encrypted_plate TEXT NOT NULL,
            vehicle_type TEXT CHECK(vehicle_type IN ('motor', 'mobil')) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Parking slots table
        CREATE TABLE IF NOT EXISTS parking_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slot_number TEXT NOT NULL,
            status TEXT CHECK(status IN ('available', 'occupied', 'maintenance')) DEFAULT 'available',
            vehicle_id INTEGER,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        );

        -- Transactions table
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehicle_id INTEGER NOT NULL,
            slot_id INTEGER NOT NULL,
            entry_time DATETIME NOT NULL,
            exit_time DATETIME,
            amount DECIMAL(10,2),
            payment_method TEXT CHECK(payment_method IN ('QRIS', 'DANA', 'OVO')),
            payment_status TEXT CHECK(payment_status IN ('pending', 'paid', 'failed')) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
            FOREIGN KEY (slot_id) REFERENCES parking_slots(id)
        );

        -- Rates table
        CREATE TABLE IF NOT EXISTS rates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehicle_type TEXT CHECK(vehicle_type IN ('motor', 'mobil')) NOT NULL,
            rate_per_hour DECIMAL(10,2) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Insert default data if tables are empty
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
        // Insert default admin user (password: admin123)
        $conn->exec("INSERT INTO users (username, password, role) VALUES 
            ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM rates");
    if ($result->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
        // Insert default parking rates
        $conn->exec("INSERT INTO rates (vehicle_type, rate_per_hour, description) VALUES 
            ('motor', 2000.00, 'Tarif parkir motor per jam'),
            ('mobil', 5000.00, 'Tarif parkir mobil per jam')");
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM parking_slots");
    if ($result->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
        // Insert default parking slots
        $conn->exec("INSERT INTO parking_slots (slot_number) VALUES 
            ('A1'), ('A2'), ('A3'), ('B1'), ('B2'), ('B3'), ('C1'), ('C2'), ('C3'), ('D1')");
    }
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
