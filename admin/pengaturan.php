<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_rate':
                $vehicle_type = $_POST['vehicle_type'];
                $rate = $_POST['rate'];
                $stmt = $conn->prepare("UPDATE rates SET rate_per_hour = ? WHERE vehicle_type = ?");
                $stmt->bind_param("ds", $rate, $vehicle_type);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Tarif berhasil diperbarui!";
                } else {
                    $_SESSION['error_message'] = "Gagal memperbarui tarif!";
                }
                break;

            case 'update_slot':
                $slot_id = $_POST['slot_id'];
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE parking_slots SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $slot_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Status slot berhasil diperbarui!";
                } else {
                    $_SESSION['error_message'] = "Gagal memperbarui status slot!";
                }
                break;

            case 'add_slot':
                $slot_number = $_POST['slot_number'];
                $stmt = $conn->prepare("INSERT INTO parking_slots (slot_number, status) VALUES (?, 'available')");
                $stmt->bind_param("s", $slot_number);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Slot parkir baru berhasil ditambahkan!";
                } else {
                    $_SESSION['error_message'] = "Gagal menambahkan slot parkir!";
                }
                break;

            case 'backup_db':
                // Create backup directory if it doesn't exist
                $backup_dir = __DIR__ . '/../backups';
                if (!file_exists($backup_dir)) {
                    mkdir($backup_dir, 0777, true);
                }

                // Generate backup filename with timestamp
                $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

                // Build mysqldump command
                $command = sprintf(
                    'mysqldump -h %s -u %s %s > %s',
                    DB_HOST,
                    DB_USER,
                    DB_NAME,
                    $backup_file
                );

                // Execute backup
                if (system($command) !== false) {
                    $_SESSION['success_message'] = "Backup database berhasil dibuat!";
                } else {
                    $_SESSION['error_message'] = "Gagal membuat backup database!";
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: pengaturan.php");
        exit();
    }
}

// Get current rates
$rates = $conn->query("SELECT * FROM rates ORDER BY vehicle_type");

// Get parking slots
$slots = $conn->query("SELECT * FROM parking_slots ORDER BY slot_number");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Sistem Parkir</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <a href="/admin/dashboard.php" class="text-xl font-bold">Admin Dashboard</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/laporan.php" class="hover:text-blue-200">Laporan</a>
                    <a href="/admin/pengaturan.php" class="hover:text-blue-200">Pengaturan</a>
                    <a href="/logout.php" class="hover:text-blue-200">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Parking Rates Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Pengaturan Tarif</h2>
                <div class="space-y-4">
                    <?php while ($rate = $rates->fetch_assoc()): ?>
                        <form method="POST" class="flex items-center space-x-4">
                            <input type="hidden" name="action" value="update_rate">
                            <input type="hidden" name="vehicle_type" value="<?php echo $rate['vehicle_type']; ?>">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    <?php echo ucfirst($rate['vehicle_type']); ?>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="number" 
                                           name="rate" 
                                           value="<?php echo $rate['rate_per_hour']; ?>"
                                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-12 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="0">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">/jam</span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                Update
                            </button>
                        </form>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Parking Slots Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Manajemen Slot Parkir</h2>
                
                <!-- Add New Slot Form -->
                <form method="POST" class="mb-6">
                    <input type="hidden" name="action" value="add_slot">
                    <div class="flex space-x-4">
                        <div class="flex-1">
                            <input type="text" 
                                   name="slot_number" 
                                   required
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="Nomor Slot (contoh: A1)">
                        </div>
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                            Tambah Slot
                        </button>
                    </div>
                </form>

                <!-- Slots List -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <?php while ($slot = $slots->fetch_assoc()): ?>
                        <form method="POST" class="bg-gray-50 p-4 rounded-md">
                            <input type="hidden" name="action" value="update_slot">
                            <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                            <div class="text-sm font-medium text-gray-700 mb-2">
                                Slot <?php echo $slot['slot_number']; ?>
                            </div>
                            <select name="status" 
                                    onchange="this.form.submit()"
                                    class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <option value="available" <?php echo $slot['status'] === 'available' ? 'selected' : ''; ?>>
                                    Tersedia
                                </option>
                                <option value="maintenance" <?php echo $slot['status'] === 'maintenance' ? 'selected' : ''; ?>>
                                    Maintenance
                                </option>
                            </select>
                        </form>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Database Backup Section -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Backup Database</h2>
            <form method="POST" class="flex items-center space-x-4">
                <input type="hidden" name="action" value="backup_db">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-md hover:bg-yellow-600">
                    <i class="fas fa-database mr-2"></i>
                    Backup Sekarang
                </button>
                <p class="text-sm text-gray-500">
                    Backup akan disimpan di folder /backups dengan timestamp
                </p>
            </form>
        </div>
    </main>
</body>
</html>
