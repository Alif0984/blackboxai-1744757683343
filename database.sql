-- Create tables for parking system

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'pengemudi') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plate_number VARCHAR(20) NOT NULL,
    encrypted_plate TEXT NOT NULL,
    vehicle_type ENUM('motor', 'mobil') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parking slots table
CREATE TABLE IF NOT EXISTS parking_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_number VARCHAR(10) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    vehicle_id INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    slot_id INT NOT NULL,
    entry_time DATETIME NOT NULL,
    exit_time DATETIME NULL,
    amount DECIMAL(10,2) NULL,
    payment_method ENUM('QRIS', 'DANA', 'OVO') NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id)
);

-- Rates table
CREATE TABLE IF NOT EXISTS rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_type ENUM('motor', 'mobil') NOT NULL,
    rate_per_hour DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default parking rates
INSERT INTO rates (vehicle_type, rate_per_hour, description) VALUES 
('motor', 2000.00, 'Tarif parkir motor per jam'),
('mobil', 5000.00, 'Tarif parkir mobil per jam');

-- Insert some default parking slots
INSERT INTO parking_slots (slot_number, status) VALUES 
('A1', 'available'),
('A2', 'available'),
('A3', 'available'),
('B1', 'available'),
('B2', 'available'),
('B3', 'available'),
('C1', 'available'),
('C2', 'available'),
('C3', 'available'),
('D1', 'available');
