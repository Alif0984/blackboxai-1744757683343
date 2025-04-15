<?php
session_start();
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Parkir Otomatis</title>
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
<body class="bg-gray-50 min-h-screen flex flex-col">
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-xl font-bold">Sistem Parkir</a>
                    <a href="/" class="hover:text-blue-200">Beranda</a>
                    <a href="/payment.php" class="hover:text-blue-200">Pembayaran</a>
                </div>
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <div class="flex items-center space-x-4">
                    <a href="/admin/dashboard.php" class="hover:text-blue-200">Dashboard</a>
                    <a href="/admin/laporan.php" class="hover:text-blue-200">Laporan</a>
                    <a href="/admin/pengaturan.php" class="hover:text-blue-200">Pengaturan</a>
                    <a href="/logout.php" class="hover:text-blue-200">Logout</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container mx-auto px-4 py-8 flex-grow">
