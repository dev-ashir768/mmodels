<?php
/**
 * M Models - Models Editor Page
 */
session_start();
require '../includes/db.php';

// Auth Check
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$model = ['name' => '', 'category' => 'Women', 'measurements' => '{}', 'images' => '[]'];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM models WHERE id = ?");
    $stmt->execute([$id]);
    $model = $stmt->fetch();
    if (!$model) {
        header('Location: index.php?tab=models');
        exit;
    }
}

// Handle Save
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "The images you selected exceed the server's maximum upload limit (" . ini_get('post_max_size') . "). Please upload fewer images at once.";
    } else {
        $name = isset($_POST['model_name']) ? trim($_POST['model_name']) : '';
        $category = isset($_POST['model_category']) ? $_POST['model_category'] : 'Women';
    // Process dynamic measurements
    $measurements = [];
    if (isset($_POST['meas_keys']) && isset($_POST['meas_vals'])) {
        for ($i = 0; $i < count($_POST['meas_keys']); $i++) {
            $k = trim($_POST['meas_keys'][$i]);
            $v = trim($_POST['meas_vals'][$i]);
            if (!empty($k) && !empty($v)) {
                $measurements[$k] = $v;
            }
        }
    }
    $measurementsJson = json_encode($measurements);

    // Existing images
    $images = json_decode($model['images'], true) ?: [];
    
    // Check if we want to remove any existing images (if submitted via hidden input or if we just want a simple UI, let's keep it simple: we can allow deleting images by passing an array of kept images)
    if (isset($_POST['kept_images'])) {
        $images = $_POST['kept_images'];
    } elseif ($id > 0) {
        $images = []; // if no kept images posted and it's edit, it means all removed
    }
    if ($id == 0) $images = [];

    // Process new uploaded images
    if (!empty($_FILES['new_images']['name'][0])) {
        $uploadDir = '../assets/models/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['new_images']['name'] as $idx => $fileName) {
            $tmpName = $_FILES['new_images']['tmp_name'][$idx];
            if ($tmpName) {
                // sanitize filename
                $safeName = preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($fileName));
                $targetFile = $uploadDir . time() . '_' . $safeName;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    // save relative path for frontend
                    $images[] = '/assets/models/' . time() . '_' . $safeName;
                }
            }
        }
    }
    
    $imagesJson = json_encode(array_values($images));

    if (empty($name)) {
        $error = 'Model Name cannot be empty.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE models SET name = ?, category = ?, measurements = ?, images = ? WHERE id = ?");
            $stmt->execute([$name, $category, $measurementsJson, $imagesJson, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO models (name, category, measurements, images) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $category, $measurementsJson, $imagesJson]);
        }
        header('Location: index.php?tab=models');
        exit;
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Edit' : 'Create'; ?> Model | M Models Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; }
        .bg-primary { background-color: #C50A76; }
        .text-primary { color: #C50A76; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-10">
    <div class="max-w-5xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-2 text-xs font-bold uppercase tracking-widest text-gray-400 mb-8">
            <a href="index.php?tab=models" class="hover:text-primary transition">Models Management</a>
            <i class="fas fa-chevron-right text-[8px]"></i>
            <span class="text-gray-900"><?php echo $id > 0 ? 'Edit Model' : 'Create New Model'; ?></span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $id > 0 ? 'Edit' : 'Create'; ?> Model</h1>
                <p class="text-gray-500 text-sm mt-1">Add or update model details and portfolio images</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="index.php?tab=models" class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-2xl text-sm font-semibold hover:bg-gray-50 transition shadow-sm">Cancel</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3 animate-pulse">
                <i class="fas fa-exclamation-circle"></i>
                <span class="text-sm font-bold uppercase tracking-widest"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="modelForm" class="space-y-8">
            <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-8">
                
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 ml-1">Model Name *</label>
                        <input type="text" name="model_name" value="<?php echo htmlspecialchars($model['name']); ?>" class="w-full bg-gray-50 border border-gray-100 p-5 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm font-semibold" placeholder="e.g. Alanna" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 ml-1">Category *</label>
                        <select name="model_category" class="w-full bg-gray-50 border border-gray-100 p-5 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm font-semibold appearance-none">
                            <option value="Women" <?php echo $model['category'] == 'Women' ? 'selected' : ''; ?>>Women</option>
                            <option value="Men" <?php echo $model['category'] == 'Men' ? 'selected' : ''; ?>>Men</option>
                            <option value="Kids" <?php echo $model['category'] == 'Kids' ? 'selected' : ''; ?>>Kids</option>
                        </select>
                    </div>
                </div>

                <!-- Measurements -->
                <div class="pt-6 border-t border-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 ml-1">Measurements & Details</label>
                        <button type="button" onclick="addMeasurement()" class="text-[10px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full font-bold uppercase tracking-widest transition"><i class="fas fa-plus mr-1"></i> Add Field</button>
                    </div>
                    <div id="measurements_container" class="space-y-3">
                        <?php 
                        $meas = json_decode($model['measurements'], true);
                        if ($meas && count($meas) > 0) {
                            foreach ($meas as $k => $v) {
                                echo '<div class="flex gap-4 items-center meas-row">
                                        <input type="text" name="meas_keys[]" value="'.htmlspecialchars($k).'" placeholder="Label (e.g. Height)" class="flex-1 bg-gray-50 border border-gray-100 p-4 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-primary/20">
                                        <input type="text" name="meas_vals[]" value="'.htmlspecialchars($v).'" placeholder="Value (e.g. 5ft 4in)" class="flex-1 bg-gray-50 border border-gray-100 p-4 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-primary/20">
                                        <button type="button" onclick="this.parentElement.remove()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition"><i class="fas fa-times"></i></button>
                                      </div>';
                            }
                        } else {
                            // Default empty row
                            echo '<div class="flex gap-4 items-center meas-row">
                                    <input type="text" name="meas_keys[]" placeholder="Label (e.g. Height)" class="flex-1 bg-gray-50 border border-gray-100 p-4 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-primary/20">
                                    <input type="text" name="meas_vals[]" placeholder="Value (e.g. 5ft 4in)" class="flex-1 bg-gray-50 border border-gray-100 p-4 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-primary/20">
                                    <button type="button" onclick="this.parentElement.remove()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition"><i class="fas fa-times"></i></button>
                                  </div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Images -->
                <div class="pt-6 border-t border-gray-50">
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-4 ml-1">Portfolio Images</label>
                    
                    <!-- Existing Images -->
                    <div id="existing_images" class="flex flex-wrap gap-4 mb-4">
                        <?php 
                        $images = json_decode($model['images'], true);
                        if ($images) {
                            foreach ($images as $idx => $img) {
                                echo '<div class="relative group w-24 h-32 rounded-xl overflow-hidden border border-gray-200">
                                        <img src="'.htmlspecialchars($img).'" class="w-full h-full object-cover">
                                        <input type="hidden" name="kept_images[]" value="'.htmlspecialchars($img).'">
                                        <button type="button" onclick="this.parentElement.remove()" class="absolute inset-0 bg-red-500/80 text-white opacity-0 group-hover:opacity-100 flex items-center justify-center transition backdrop-blur-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                      </div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- New Upload Previews -->
                    <div id="new_images_preview" class="flex flex-wrap gap-4 mb-4 hidden"></div>

                    <!-- Upload New -->
                    <div class="relative w-full">
                        <input type="file" name="new_images[]" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewFiles(this)">
                        <div class="w-full border-2 border-dashed border-gray-200 rounded-2xl p-8 flex flex-col items-center justify-center text-gray-400 bg-gray-50/50 hover:bg-gray-50 transition">
                            <i class="fas fa-cloud-upload-alt text-3xl mb-3"></i>
                            <span class="text-sm font-semibold" id="file_count_text">Click or drag multiple images to upload</span>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-6 border-t border-gray-50 flex justify-end">
                    <button type="submit" class="bg-primary text-white px-12 py-5 rounded-2xl font-bold uppercase tracking-widest text-xs hover:bg-black transition shadow-xl shadow-[#C50A76]/20 transform hover:-translate-y-1">
                        <i class="fas fa-save mr-2"></i> <?php echo $id > 0 ? 'Update Model' : 'Save Model'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function addMeasurement() {
            const container = document.getElementById('measurements_container');
            const row = document.createElement('div');
            row.className = 'flex gap-4 items-center meas-row mt-3';
            row.innerHTML = `
                <input type="text" name="meas_keys[]" placeholder="Label (e.g. Chest)" class="flex-1 bg-gray-50 border border-gray-100 p-4 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-primary/20">
                <input type="text" name="meas_vals[]" placeholder="Value" class="flex-1 bg-gray-50 border border-gray-100 p-4 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-primary/20">
                <button type="button" onclick="this.parentElement.remove()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(row);
        }

        function compressImage(file, maxWidth, maxHeight, quality) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = event => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        let width = img.width;
                        let height = img.height;
                        if (width > height) {
                            if (width > maxWidth) {
                                height = Math.round((height * maxWidth) / width);
                                width = maxWidth;
                            }
                        } else {
                            if (height > maxHeight) {
                                width = Math.round((width * maxHeight) / height);
                                height = maxHeight;
                            }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                        }, 'image/jpeg', quality);
                    };
                };
                reader.onerror = error => reject(error);
            });
        }

        async function previewFiles(input) {
            const text = document.getElementById('file_count_text');
            const previewContainer = document.getElementById('new_images_preview');
            previewContainer.innerHTML = ''; // Clear old previews
            
            if (input.files && input.files.length > 0) {
                text.innerHTML = '<span class="text-primary font-bold"><i class="fas fa-spinner fa-spin mr-2"></i>Compressing large images...</span>';
                
                try {
                    const dt = new DataTransfer();
                    for (let i = 0; i < input.files.length; i++) {
                        let file = input.files[i];
                        if (file.type.match(/image.*/) && file.size > 2 * 1024 * 1024) { // Compress if > 2MB
                            file = await compressImage(file, 1920, 1920, 0.85);
                        }
                        dt.items.add(file);
                    }
                    input.files = dt.files;
                } catch(e) {
                    console.error("Compression failed", e);
                }

                // Validation logic
                let totalSize = 0;
                const maxSingleFileSize = 5 * 1024 * 1024; // 5MB
                const maxTotalSize = 8 * 1024 * 1024; // 8MB safe limit
                let hasError = false;

                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    totalSize += file.size;
                    if (file.size > maxSingleFileSize) {
                        alert(`Image "${file.name}" is too large! Each image must be under 5MB.`);
                        hasError = true;
                        break;
                    }
                }

                if (!hasError && totalSize > maxTotalSize) {
                    alert(`The total size of selected images exceeds the safe 8MB server limit. Please select fewer images at once.`);
                    hasError = true;
                }

                if (hasError) {
                    input.value = ''; // Clear selection
                    text.innerHTML = 'Click or drag multiple images to upload';
                    previewContainer.classList.add('hidden');
                    return;
                }

                text.innerHTML = `<span class="text-primary font-bold">${input.files.length} new file(s) selected</span> - Ready to upload on save`;
                previewContainer.classList.remove('hidden');
                
                // Show actual thumbnails for each file
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative w-24 h-32 rounded-xl overflow-hidden border-2 border-primary/50 opacity-80';
                        div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                                         <div class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[8px] text-center p-1 truncate">${file.name}</div>`;
                        previewContainer.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            } else {
                text.innerHTML = 'Click or drag multiple images to upload';
                previewContainer.classList.add('hidden');
            }
        }
    </script>
</body>
</html>