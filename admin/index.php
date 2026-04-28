<?php
/**
 * M Models - Premium Admin Dashboard
 * High-end UI for Managing Form Submissions
 */

session_start();

// Configuration
$admin_pass = 'mmodels2026';
$csv_file = '../data/submissions.csv';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_pass) {
        $_SESSION['loggedin'] = true;
    } else {
        $error = "Access Denied. Incorrect password.";
    }
}

// Login Screen UI
if (!isset($_SESSION['loggedin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login | M Models</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }

            .bg-mesh {
                background-color: #ffffff;
                background-image: radial-gradient(at 0% 0%, hsla(327, 87%, 53%, 0.15) 0, transparent 50%),
                    radial-gradient(at 50% 0%, hsla(225, 39%, 30%, 0.05) 0, transparent 50%);
            }
        </style>
    </head>

    <body class="bg-mesh min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <img src="/assets/others/logo.png" alt="M Models" class="h-16 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Agency Dashboard</h1>
                <p class="text-gray-500 mt-2 text-sm">Secure access for authorized personnel only</p>
            </div>

            <div
                class="bg-white/80 backdrop-blur-xl p-10 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-white">
                <?php if (isset($error))
                    echo "<div class='bg-red-50 text-red-600 p-4 rounded-xl text-xs font-semibold mb-6 border border-red-100'>$error</div>"; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label
                            class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Password</label>
                        <input type="password" name="password"
                            class="w-full bg-gray-50 border-0 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-[#C50A76]/20 transition-all text-sm"
                            placeholder="••••••••" required autofocus>
                    </div>
                    <button type="submit"
                        class="w-full bg-[#C50A76] text-white py-4 rounded-2xl font-bold uppercase tracking-widest text-xs hover:bg-black transition-all duration-300 shadow-lg shadow-[#C50A76]/20 transform hover:-translate-y-1">
                        Authenticate
                    </button>
                </form>
            </div>
            <p class="text-center text-gray-400 text-[10px] mt-8 uppercase tracking-[0.2em]">© 2026 M Models & Talent Agency
            </p>
        </div>
    </body>

    </html>
    <?php
    exit;
}

if (isset($_GET['delete'])) {
    $delete_idx = (int) $_GET['delete'];
    $all_data = [];
    if (file_exists($csv_file)) {
        if (($handle = fopen($csv_file, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
                $all_data[] = $row;
            }
            fclose($handle);
        }

        // Remove the entry (adjusted for newest-first display in UI vs file index)
        // Note: $data in UI is array_reverse of $all_data (minus headers)
        // File index = count(all_data) - 1 - ui_index (if headers exist)
        // Better: just use the timestamp to match.
    }
}

// Forms configuration
$forms = [
    'become_a_model' => 'Become a Model',
    'hire_a_model' => 'Hire a Model',
    'application' => 'Applications',
    'contact' => 'Contact Us',
];

// Improved Deletion Logic using Timestamp and Form Type
if (isset($_POST['delete_timestamp']) && isset($_POST['delete_form_type'])) {
    $ts = $_POST['delete_timestamp'];
    $ft = $_POST['delete_form_type'];
    if (isset($forms[$ft])) {
        $del_csv_file = "../data/submissions_{$ft}.csv";
        $new_data = [];
        if (file_exists($del_csv_file)) {
            if (($handle = fopen($del_csv_file, "r")) !== FALSE) {
                $header = fgetcsv($handle, 10000, ",");
                $new_data[] = $header;
                while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    if ($row[0] !== $ts) {
                        $new_data[] = $row;
                    }
                }
                fclose($handle);

                // Rewrite CSV
                $handle = fopen($del_csv_file, "w");
                foreach ($new_data as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }
        }
    }
    header('Location: index.php');
    exit;
}

// Data Processing
$all_data = [];
$total_submissions = 0;
$today_submissions = 0;
$current_date = date('Y-m-d');

foreach ($forms as $key => $title) {
    $csv_file = "../data/submissions_{$key}.csv";
    $data = [];
    $headers = [];
    if (file_exists($csv_file)) {
        if (($handle = fopen($csv_file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 10000, ","); // Keep headers separate
            while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
                $data[] = $row;
                $total_submissions++;
                if (strpos($row[0], $current_date) === 0)
                    $today_submissions++;
            }
            fclose($handle);
        }
        $data = array_reverse($data); // Newest first
    }
    $all_data[$key] = [
        'title' => $title,
        'headers' => $headers,
        'data' => $data,
        'file' => $csv_file
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions | M Models Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
        }

        .sidebar {
            background-color: #0F172A;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #C50A76 !important;
            color: white !important;
            border-color: #C50A76 !important;
            border-radius: 12px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 8px 16px;
            outline: none;
            margin-bottom: 20px;
        }

        table.dataTable thead th {
            border-bottom: 1px solid #E2E8F0 !important;
        }

        .text-primary {
            color: #C50A76;
        }

        .bg-primary {
            background-color: #C50A76;
        }
    </style>
</head>

<body class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="sidebar w-64 fixed inset-y-0 left-0 z-50 hidden lg:flex flex-col">
        <div class="p-8">
            <img src="/assets/others/logo.png" alt="Logo" class="h-10 brightness-0 invert opacity-90">
        </div>

        <nav class="flex-1 px-4 space-y-2 mt-4 overflow-y-auto">
            <div class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-gray-500 opacity-50">Submissions</div>
            <?php foreach ($all_data as $key => $dataset): ?>
            <a href="javascript:void(0)" onclick="switchTab('<?php echo $key; ?>')" id="side_btn_<?php echo $key; ?>" 
                class="side-btn flex items-center space-x-3 px-4 py-3 text-gray-400 hover:text-white hover:bg-white/5 rounded-xl transition">
                <i class="fas <?php 
                    echo ($key == 'become_a_model') ? 'fa-user-plus' : 
                         (($key == 'hire_a_model') ? 'fa-user-tie' : 
                         (($key == 'application') ? 'fa-file-invoice' : 'fa-envelope')); 
                ?> w-5"></i>
                <span class="text-sm font-semibold"><?php echo $dataset['title']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-6 border-t border-white/10">
            <a href="?logout=1"
                class="flex items-center space-x-3 px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-xl transition">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="text-sm font-semibold">Log Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0 lg:ml-64 p-6 md:p-10 flex flex-col h-screen overflow-y-auto">
        <!-- Top Header -->
        <header class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4 shrink-0">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Form Submissions</h1>
                <p class="text-gray-500 text-sm mt-1">Review and manage your agency applications</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Date Filter -->
                <div class="relative hidden md:block">
                    <input type="text" id="dateFilter" 
                        class="bg-white border border-gray-200 text-gray-700 px-10 py-3 rounded-2xl text-sm font-semibold shadow-sm focus:ring-2 focus:ring-primary/20 outline-none w-64"
                        placeholder="Filter by date range...">
                    <i class="fas fa-calendar-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>

                <!-- Export Dropdown -->
                <div class="relative group">
                    <button class="flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-2xl text-sm font-semibold hover:bg-gray-50 transition shadow-sm">
                        <i class="fas fa-download text-xs"></i> Export
                        <i class="fas fa-chevron-down text-[10px] ml-1 opacity-50"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-[100] overflow-hidden">
                        <div class="p-2">
                            <div class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">Choose Form Type</div>
                            <?php foreach ($all_data as $key => $dataset): ?>
                            <a href="<?php echo $dataset['file']; ?>" download 
                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-600 hover:bg-primary/5 hover:text-primary rounded-xl transition">
                                <span><?php echo $dataset['title']; ?></span>
                                <i class="fas fa-file-csv opacity-30 text-xs"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="h-12 w-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-lg shadow-[#C50A76]/20">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 shrink-0">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Entries</span>
                </div>
                <div class="text-3xl font-bold text-gray-900"><?php echo $total_submissions; ?></div>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">New Today</span>
                </div>
                <div class="text-3xl font-bold text-gray-900"><?php echo $today_submissions; ?></div>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">System Status</span>
                </div>
                <div class="text-sm font-bold text-green-500 uppercase tracking-widest">Active & Secure</div>
            </div>
        </div>

        <div class="flex gap-4 mb-6 border-b border-gray-200 overflow-x-auto pb-2 shrink-0">
            <?php foreach ($all_data as $key => $dataset): ?>
                <button onclick="switchTab('<?php echo $key; ?>')" id="tab_btn_<?php echo $key; ?>" class="tab-btn px-6 py-3 font-semibold text-sm rounded-t-xl transition-all border-b-2 border-transparent text-gray-500 hover:text-primary whitespace-nowrap">
                    <?php echo $dataset['title']; ?> <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs"><?php echo count($dataset['data']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex-1 min-h-0 flex flex-col">
            <div class="p-8 overflow-y-auto flex-1 relative">
                <?php foreach ($all_data as $key => $dataset): ?>
                <div id="tab_content_<?php echo $key; ?>" class="tab-content hidden h-full flex flex-col">
                    <div class="overflow-x-auto">
                        <table id="submissionsTable_<?php echo $key; ?>" class="w-full text-sm min-w-[1500px]">
                            <thead class="text-gray-400 uppercase text-[10px] font-bold tracking-[0.2em] bg-gray-50/50">
                                <tr>
                                    <?php
                                    if (!empty($dataset['headers'])) {
                                        foreach ($dataset['headers'] as $header) {
                                            $label = $header;
                                            if (strpos(strtolower($header), 'photo') !== false) {
                                                $label = str_replace('photo', 'Image ', strtolower($header));
                                            } else {
                                                // Convert snake_case to Title Case
                                                $label = ucwords(str_replace('_', ' ', $header));
                                            }
                                            echo "<th class='px-6 py-5 text-left'>$label</th>";
                                        }
                                        echo "<th class='px-6 py-5 text-left'>Actions</th>";
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php
                                if (!empty($dataset['data'])) {
                                    foreach ($dataset['data'] as $row) {
                                        $row_ts = $row[0]; // First col is timestamp
                                        echo "<tr class='hover:bg-gray-50/50 transition-colors'>";
                                        foreach ($row as $cell) {
                                            if (is_string($cell) && strpos($cell, 'data:') === 0 && strpos($cell, ';base64,') !== false) {
                                                // Handle File Cell
                                                $is_image = strpos($cell, 'data:image/') === 0;
                                                $preview = $is_image
                                                    ? "<img src='$cell' class='w-full h-full object-cover rounded-lg border border-gray-100 shadow-sm transition-all group-hover:ring-2 group-hover:ring-primary/30' alt='Photo'>"
                                                    : "<div class='w-full h-full rounded-lg border border-gray-100 bg-gray-50 flex items-center justify-center text-gray-400 group-hover:ring-2 group-hover:ring-primary/30'><i class='fas fa-file-alt text-xl'></i></div>";

                                                echo "<td class='px-6 py-4'>
                                                        <div class='flex flex-col items-center gap-1 min-w-[70px]'>
                                                            <a href='$cell' target='_blank' class='w-12 h-12 block group relative'>
                                                                $preview
                                                                <div class='absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center text-white text-[8px]'>
                                                                    <i class='fas " . ($is_image ? "fa-eye" : "fa-download") . "'></i>
                                                                </div>
                                                            </a>
                                                            <div class='flex gap-2 mt-1'>
                                                                <a href='$cell' download='mmodels_file' class='text-[9px] text-gray-400 hover:text-green-500 font-bold tracking-tighter uppercase'>Save</a>
                                                            </div>
                                                        </div>
                                                      </td>";
                                            } else {
                                                $display = htmlspecialchars($cell);
                                                if ($cell == 'become_a_model') {
                                                    $display = "<span class='px-3 py-1 bg-pink-50 text-[#C50A76] rounded-full text-[10px] font-bold uppercase tracking-tighter'>Model App</span>";
                                                } elseif ($cell == 'hire_a_model') {
                                                    $display = "<span class='px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-tighter'>Hire Model</span>";
                                                } elseif ($cell == 'application') {
                                                    $display = "<span class='px-3 py-1 bg-purple-50 text-purple-600 rounded-full text-[10px] font-bold uppercase tracking-tighter'>Application</span>";
                                                } elseif ($cell == 'contact') {
                                                    $display = "<span class='px-3 py-1 bg-green-50 text-green-600 rounded-full text-[10px] font-bold uppercase tracking-tighter'>Contact</span>";
                                                } elseif (strpos($display, 'No photo') !== false || strpos($display, 'No file') !== false || strpos($display, 'Not sent') !== false) {
                                                    $display = "<span class='text-gray-300 italic text-[10px]'>Empty</span>";
                                                }
                                                echo "<td class='px-6 py-4 font-medium text-gray-700 whitespace-nowrap'>$display</td>";
                                            }
                                        }
                                        // Add Actions Column
                                        echo "<td class='px-6 py-4 text-center'>
                                                <form method='POST' id='deleteForm_{$key}_$row_ts' class='inline-block'>
                                                    <input type='hidden' name='delete_timestamp' value='$row_ts'>
                                                    <input type='hidden' name='delete_form_type' value='$key'>
                                                    <button type='button' onclick='confirmDelete(\"deleteForm_{$key}_$row_ts\")' class='w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all shadow-sm'>
                                                        <i class='fas fa-trash-alt text-xs'></i>
                                                    </button>
                                                </form>
                                              </td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script>
        function confirmDelete(formId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            })
        }

        function switchTab(tabId) {
            $('.tab-content').addClass('hidden');
            $('#tab_content_' + tabId).removeClass('hidden');

            // Update Tab Buttons
            $('.tab-btn').removeClass('text-primary border-primary bg-primary/5').addClass('text-gray-500 border-transparent');
            $('#tab_btn_' + tabId).removeClass('text-gray-500 border-transparent').addClass('text-primary border-primary bg-primary/5');

            // Update Sidebar Buttons
            $('.side-btn').removeClass('bg-white/10 text-white').addClass('text-gray-400 hover:text-white hover:bg-white/5');
            $('#side_btn_' + tabId).addClass('bg-white/10 text-white').removeClass('text-gray-400 hover:text-white hover:bg-white/5');

            // Adjust datatables column width since they might be hidden initially
            setTimeout(function () {
                $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
            }, 10);
        }

        $(document).ready(function () {
            let tables = {};

            // Custom DataTable filtering for Date Range
            $.fn.dataTable.ext.search.push(
                function (settings, data, dataIndex) {
                    var range = $('#dateFilter').val();
                    if (!range || !range.includes(' to ')) return true;

                    var dates = range.split(' to ');
                    var min = moment(dates[0], 'Y-m-d');
                    var max = moment(dates[1], 'Y-m-d');
                    var dateStr = data[0].split(' ')[0]; // Assuming first column is "YYYY-MM-DD HH:MM:SS"
                    var current = moment(dateStr, 'Y-m-d');

                    if (
                        (min === null || current.isSameOrAfter(min)) &&
                        (max === null || current.isSameOrBefore(max))
                    ) {
                        return true;
                    }
                    return false;
                }
            );

            // Initialize Flatpickr
            flatpickr("#dateFilter", {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        Object.values(tables).forEach(t => t.draw());
                    }
                }
            });

            <?php foreach ($all_data as $key => $dataset): ?>
                if ($('#submissionsTable_<?php echo $key; ?> tbody tr').length > 0) {
                    tables['<?php echo $key; ?>'] = $('#submissionsTable_<?php echo $key; ?>').DataTable({
                        order: [[0, 'desc']],
                        pageLength: 10,
                        bAutoWidth: false,
                        columnDefs: [
                            { targets: '_all', defaultContent: '' }
                        ],
                        dom: '<"flex flex-col md:flex-row justify-between mb-4"f>rt<"flex flex-col md:flex-row justify-between mt-6 items-center"ip>',
                        language: {
                            search: "",
                            searchPlaceholder: "Search <?php echo strtolower($dataset['title']); ?>...",
                            paginate: {
                                next: '<i class="fas fa-chevron-right text-xs"></i>',
                                previous: '<i class="fas fa-chevron-left text-xs"></i>'
                            }
                        }
                    });
                }
            <?php endforeach; ?>

            // Open 'Become a Model' tab by default
            switchTab('become_a_model');
        });
    </script>
</body>

</html>