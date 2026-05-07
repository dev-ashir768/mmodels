<?php
require 'includes/db.php';

// Fetch Search and Filters
$search = $_GET['s'] ?? '';
$category_filter = $_GET['cat'] ?? '';
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? '';

// Build Query
$query = "SELECT * FROM news WHERE status = 'published'";
$params = [];

if ($search) {
    $query .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

if ($month_filter && $year_filter) {
    $query .= " AND MONTH(news_date) = ? AND YEAR(news_date) = ?";
    $params[] = $month_filter;
    $params[] = $year_filter;
} elseif ($year_filter) {
    $query .= " AND YEAR(news_date) = ?";
    $params[] = $year_filter;
}

$query .= " ORDER BY news_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll();

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
    <title>News | M Models</title>
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
        .article-card:hover { transform: translateY(-4px); }
        .article-content img { max-width: 100%; height: auto; border-radius: 1rem; margin: 1.5rem 0; }
        .article-content p { margin-bottom: 1.25rem; }
        .article-content h1, .article-content h2, .article-content h3 { font-family: 'Playfair Display', serif; font-weight: bold; margin-top: 2rem; margin-bottom: 1rem; color: #111827; }
        .article-content h2 { font-size: 1.5rem; }
        .article-content ul, .article-content ol { padding-left: 1.5rem; margin-bottom: 1.25rem; list-style-type: disc; }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
  </head>
  <body class="antialiased overflow-x-hidden selection:bg-primary selection:text-white bg-gray-50">
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

    <!-- Page Header -->
    <header class="pt-48 pb-20 bg-white border-b border-gray-100">
      <div class="text-center container mx-auto px-4">
        <h1 class="text-primary font-bold uppercase tracking-[0.3em] text-[10px] md:text-xs mb-4">Updates & Press</h1>
        <h2 class="font-serif text-5xl md:text-7xl font-bold text-gray-900 mb-8 tracking-tight">Latest News</h2>
        <div class="h-1 w-20 bg-primary mx-auto rounded-full"></div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="flex flex-col lg:flex-row gap-16">
        
        <!-- Sidebar -->
        <aside class="w-full lg:w-80 order-2 lg:order-1 space-y-10">
          
          <!-- Search -->
          <div class="bg-white p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100">
            <h3 class="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <span class="w-5 h-px bg-primary/30"></span> Search News
            </h3>
            <form action="news.php" method="GET" class="relative">
              <input type="text" name="s" value="<?php echo htmlspecialchars($search); ?>" placeholder="Keywords..." class="w-full bg-gray-50 border-0 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 text-sm transition-all font-medium">
              <button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-primary transition-colors">
                <i class="fas fa-arrow-right"></i>
              </button>
            </form>
          </div>

          <!-- Archives -->
          <div class="bg-white p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100">
            <h3 class="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <span class="w-5 h-px bg-primary/30"></span> Archives
            </h3>
            <ul class="space-y-4">
              <?php foreach ($archives as $arch): 
                  $monthName = date("F", mktime(0, 0, 0, $arch['month'], 10));
              ?>
                <li>
                  <a href="news.php?month=<?php echo $arch['month']; ?>&year=<?php echo $arch['year']; ?>" class="archive-link flex items-center justify-between text-gray-600 font-semibold text-sm transition-all group">
                    <span><?php echo $monthName . ' ' . $arch['year']; ?></span>
                    <i class="fas fa-chevron-right text-[8px] opacity-0 group-hover:opacity-100 transform translate-x-[-4px] group-hover:translate-x-0 transition-all"></i>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Categories -->
          <div class="bg-white p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.03)] border border-gray-100">
            <h3 class="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <span class="w-5 h-px bg-primary/30"></span> Categories
            </h3>
            <ul class="space-y-4">
              <?php foreach ($categories as $cat): ?>
                <li>
                  <a href="news.php?cat=<?php echo urlencode($cat['category']); ?>" class="archive-link flex items-center justify-between text-gray-600 font-semibold text-sm transition-all group">
                    <span><?php echo htmlspecialchars($cat['category']); ?></span>
                    <span class="text-[10px] bg-gray-50 px-2 py-1 rounded-lg text-gray-400 group-hover:bg-primary group-hover:text-white transition-all"><?php echo $cat['count']; ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Stay Connected -->
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

        <!-- News Feed -->
        <div class="flex-1 order-1 lg:order-2 space-y-12">
          <?php if ($category_filter || $month_filter || $search): ?>
            <div class="bg-primary/5 p-6 rounded-3xl border border-primary/10 flex flex-col md:flex-row justify-between items-center mb-12 gap-4">
                <span class="text-sm font-bold text-primary flex items-center gap-2">
                    <i class="fas fa-filter text-xs"></i> Filtering By: 
                    <span class="text-gray-900 font-semibold ml-2">
                        <?php 
                            if($search) echo "Search: '".htmlspecialchars($search)."'";
                            if($category_filter) echo " Category: ".htmlspecialchars($category_filter);
                            if($month_filter) echo " Archive: ".date("F", mktime(0, 0, 0, $month_filter, 10))." ".$year_filter;
                        ?>
                    </span>
                </span>
                <a href="news.php" class="text-[10px] font-bold uppercase tracking-[0.2em] bg-white px-6 py-3 rounded-2xl text-primary hover:bg-primary hover:text-white transition shadow-sm border border-primary/10">Clear All Filters</a>
            </div>
          <?php endif; ?>

          <?php foreach ($articles as $article): ?>
            <article class="article-card bg-white p-10 md:p-16 rounded-[3.5rem] shadow-[0_20px_60px_rgba(0,0,0,0.02)] border border-gray-50 transition-all duration-500 overflow-hidden relative group">
              <div class="absolute top-0 left-0 w-2 h-full bg-primary opacity-0 group-hover:opacity-100 transition-opacity"></div>
              
              <div class="flex flex-col md:flex-row md:items-center gap-6 mb-10">
                <span class="bg-primary/10 text-primary px-5 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest">
                    <?php echo htmlspecialchars($article['category']); ?>
                </span>
                <div class="flex items-center text-gray-400 text-[10px] gap-6 font-bold uppercase tracking-widest">
                  <span class="flex items-center gap-2"><i class="far fa-calendar text-primary/50"></i> <?php echo date('M d, Y', strtotime($article['news_date'])); ?></span>
                  <span class="flex items-center gap-2"><i class="far fa-clock text-primary/50"></i> 4 min read</span>
                </div>
              </div>
              
              <h3 class="font-serif text-4xl md:text-5xl font-bold text-gray-900 mb-8 leading-tight tracking-tight group-hover:text-primary transition-colors duration-300">
                <a href="news-detail.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a>
              </h3>
              
              <div class="text-gray-500 leading-relaxed text-lg mb-12 line-clamp-3 article-content">
                <?php echo $article['content']; // HTML allowed here from editor ?>
              </div>
              
              <div class="pt-10 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-between gap-6">
                <a href="news-detail.php?id=<?php echo $article['id']; ?>" class="text-primary font-bold uppercase tracking-[0.2em] text-[10px] flex items-center gap-4 group/link">
                    Read Full Story 
                    <span class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center transition-all group-hover/link:bg-primary group-hover/link:text-white transform group-hover/link:scale-110">
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </span>
                </a>
                <div class="flex items-center gap-6 text-gray-200">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . "/news-detail.php?id=" . $article['id']); ?>" target="_blank" class="hover:text-primary transition-colors"><i class="fab fa-facebook-f text-sm"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("https://" . $_SERVER['HTTP_HOST'] . "/news-detail.php?id=" . $article['id']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" class="hover:text-primary transition-colors"><i class="fab fa-twitter text-sm"></i></a>
                    <button onclick="copyLink('<?php echo "https://" . $_SERVER['HTTP_HOST'] . "/news-detail.php?id=" . $article['id']; ?>')" class="hover:text-primary transition-colors"><i class="fas fa-link text-sm"></i></button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>

          <?php if (empty($articles)): ?>
            <div class="bg-white p-24 rounded-[4rem] text-center border border-gray-50 shadow-[0_20px_60px_rgba(0,0,0,0.02)]">
                <div class="w-24 h-24 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-10">
                    <i class="fas fa-newspaper text-4xl text-gray-100"></i>
                </div>
                <h3 class="text-3xl font-serif font-bold text-gray-900 mb-4">No Articles Found</h3>
                <p class="text-gray-500 mb-10 max-w-sm mx-auto leading-relaxed">We couldn't find any news articles matching your criteria. Try adjusting your filters or search keywords.</p>
                <a href="news.php" class="bg-primary text-white px-12 py-5 rounded-2xl font-bold uppercase tracking-widest text-xs hover:bg-black transition shadow-xl shadow-[#C50A76]/20 inline-block">Back to News</a>
            </div>
          <?php endif; ?>
        </div>
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
      $(document).ready(function() {
        $('#mobile-btn').click(function() {
          $('#mobile-menu').toggleClass('hidden');
        });
      });

      function copyLink(url) {
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
