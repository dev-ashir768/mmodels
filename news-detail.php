<?php
require 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: news.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: news.php');
    exit;
}

// Fetch Categories for Sidebar
$categories = $pdo->query("SELECT category, COUNT(*) as count FROM news GROUP BY category")->fetchAll();
// Fetch Archives for Sidebar
$archives = $pdo->query("SELECT DISTINCT YEAR(news_date) as year, MONTH(news_date) as month FROM news ORDER BY year DESC, month DESC")->fetchAll();

?>
<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($article['title']); ?> | M Models News</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="/assets/styles.css" />
    <style>
        .archive-link:hover { color: #C50A76; }
        .article-content img { max-width: 100%; height: auto; border-radius: 2rem; margin: 2.5rem 0; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
        .article-content p { margin-bottom: 1.5rem; font-size: 1.125rem; line-height: 1.8; color: #4B5563; }
        .article-content h1, .article-content h2, .article-content h3 { font-family: 'Playfair Display', serif; font-weight: bold; margin-top: 3rem; margin-bottom: 1.5rem; color: #111827; }
        .article-content h2 { font-size: 2.25rem; tracking: -0.02em; }
        .article-content ul, .article-content ol { padding-left: 1.5rem; margin-bottom: 1.5rem; list-style-type: disc; color: #4B5563; }
    </style>
  </head>
  <body class="antialiased overflow-x-hidden selection:bg-primary selection:text-white bg-white">
    <!-- Navbar -->
    <nav
      id="navbar"
      class="fixed w-full z-50 glass-nav transition-all duration-300"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-24">
          <div class="flex-shrink-0 flex items-center">
            <a href="index.html" class="block"
              ><img
                src="/assets/others/logo.png"
                alt="M Models"
                class="h-10 md:h-12 w-auto object-contain"
            /></a>
          </div>
          <div
            class="hidden xl:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider"
          >
            <a href="index.html" class="menu-link text-gray-600">Home</a>

            <!-- Talent -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center text-gray-600"
                >Talent <i class="fas fa-chevron-down nav-arrow"></i
              ></span>
              <div class="dropdown-menu">
                <a href="models.html" class="dropdown-item"
                  >Models <i class="fas fa-arrow-right"></i
                ></a>
                <a href="influencers.html" class="dropdown-item"
                  >Influencers <i class="fas fa-arrow-right"></i
                ></a>
                <a href="talent-introduction.html" class="dropdown-item"
                  >Introduction <i class="fas fa-arrow-right"></i
                ></a>
              </div>
            </div>

            <!-- Locations -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center text-gray-600"
                >Locations <i class="fas fa-chevron-down nav-arrow"></i
              ></span>
              <div class="dropdown-menu">
                <a href="toronto.html?lang=en" class="dropdown-item"
                  >Toronto <i class="fas fa-arrow-right"></i
                ></a>
                <a href="vancouver.html?lang=en" class="dropdown-item"
                  >Vancouver <i class="fas fa-arrow-right"></i
                ></a>
                <a href="calgary.html?lang=en" class="dropdown-item"
                  >Calgary <i class="fas fa-arrow-right"></i
                ></a>
                <a href="japan.html?lang=ja" class="dropdown-item"
                  >Japan <i class="fas fa-arrow-right"></i
                ></a>
              </div>
            </div>

            <!-- Work -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center text-gray-600"
                >Our Work <i class="fas fa-chevron-down nav-arrow"></i
              ></span>
              <div class="dropdown-menu">
                <a href="our-works.html" class="dropdown-item"
                  >Showcase <i class="fas fa-arrow-right"></i
                ></a>
                <a href="video.html" class="dropdown-item"
                  >Video <i class="fas fa-arrow-right"></i
                ></a>
                <a href="clients.html" class="dropdown-item"
                  >Our Clients <i class="fas fa-arrow-right"></i
                ></a>
              </div>
            </div>

            <!-- News -->
            <a href="news.php" class="menu-link text-primary active">News</a>

            <!-- Join/Hire -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center text-gray-600"
                >Join Us <i class="fas fa-chevron-down nav-arrow"></i
              ></span>
              <div class="dropdown-menu">
                <a href="become-a-model.html" class="dropdown-item"
                  >Become a Model <i class="fas fa-arrow-right"></i
                ></a>
                <a href="influencer-registration.html" class="dropdown-item"
                  >Influencer Registeration <i class="fas fa-arrow-right"></i
                ></a>
                <a href="hire-a-model.html" class="dropdown-item"
                  >Hire a Model <i class="fas fa-arrow-right"></i
                ></a>
              </div>
            </div>

            <!-- More -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center text-gray-600"
                >Contact <i class="fas fa-chevron-down nav-arrow"></i
              ></span>
              <div class="dropdown-menu">
                <a href="faq.html" class="dropdown-item"
                  >FAQ's <i class="fas fa-arrow-right"></i
                ></a>
                <a href="contact.html" class="dropdown-item"
                  >Contact Us <i class="fas fa-arrow-right"></i
                ></a>
              </div>
            </div>

            <!-- Language Switcher -->
            <div class="dropdown-wrapper group">
              <span
                class="menu-link flex items-center text-gray-600 switcher-active-glow px-3 py-1.5"
                ><i class="fas fa-globe mr-2"></i>
                <span class="nav-current-lang">EN</span>
                <i class="fas fa-chevron-down nav-arrow"></i
              ></span>
              <div class="dropdown-menu">
                <a
                  href="javascript:void(0)"
                  onclick="applyLanguage('en')"
                  class="dropdown-item"
                  >English</a
                >
                <a
                  href="javascript:void(0)"
                  onclick="applyLanguage('fr')"
                  class="dropdown-item"
                  >Français</a
                >
                <a
                  href="javascript:void(0)"
                  onclick="applyLanguage('ja')"
                  class="dropdown-item"
                  >日本語</a
                >
              </div>
            </div>
           </div>
          <div class="xl:hidden flex items-center">
            <button id="mobile-btn" class="text-gray-600 hover:text-primary">
              <i class="fas fa-bars text-2xl"></i>
            </button>
          </div>
        </div>
      </div>
      <div
        id="mobile-menu"
        class="hidden xl:hidden bg-white border-t border-gray-100 shadow-xl pb-4"
      >
        <div
          class="px-8 pt-2 pb-8 flex flex-col space-y-0.5 text-sm font-bold uppercase tracking-wider"
        >
          <a href="index.html" class="mobile-link"
            >Home <i class="fas fa-chevron-right"></i
          ></a>

          <div class="mobile-section-title">Talent</div>
          <a href="models.html" class="mobile-link"
            >Models <i class="fas fa-chevron-right"></i
          ></a>
          <a href="influencers.html" class="mobile-link"
            >Influencers <i class="fas fa-chevron-right"></i
          ></a>
          <a href="talent-introduction.html" class="mobile-link"
            >Introduction <i class="fas fa-chevron-right"></i
          ></a>

          <div class="mobile-section-title">Locations</div>
          <a href="toronto.html?lang=en" class="mobile-link"
            >Toronto <i class="fas fa-chevron-right"></i
          ></a>
          <a href="vancouver.html?lang=en" class="mobile-link"
            >Vancouver <i class="fas fa-chevron-right"></i
          ></a>
          <a href="calgary.html?lang=en" class="mobile-link"
            >Calgary <i class="fas fa-chevron-right"></i
          ></a>
          <a href="japan.html?lang=ja" class="mobile-link"
            >Japan <i class="fas fa-chevron-right"></i
          ></a>

          <div class="mobile-section-title">Our Work</div>
          <a href="our-works.html" class="mobile-link"
            >Showcase <i class="fas fa-chevron-right"></i
          ></a>
          <a href="video.html" class="mobile-link"
            >Video <i class="fas fa-chevron-right"></i
          ></a>
          <a href="clients.html" class="mobile-link"
            >Our Clients <i class="fas fa-chevron-right"></i
          ></a>
          <a href="news.php" class="mobile-link active"
            >News <i class="fas fa-chevron-right"></i
          ></a>

          <div class="mobile-section-title">Join & Booking</div>
          <a href="become-a-model.html" class="mobile-link"
            >Become a Model <i class="fas fa-chevron-right"></i
          ></a>
          <a href="influencer-registration.html" class="mobile-link"
            >Influencer Registeration <i class="fas fa-chevron-right"></i
          ></a>
          <a href="hire-a-model.html" class="mobile-link"
            >Hire a Model <i class="fas fa-chevron-right"></i
          ></a>

          <div class="mobile-section-title">Information</div>
          <a href="faq.html" class="mobile-link"
            >FAQ's <i class="fas fa-chevron-right"></i
          ></a>
          <a href="contact.html" class="mobile-link"
            >Contact Us <i class="fas fa-chevron-right"></i
          ></a>

          <div class="mobile-section-title">Language</div>
          <div class="flex gap-2 mt-2">
            <button
              onclick="applyLanguage('en')"
              class="flex-1 py-3 border border-gray-200 rounded-lg text-[10px] font-bold hover:bg-primary hover:text-white transition-all"
            >
              EN
            </button>
            <button
              onclick="applyLanguage('fr')"
              class="flex-1 py-3 border border-gray-200 rounded-lg text-[10px] font-bold hover:bg-primary hover:text-white transition-all"
            >
              FR
            </button>
            <button
              onclick="applyLanguage('ja')"
              class="flex-1 py-3 border border-gray-200 rounded-lg text-[10px] font-bold hover:bg-primary hover:text-white transition-all"
            >
              JA
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Article Header -->
    <header class="pt-48 pb-20 bg-gray-50/50">
      <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="flex items-center justify-center gap-4 mb-8">
            <span class="bg-primary/10 text-primary px-5 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest">
                <?php echo htmlspecialchars($article['category']); ?>
            </span>
            <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                <i class="far fa-calendar text-primary/50"></i> <?php echo date('F d, Y', strtotime($article['news_date'])); ?>
            </span>
        </div>
        <h1 class="font-serif text-5xl md:text-7xl font-bold text-gray-900 mb-10 leading-tight tracking-tight">
            <?php echo htmlspecialchars($article['title']); ?>
        </h1>
        <div class="flex items-center justify-center gap-6">
            <a href="news.php" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-primary transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-left text-[8px]"></i> Back to News
            </a>
            <div class="h-4 w-px bg-gray-200"></div>
            <div class="flex items-center gap-4 text-gray-300">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="hover:text-primary transition-colors"><i class="fab fa-facebook-f text-sm"></i></a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" class="hover:text-primary transition-colors"><i class="fab fa-twitter text-sm"></i></a>
                <button onclick="copyToClipboard()" class="hover:text-primary transition-colors"><i class="fas fa-link text-sm"></i></button>
            </div>
        </div>
      </div>
    </header>

    <!-- Article Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
      <div class="flex flex-col lg:flex-row gap-20">
        
        <article class="flex-1 max-w-4xl">
          <div class="article-content">
            <?php echo $article['content']; ?>
          </div>
          
          <div class="mt-20 pt-10 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-serif text-xl italic">M</div>
                <div>
                    <h4 class="text-sm font-bold text-gray-900">M Models Editorial</h4>
                    <p class="text-xs text-gray-500">Official Press Team</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Share This Story</span>
                <div class="flex items-center gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white transition-all"><i class="fab fa-facebook-f text-xs"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white transition-all"><i class="fab fa-twitter text-xs"></i></a>
                    <button onclick="copyToClipboard()" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white transition-all"><i class="fas fa-link text-xs"></i></button>
                </div>
            </div>
          </div>
        </article>

        <!-- Sidebar -->
        <aside class="w-full lg:w-80 space-y-10">
            <div class="bg-white p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100 space-y-10">
                <div>
                    <h3 class="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="w-5 h-px bg-primary/30"></span> Explore
                    </h3>
                    <ul class="space-y-4">
                        <?php foreach ($categories as $cat): ?>
                            <li><a href="news.php?cat=<?php echo urlencode($cat['category']); ?>" class="archive-link text-sm font-semibold text-gray-600 hover:text-primary transition-all flex items-center justify-between group">
                                <?php echo htmlspecialchars($cat['category']); ?>
                                <i class="fas fa-chevron-right text-[8px] opacity-0 group-hover:opacity-100 transform translate-x-[-4px] group-hover:translate-x-0 transition-all"></i>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="pt-10 border-t border-gray-100">
                    <h3 class="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="w-5 h-px bg-primary/30"></span> Recent Archives
                    </h3>
                    <ul class="space-y-4">
                        <?php 
                        $i = 0;
                        foreach ($archives as $arch): 
                            if ($i++ > 4) break;
                            $monthName = date("F", mktime(0, 0, 0, $arch['month'], 10));
                        ?>
                            <li><a href="news.php?month=<?php echo $arch['month']; ?>&year=<?php echo $arch['year']; ?>" class="archive-link text-sm font-semibold text-gray-600 hover:text-primary transition-all flex items-center justify-between group">
                                <?php echo $monthName . ' ' . $arch['year']; ?>
                                <i class="fas fa-chevron-right text-[8px] opacity-0 group-hover:opacity-100 transform translate-x-[-4px] group-hover:translate-x-0 transition-all"></i>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="bg-gray-900 p-10 rounded-3xl text-white relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/20 rounded-full -translate-y-16 translate-x-16 blur-3xl group-hover:bg-primary/30 transition-colors"></div>
                <h3 class="font-serif text-2xl font-bold mb-4 relative z-10">Stay Connected</h3>
                <p class="text-white/50 text-sm mb-8 leading-relaxed relative z-10">Follow us on social media for daily updates and highlights.</p>
                <div class="flex items-center gap-4 relative z-10">
                    <a href="https://www.instagram.com/mmodelsandtalentagency" target="_blank" class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center hover:bg-primary hover:text-white transition-all transform hover:scale-110 shadow-lg group-hover:shadow-primary/20">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                </div>
            </div>
        </aside>

      </div>
    </main>

    <section class="py-20 bg-white border-t border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-primary font-bold uppercase tracking-[0.3em] text-sm mb-4">Trusted By</p>
        <h2 class="font-serif text-3xl md:text-4xl font-bold mb-12 text-gray-900">Our Clients</h2>
        <div class="flex flex-wrap justify-center items-center gap-10 md:gap-16 opacity-60">
          <img src="/assets/our-clients/1200px-pc-financial-logo-svg_orig.png" alt="PC Financial" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-300" />
          <img src="/assets/our-clients/canada-post-logo_orig.png" alt="Canada Post" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-300" />
          <img src="/assets/our-clients/samsung-logo_orig.jpg" alt="Samsung" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-300" />
          <img src="/assets/our-clients/rogers_1_orig.jpg" alt="Rogers" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-300" />
          <img src="/assets/our-clients/cineplex_orig.png" alt="Cineplex" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-300" />
          <img src="/assets/our-clients/the-brick-logo_orig.gif" alt="The Brick" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-300" />
        </div>
        <div class="mt-12">
          <a href="clients.html" class="inline-block text-sm font-bold uppercase tracking-widest text-primary border-b-2 border-primary pb-1 hover:text-black hover:border-black transition-colors">View All Clients</a>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12 border-t border-gray-200">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <a href="index.html" class="block mb-6"><img src="/assets/others/logo.png" alt="M Models" class="max-h-16 w-auto object-contain mx-auto" /></a>
        <p class="text-gray-400 text-xs">© 2026 M Models Global. All rights reserved. • <a href="privacy-policy.html" class="hover:text-primary transition-colors">Privacy Policy</a></p>
      </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/assets/main.js"></script>
    <script>
        function copyToClipboard() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Link Copied!',
                    text: 'The article link has been copied to your clipboard.',
                    timer: 2000,
                    showConfirmButton: false,
                    confirmButtonColor: '#C50A76'
                });
            });
        }
    </script>
  </body>
</html>
