<?php
require_once 'includes/header.php';

// Get available parking slots
$sql = "SELECT COUNT(*) as available_slots FROM parking_slots WHERE status = 'available'";
$result = $conn->query($sql);
$available = $result->fetch(PDO::FETCH_ASSOC)['available_slots'];
?>

    <div class="max-w-4xl mx-auto">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistem Parkir Otomatis</h1>
        <p class="text-gray-600">Slot Tersedia: <span class="font-bold text-green-600"><?php echo $available; ?></span></p>
    </div>

    <!-- Main Content -->
    <div class="grid md:grid-cols-2 gap-8">
        <!-- Camera Simulation Section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Kamera ANPR</h2>
            <div class="relative">
                <!-- Simulated camera view using a placeholder image -->
                <img src="https://images.pexels.com/photos/1004409/pexels-photo-1004409.jpeg" 
                     alt="Camera View" 
                     class="w-full h-64 object-cover rounded-lg mb-4">
                <div class="absolute top-2 right-2 animate-pulse">
                    <div class="h-3 w-3 bg-red-500 rounded-full"></div>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-4">Arahkan kendaraan ke area scan untuk membaca plat nomor secara otomatis</p>
        </div>

        <!-- Entry Form Section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Registrasi Masuk</h2>
            <form action="proses_parkir.php" method="POST" class="space-y-4">
                <div>
                    <label for="plate_number" class="block text-gray-700 mb-2">Plat Nomor</label>
                    <input type="text" 
                           id="plate_number" 
                           name="plate_number" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Contoh: B 1234 CD"
                           required>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Jenis Kendaraan</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center justify-center p-4 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors">
                            <input type="radio" name="vehicle_type" value="motor" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-motorcycle text-2xl mb-2"></i>
                                <div>Motor</div>
                            </div>
                        </label>
                        <label class="flex items-center justify-center p-4 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors">
                            <input type="radio" name="vehicle_type" value="mobil" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-car text-2xl mb-2"></i>
                                <div>Mobil</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                    Masuk Parkir
                </button>
            </form>
        </div>
    </div>

    <!-- Information Cards -->
    <div class="grid md:grid-cols-3 gap-6 mt-8">
        <!-- Tarif Card -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center mb-4">
                <i class="fas fa-money-bill text-green-500 text-2xl mr-3"></i>
                <h3 class="text-lg font-semibold">Tarif Parkir</h3>
            </div>
            <?php
            $sql = "SELECT vehicle_type, rate_per_hour FROM rates";
            $result = $conn->query($sql);
            while($rate = $result->fetch(PDO::FETCH_ASSOC)) {
                echo "<p class='text-gray-600 mb-2'>" . 
                     ucfirst($rate['vehicle_type']) . ": Rp " . 
                     number_format($rate['rate_per_hour'], 0, ',', '.') . "/jam</p>";
            }
            ?>
        </div>

        <!-- Payment Methods Card -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center mb-4">
                <i class="fas fa-credit-card text-blue-500 text-2xl mr-3"></i>
                <h3 class="text-lg font-semibold">Metode Pembayaran</h3>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div class="text-center">
                    <i class="fas fa-qrcode text-2xl mb-2"></i>
                    <p class="text-sm">QRIS</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-wallet text-2xl mb-2"></i>
                    <p class="text-sm">DANA</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-money-bill-wave text-2xl mb-2"></i>
                    <p class="text-sm">OVO</p>
                </div>
            </div>
        </div>

        <!-- Operating Hours Card -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center mb-4">
                <i class="fas fa-clock text-purple-500 text-2xl mr-3"></i>
                <h3 class="text-lg font-semibold">Jam Operasional</h3>
            </div>
            <p class="text-gray-600 mb-2">Senin - Jumat: 06:00 - 22:00</p>
            <p class="text-gray-600">Sabtu - Minggu: 08:00 - 20:00</p>
        </div>
    </div>
</div>

<script>
// Simulate ANPR by auto-filling a random plate number (for demo purposes)
document.addEventListener('DOMContentLoaded', function() {
    // Add plate number simulation
    const simulateButton = document.createElement('button');
    simulateButton.textContent = 'Simulasi Baca Plat';
    simulateButton.className = 'mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300';
    simulateButton.onclick = function() {
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const randomLetter = () => letters.charAt(Math.floor(Math.random() * letters.length));
        const randomNum = () => Math.floor(Math.random() * 9999).toString().padStart(4, '0');
        const plateNumber = `${randomLetter()} ${randomNum()} ${randomLetter()}${randomLetter()}`;
        document.getElementById('plate_number').value = plateNumber;
    };
    document.querySelector('.relative').appendChild(simulateButton);

    // Enhance radio button selection UI
    const vehicleTypeLabels = document.querySelectorAll('input[name="vehicle_type"] + div').forEach(div => {
        const radio = div.parentElement.querySelector('input[type="radio"]');
        div.parentElement.addEventListener('click', () => {
            // Remove active state from all labels
            document.querySelectorAll('input[name="vehicle_type"]').forEach(input => {
                input.parentElement.classList.remove('bg-blue-50', 'border-blue-500');
            });
            // Add active state to selected label
            radio.checked = true;
            div.parentElement.classList.add('bg-blue-50', 'border-blue-500');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
