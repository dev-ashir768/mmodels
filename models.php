<?php
// Load model data
$csv_file = "data/submissions_become_a_model.csv";
$categories = ['men' => [], 'women' => [], 'kids' => []];

if (file_exists($csv_file)) {
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 10000, ",");
        while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
            $m = array_combine($headers, $row);
            $age = (int)($m['age'] ?? 0);
            $gender = strtolower($m['gender'] ?? '');

            if ($age > 0 && $age <= 12) {
                $categories['kids'][] = $m;
            } elseif ($gender == 'male') {
                $categories['men'][] = $m;
            } else {
                $categories['women'][] = $m;
            }
        }
        fclose($handle);
    }
}
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Models Portfolio | M Models</title>
    <link rel="icon" href="/assets/others/logo.png" type="image/png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ["Inter", "sans-serif"],
              serif: ["Playfair Display", "serif"],
            },
            colors: { primary: "#C50A76" },
          },
        },
      };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .model-card {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            aspect-ratio: 3/4;
            background: #f8fafc;
            cursor: pointer;
        }
        .model-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 60%);
            opacity: 0.6;
            transition: all 0.5s ease;
        }
        .model-card:hover .model-overlay {
            opacity: 1;
            background: linear-gradient(to top, #C50A76 0%, rgba(0,0,0,0.4) 100%);
        }
        .model-info-summary {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            color: white;
        }
        
        /* Modal Styles */
        #modelModal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .modal-content {
            background: white;
            width: 100%;
            max-width: 900px;
            border-radius: 2rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            animation: modalFadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @media (min-width: 768px) {
            .modal-content { flex-direction: row; }
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-img-container {
            width: 100%;
            height: 400px;
        }
        @media (min-width: 768px) {
            .modal-img-container { width: 45%; height: auto; }
        }
        .modal-body {
            padding: 2.5rem;
            flex: 1;
        }
        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            z-index: 10;
            transition: color 0.3s;
        }
        .close-modal:hover { color: #C50A76; }

        .measurement-grid {
            display: grid;
            grid-template-cols: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .m-item {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.5rem;
        }
        .m-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 700;
            color: #94a3b8;
        }
        .m-val {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }
    </style>
  </head>
  <body class="antialiased overflow-x-hidden selection:bg-primary selection:text-white">
    <!-- Navbar -->
    <nav id="navbar" class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-24">
          <div class="flex-shrink-0 flex items-center">
            <a href="index.html" class="block">
                <img src="/assets/others/logo.png" alt="M Models" class="h-10 md:h-12 w-auto object-contain" />
            </a>
          </div>
          <div class="hidden xl:flex items-center space-x-6 text-[10px] font-bold uppercase tracking-[0.2em]">
            <a href="home.html" class="hover:text-primary transition">Home</a>
            <a href="models.php" class="text-primary">Models</a>
            <a href="become-a-model.html" class="hover:text-primary transition">Become a Model</a>
            <a href="hire-a-model.html" class="hover:text-primary transition">Hire a Model</a>
            <a href="contact.html" class="hover:text-primary transition">Contact Us</a>
          </div>
          <div class="xl:hidden flex items-center">
            <button id="mobile-btn" class="text-gray-600 hover:text-primary">
              <i class="fas fa-bars text-2xl"></i>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Header -->
    <header class="pt-48 pb-20 bg-white">
      <div class="text-center max-w-4xl mx-auto px-4">
        <h1 class="text-primary font-bold uppercase tracking-[0.3em] text-xs mb-4">Portfolio</h1>
        <h2 class="font-serif text-5xl md:text-7xl font-bold text-gray-900 mb-8 italic">Our Talent</h2>
        <div class="h-1.5 w-20 bg-primary mx-auto mb-12 rounded-full"></div>

        <!-- Filter Category -->
        <div class="flex flex-wrap justify-center gap-4 md:gap-10 text-[11px] font-bold uppercase tracking-[0.2em]" id="portfolio-filters">
          <button data-target="women" class="filter-btn text-primary border-b-2 border-primary pb-2 px-4 transition-all">Women</button>
          <button data-target="men" class="filter-btn text-gray-400 hover:text-gray-900 border-b-2 border-transparent pb-2 px-4 transition-all">Men</button>
          <button data-target="kids" class="filter-btn text-gray-400 hover:text-gray-900 border-b-2 border-transparent pb-2 px-4 transition-all">Kids</button>
        </div>
      </div>
    </header>

    <!-- Modal -->
    <div id="modelModal" class="hidden">
        <div class="modal-content">
            <span class="close-modal"><i class="fas fa-times"></i></span>
            <div class="modal-img-container">
                <img src="" id="modalImg" class="w-full h-full object-cover">
            </div>
            <div class="modal-body">
                <h2 id="modalName" class="font-serif text-4xl font-bold text-gray-900 mb-2 italic"></h2>
                <p id="modalCategory" class="text-primary font-bold uppercase tracking-widest text-[10px] mb-10"></p>
                
                <div class="measurement-grid" id="modalMeasurements">
                    <!-- Measurements injected here -->
                </div>

                <div class="mt-12 pt-8 border-t border-gray-100">
                    <a href="hire-a-model.html" class="bg-black text-white px-8 py-4 uppercase text-[10px] font-bold tracking-[0.2em] hover:bg-primary transition-colors rounded-xl inline-block w-full text-center md:w-auto">Book This Model</a>
                </div>
            </div>
        </div>
    </div>

    <section class="py-12 bg-white min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php foreach (['women', 'men', 'kids'] as $idx => $cat): ?>
        <div id="<?php echo $cat; ?>" class="model-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 <?php echo $idx > 0 ? 'hidden' : ''; ?>">
            <?php if (empty($categories[$cat])): ?>
                <div class="col-span-full py-20 text-center">
                    <p class="text-gray-300 font-serif italic text-2xl">Coming Soon...</p>
                </div>
            <?php else: ?>
                <?php foreach ($categories[$cat] as $m): 
                    $model_json = htmlspecialchars(json_encode([
                        'name' => ($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''),
                        'category' => ucfirst($cat),
                        'measurements' => [
                            'Height' => $m['height'] ?? '',
                            'Chest' => $m['chest'] ?? '',
                            'Waist' => $m['waist'] ?? '',
                            'Hips' => $m['hips'] ?? '',
                            'Shoe Size' => $m['shoe_size'] ?? '',
                            'Hair Color' => $m['hair_color'] ?? '',
                            'Eye Color' => $m['eye_color'] ?? '',
                            'Sizing' => $m['dress_tshirt_size'] ?? ''
                        ]
                    ]));
                ?>
                    <div class="model-card group shadow-2xl shadow-black/5" 
                         onclick='openModel(<?php echo $model_json; ?>, this.querySelector("img")?.src || "")'>
                        <?php 
                        $img = "";
                        foreach($m as $key => $val) {
                            if (strpos(strtolower($key), 'photo') !== false && !empty($val) && strpos($val, 'data:image') !== false) {
                                $img = $val;
                                break;
                            }
                        }
                        if ($img): ?>
                            <img src="<?php echo $img; ?>" alt="Model" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-50 flex items-center justify-center">
                                <i class="fas fa-user-circle text-gray-100 text-9xl"></i>
                            </div>
                        <?php endif; ?>

                        <div class="model-overlay"></div>
                        
                        <div class="model-info-summary">
                            <h3 class="font-serif text-2xl font-bold text-white italic">
                                <?php echo htmlspecialchars($m['first_name'] ?? ''); ?>
                            </h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black py-20">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <a href="index.html" class="inline-block mb-10">
            <img src="/assets/others/logo.png" alt="M Models" class="h-12 w-auto brightness-0 invert mx-auto" />
        </a>
        <div class="flex justify-center space-x-8 mb-10 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">
            <a href="privacy-policy.html" class="hover:text-white transition">Privacy</a>
            <a href="contact.html" class="hover:text-white transition">Contact</a>
            <a href="become-a-model.html" class="hover:text-white transition">Join Us</a>
        </div>
        <p class="text-gray-600 text-[10px] uppercase tracking-widest font-bold">
          © 2026 M Models Global. Built with Excellence.
        </p>
      </div>
    </footer>

    <script>
      function openModel(data, imgSrc) {
          $("#modalImg").attr("src", imgSrc);
          $("#modalName").text(data.name);
          $("#modalCategory").text(data.category);
          
          let mHtml = "";
          for (const [label, val] of Object.entries(data.measurements)) {
              if (val) {
                  mHtml += `
                    <div class="m-item">
                        <p class="m-label">${label}</p>
                        <p class="m-val">${val}</p>
                    </div>
                  `;
              }
          }
          $("#modalMeasurements").html(mHtml);
          $("#modelModal").removeClass("hidden").addClass("flex");
          $("body").addClass("overflow-hidden");
      }

      function closeModel() {
          $("#modelModal").addClass("hidden").removeClass("flex");
          $("body").removeClass("overflow-hidden");
      }

      $(document).ready(function () {
        $(".close-modal, #modelModal").click(function(e) {
            if (e.target === this || $(e.target).closest('.close-modal').length) {
                closeModel();
            }
        });

        $(".filter-btn").click(function (e) {
          e.preventDefault();
          $(".filter-btn")
            .removeClass("text-primary border-primary")
            .addClass("text-gray-400 hover:text-gray-900 border-transparent");
          $(this)
            .addClass("text-primary border-primary")
            .removeClass("text-gray-400 hover:text-gray-900 border-transparent");

          const target = $(this).data("target");
          $(".model-grid").addClass("hidden");
          $("#" + target).removeClass("hidden").css({ opacity: 0 }).animate({ opacity: 1 }, 500);
        });

        // ESC key to close modal
        $(document).keydown(function(e) {
            if (e.keyCode === 27) closeModel();
        });
      });
    </script>
  </body>
</html>>
