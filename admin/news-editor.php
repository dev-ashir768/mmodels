<?php
/**
 * M Models - News Editor Page
 */
session_start();
require '../includes/db.php';

// Auth Check
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = ['title' => '', 'content' => '', 'category' => 'General', 'news_date' => date('Y-m-d')];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    if (!$article) {
        header('Location: index.php?tab=news');
        exit;
    }
}

// Handle Save
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['news_title']);
    $content = trim($_POST['news_content']);
    $category = $_POST['news_category'];
    $date = $_POST['news_date'];

    if (empty($title) || empty($content) || strip_tags($content) == '') {
        $error = 'Title and Content cannot be empty.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ?, category = ?, news_date = ? WHERE id = ?");
            $stmt->execute([$title, $content, $category, $date, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO news (title, content, category, news_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $category, $date]);
        }
        header('Location: index.php?tab=news');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Edit' : 'Create'; ?> News | M Models Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: '#news_content',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            height: 500,
            branding: false,
            promotion: false,
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
        });
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
        }

        .bg-primary {
            background-color: #C50A76;
        }

        .text-primary {
            color: #C50A76;
        }

        .tox-tinymce {
            border-radius: 1.5rem !important;
            border: 1px solid #E2E8F0 !important;
            overflow: hidden;
        }
    </style>
</head>

<body class="min-h-screen p-4 md:p-10">

    <div class="max-w-5xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-2 text-xs font-bold uppercase tracking-widest text-gray-400 mb-8">
            <a href="index.php?tab=news" class="hover:text-primary transition">News Management</a>
            <i class="fas fa-chevron-right text-[8px]"></i>
            <span class="text-gray-900"><?php echo $id > 0 ? 'Edit Article' : 'Create New Article'; ?></span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $id > 0 ? 'Edit' : 'Create'; ?> News Article
                </h1>
                <p class="text-gray-500 text-sm mt-1">Compose and publish news to your agency feed</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="index.php?tab=news"
                    class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-2xl text-sm font-semibold hover:bg-gray-50 transition shadow-sm">
                    Cancel
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-50 border border-red-100 text-red-600 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3 animate-pulse">
                <i class="fas fa-exclamation-circle"></i>
                <span class="text-sm font-bold uppercase tracking-widest"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="newsForm" class="space-y-8" onsubmit="return validateForm()">
            <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 ml-1">Article
                            Title</label>
                        <input type="text" name="news_title" value="<?php echo htmlspecialchars($article['title']); ?>"
                            class="w-full bg-gray-50 border border-gray-100 p-5 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm font-semibold"
                            placeholder="Enter a compelling title..." required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 ml-1">Category</label>
                            <select name="news_category"
                                class="w-full bg-gray-50 border border-gray-100 p-5 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm font-semibold appearance-none">
                                <option value="General" <?php echo $article['category'] == 'General' ? 'selected' : ''; ?>>General</option>
                                <option value="Fashion Week" <?php echo $article['category'] == 'Fashion Week' ? 'selected' : ''; ?>>Fashion Week</option>
                                <option value="Model Spotlight" <?php echo $article['category'] == 'Model Spotlight' ? 'selected' : ''; ?>>Model Spotlight</option>
                                <option value="New Office" <?php echo $article['category'] == 'New Office' ? 'selected' : ''; ?>>New Office</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 ml-1">Publish
                                Date</label>
                            <input type="text" name="news_date" id="news_date_picker"
                                value="<?php echo $article['news_date']; ?>"
                                class="w-full bg-gray-50 border border-gray-100 p-5 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm font-semibold"
                                required>
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 ml-1">Content</label>
                    <textarea name="news_content" id="news_content"
                        required><?php echo $article['content']; ?></textarea>
                </div>

                <div class="pt-6 border-t border-gray-50 flex justify-end">
                    <button type="submit"
                        class="bg-primary text-white px-12 py-5 rounded-2xl font-bold uppercase tracking-widest text-xs hover:bg-black transition shadow-xl shadow-[#C50A76]/20 transform hover:-translate-y-1">
                        <i class="fas fa-paper-plane mr-2"></i>
                        <?php echo $id > 0 ? 'Update Article' : 'Publish Article'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        flatpickr("#news_date_picker", { dateFormat: "Y-m-d" });

        function validateForm() {
            const title = document.getElementsByName('news_title')[0].value.trim();
            const content = tinymce.get('news_content').getContent().trim();

            if (title === '' || content === '' || content === '<p></p>') {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Information',
                    text: 'Please provide both a title and some content for your article.',
                    confirmButtonColor: '#C50A76'
                });
                return false;
            }
            return true;
        }
    </script>
</body>

</html>