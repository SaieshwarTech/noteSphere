<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | NoteSphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card:hover { transform: translateY(-5px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .active-menu { border-left: 4px solid #4f46e5; background: #f8f9fa; }
        .gradient-bg { background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%); }
    </style>
</head>
<body class="h-full bg-gray-50">