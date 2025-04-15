<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get current statistics
$stats = [];

// Total vehicles currently parked
$sql = "SELECT COUNT(*) as total FROM transactions WHERE exit_time IS NULL";
$result = $conn->query($sql);
$stats['current_vehicles'] = $result->fetch_assoc()['total'];

// Available parking slots
$sql = "SELECT COUNT(*) as total FROM parking_slots WHERE status = 'available'";
$result = $conn->query($sql);
$stats['available_slots'] = $result->fetch_assoc()['total'];

// Today's revenue
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE DATE(exit_time) = CURDATE() AND payment_status = 'paid'";
$result = $conn->query($sql);
$stats['today_revenue'] = $result->fetch_assoc()['total'];

// Today's transactions count
$sql = "SELECT COUNT(*) as total FROM transactions 
        WHERE DATE(entry_time) = CURDATE()";
$result = $conn->query($sql);
$stats['today_transactions'] = $result->fetch_assoc()['total'];

// Get last 7 days revenue data for chart
$sql = "SELECT 
            DATE(exit_time) as date,
            COALESCE(SUM(amount), 0) as revenue
        FROM transactions
        WHERE 
            exit_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND payment_status = 'paid'
        GROUP BY DATE(exit_time)
        ORDER BY date ASC";
$result = $conn->query($sql);
$revenue_data = [];
while ($row = $result->fetch_assoc()) {
    $revenue_data[$row['date']] = $row['revenue'];
}

// Get recent transactions
$sql = "SELECT 
            t.id,
            v.plate_number,
            ps.slot_number,
            t.entry_time,
            t.exit_time,
            t.amount,
            t.payment_status
        FROM transactions t
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN parking_slots ps ON t.slot_id = ps.id
        ORDER BY t.entry_time DESC
        LIMIT 10";
$recent_transactions = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Parkir</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Current Vehicles -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-500 rounded-full">
                        <i class="fas fa-car text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Kendaraan Parkir</p>
                        <p class="text-2xl font-semibold"><?php echo $stats['current_vehicles']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Available Slots -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-500 rounded-full">
                        <i class="fas fa-parking text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Slot Tersedia</p>
                        <p class="text-2xl font-semibold"><?php echo $stats['available_slots']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-500 rounded-full">
                        <i class="fas fa-money-bill-wave text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pendapatan Hari Ini</p>
                        <p class="text-2xl font-semibold">Rp <?php echo number_format($stats['today_revenue'], 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Today's Transactions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-500 rounded-full">
                        <i class="fas fa-receipt text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Transaksi Hari Ini</p>
                        <p class="text-2xl font-semibold"><?php echo $stats['today_transactions']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Grafik Pendapatan 7 Hari Terakhir</h2>
            <canvas id="revenueChart"></canvas>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Transaksi Terbaru</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plat Nomor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Slot
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu Masuk
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu Keluar
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Biaya
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo $transaction['plate_number']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $transaction['slot_number']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($transaction['entry_time'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $transaction['exit_time'] ? date('d/m/Y H:i', strtotime($transaction['exit_time'])) : '-'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $transaction['amount'] ? 'Rp ' . number_format($transaction['amount'], 0, ',', '.') : '-'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_class = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'paid' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800'
                                    ][$transaction['payment_status']];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo ucfirst($transaction['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Setup revenue chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode($revenue_data); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(revenueData),
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: Object.values(revenueData),
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
