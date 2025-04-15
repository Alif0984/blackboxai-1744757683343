<?php
require_once 'includes/header.php';

// Function to calculate parking duration and fee
function calculateParkingFee($entry_time, $vehicle_type, $rates) {
    $entry = new DateTime($entry_time);
    $exit = new DateTime();
    $duration = $entry->diff($exit);
    $hours = $duration->h + ($duration->days * 24);
    if ($duration->i > 0) $hours++; // Round up to the next hour
    
    $rate = $rates[$vehicle_type];
    return [
        'duration' => $hours,
        'fee' => $hours * $rate
    ];
}

// Get parking rates
$rates = [];
$sql = "SELECT vehicle_type, rate_per_hour FROM rates";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $rates[$row['vehicle_type']] = $row['rate_per_hour'];
}

// Get active parking transactions
$sql = "
    SELECT 
        t.id as transaction_id,
        v.plate_number,
        v.vehicle_type,
        ps.slot_number,
        t.entry_time,
        t.payment_status
    FROM transactions t
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN parking_slots ps ON t.slot_id = ps.id
    WHERE t.exit_time IS NULL AND t.payment_status = 'pending'
    ORDER BY t.entry_time DESC
";
$active_parkings = $conn->query($sql);
?>

<div class="max-w-4xl mx-auto">
    <!-- Header Section -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Pembayaran Parkir</h1>
        <p class="text-gray-600">Silakan pilih kendaraan yang akan keluar</p>
    </div>

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

    <!-- Active Parkings Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Kendaraan Aktif</h2>
        </div>
        
        <?php if ($active_parkings->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plat Nomor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jenis
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Slot
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Durasi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Biaya
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($parking = $active_parkings->fetch_assoc()): 
                            $parkingCalc = calculateParkingFee(
                                $parking['entry_time'], 
                                $parking['vehicle_type'], 
                                $rates
                            );
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo $parking['plate_number']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo ucfirst($parking['vehicle_type']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $parking['slot_number']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $parkingCalc['duration']; ?> jam
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Rp <?php echo number_format($parkingCalc['fee'], 0, ',', '.'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="showPaymentModal(<?php 
                                        echo htmlspecialchars(json_encode([
                                            'id' => $parking['transaction_id'],
                                            'plate' => $parking['plate_number'],
                                            'fee' => $parkingCalc['fee'],
                                            'duration' => $parkingCalc['duration']
                                        ])); 
                                    ?>)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Bayar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="px-6 py-4 text-center text-gray-500">
                Tidak ada kendaraan yang sedang parkir
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Detail Pembayaran</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 mb-2">Plat Nomor: <span id="modalPlate" class="font-semibold"></span></p>
                <p class="text-sm text-gray-500 mb-2">Durasi: <span id="modalDuration" class="font-semibold"></span> jam</p>
                <p class="text-sm text-gray-500 mb-4">Total Biaya: Rp <span id="modalFee" class="font-semibold"></span></p>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Pilih Metode Pembayaran
                    </label>
                    <select id="paymentMethod" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="QRIS">QRIS</option>
                        <option value="DANA">DANA</option>
                        <option value="OVO">OVO</option>
                    </select>
                </div>

                <!-- QR Code Simulation -->
                <div class="mb-4">
                    <img src="https://images.pexels.com/photos/8370772/pexels-photo-8370772.jpeg" 
                         alt="QR Code" 
                         class="w-48 h-48 mx-auto">
                </div>
            </div>
            <div class="items-center px-4 py-3">
                <form id="paymentForm" action="process_payment.php" method="POST">
                    <input type="hidden" id="transactionId" name="transaction_id">
                    <input type="hidden" id="selectedPaymentMethod" name="payment_method">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300">
                        Konfirmasi Pembayaran
                    </button>
                </form>
                <button onclick="closePaymentModal()" class="mt-3 px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showPaymentModal(data) {
    document.getElementById('modalPlate').textContent = data.plate;
    document.getElementById('modalDuration').textContent = data.duration;
    document.getElementById('modalFee').textContent = new Intl.NumberFormat('id-ID').format(data.fee);
    document.getElementById('transactionId').value = data.id;
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

// Update selected payment method when changed
document.getElementById('paymentMethod').addEventListener('change', function() {
    document.getElementById('selectedPaymentMethod').value = this.value;
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target == modal) {
        closePaymentModal();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
