<?php
/**
 * M Models - Admin Dashboard
 * View and Manage Form Submissions
 */

session_start();

// Simple Password Protection
$admin_pass = 'mmodels2026'; // Default password

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_pass) {
        $_SESSION['loggedin'] = true;
    } else {
        $error = "Invalid password";
    }
}

if (!isset($_SESSION['loggedin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - M Models</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center text-[#C50A76]">M Models Admin</h1>
            <?php if (isset($error)) echo "<p class='text-red-500 text-sm mb-4'>$error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter Password" class="w-full border p-2 rounded mb-4 outline-none focus:border-[#C50A76]" required>
                <button type="submit" class="w-full bg-[#C50A76] text-white p-2 rounded hover:bg-black transition">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$csv_file = '../data/submissions.csv';
$data = [];
if (file_exists($csv_file)) {
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Dashboard - M Models</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #C50A76 !important;
            color: white !important;
            border-color: #C50A76 !important;
        }
        .text-primary { color: #C50A76; }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm border-b p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold text-primary">M Models Dashboard</h1>
            <a href="?logout=1" class="text-sm text-gray-500 hover:text-red-500">Logout</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-bold text-gray-800">Form Submissions</h2>
                <a href="../data/submissions.csv" download class="text-sm bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Download CSV</a>
            </div>

            <div class="overflow-x-auto">
                <table id="submissionsTable" class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <?php 
                            if (!empty($data)) {
                                foreach ($data[0] as $header) {
                                    echo "<th class='px-4 py-3'>$header</th>";
                                }
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (count($data) > 1) {
                            for ($i = 1; $i < count($data); $i++) {
                                echo "<tr class='bg-white border-b hover:bg-gray-50'>";
                                foreach ($data[$i] as $cell) {
                                    echo "<td class='px-4 py-3'>" . htmlspecialchars($cell) . "</td>";
                                }
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#submissionsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
            });
        });
    </script>
</body>
</html>
