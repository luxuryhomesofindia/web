<?php
/**
 * LHI Dynamic Blog Listing Page
 * Fetches and filters posts from blog-data/posts.json
 * Supports Search, Categories, and Pagination.
 */

// Load posts database
$posts_file = __DIR__ . '/blog-data/posts.json';
$posts = [];
if (file_exists($posts_file)) {
    $posts = json_decode(file_get_contents($posts_file), true) ?: [];
}

// ----------------------------------------------------
# 1. FILTERING & SEARCH
// ----------------------------------------------------
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['q'] ?? '';

$filtered_posts = $posts;

// Apply Category Filter
if (!empty($category_filter)) {
    // Replace URL dashes with spaces if necessary
    $formatted_cat = str_replace('-', ' ', $category_filter);
    $filtered_posts = array_filter($posts, function($post) use ($formatted_cat) {
        return strcasecmp(str_replace(' & ', ' ', $post['category']), str_replace(' & ', ' ', $formatted_cat)) === 0 
            || strcasecmp($post['category'], $formatted_cat) === 0;
    });
}

// Apply Search Filter
if (!empty($search_query)) {
    $filtered_posts = array_filter($filtered_posts, function($post) use ($search_query) {
        return stripos($post['title'], $search_query) !== false 
            || stripos($post['excerpt'], $search_query) !== false;
    });
}

// Re-index array
$filtered_posts = array_values($filtered_posts);

// Identify Featured Post (First post in default list)
$featured_post = null;
if (empty($category_filter) && empty($search_query) && !empty($posts)) {
    foreach ($posts as $post) {
        if ($post['is_featured']) {
            $featured_post = $post;
            break;
        }
    }
    if (!$featured_post) {
        $featured_post = $posts[0];
    }
    
    // Remove featured post from the list of standard grid cards to avoid duplication
    $filtered_posts = array_filter($filtered_posts, function($p) use ($featured_post) {
        return $p['id'] !== $featured_post['id'];
    });
    $filtered_posts = array_values($filtered_posts);
}

// ----------------------------------------------------
# 2. PAGINATION (6 posts per page)
// ----------------------------------------------------
$posts_per_page = 6;
$total_posts = count($filtered_posts);
$total_pages = ceil($total_posts / $posts_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $posts_per_page;

$paginated_posts = array_slice($filtered_posts, $offset, $posts_per_page);

// Unique categories for sidebar
$all_categories = [];
foreach ($posts as $p) {
    if (!in_array($p['category'], $all_categories)) {
        $all_categories[] = $p['category'];
    }
}

// Latest Posts for sidebar
$latest_posts = array_slice($posts, 0, 5);

// Popular Posts (IDs 5, 12, 25, 42)
$popular_ids = [5, 12, 25, 42];
$popular_posts = array_filter($posts, function($p) use ($popular_ids) {
    return in_array($p['id'], $popular_ids);
});

// Dynamic Title & Description for SEO
$page_title = "Luxury Villa & Home Design Blog | Luxury Homes of India";
$meta_desc = "Discover latest design trends, construction costs, structural tips, Vastu guides, and home automation updates from Luxury Homes of India.";
if (!empty($category_filter)) {
    $page_title = htmlspecialchars($category_filter) . " Archives - Luxury Villa Blog";
    $meta_desc = "Read our premium guides and design articles covering " . htmlspecialchars($category_filter) . " for luxury residential construction.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $meta_desc; ?>">
    <link rel="canonical" href="https://luxuryhomesofindia.in/blog" />
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/fav.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet preload" href="assets/css/vendor/bootstrap.min.css" as="style">
    <link rel="stylesheet preload" href="assets/css/style.css" as="style">
    <link rel="stylesheet" href="assets/css/premium-popup.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Red+Hat+Display:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet preload" as="style">

    <style>
        body {
            background-color: var(--pearl-white);
            color: var(--text-dark);
            font-family: 'DM Sans', sans-serif;
            line-height: 1.85;
        }
        .rts-breadcrumb-area-about {
            background: var(--charcoal-black);
            padding: 100px 0;
            text-align: center;
            border-bottom: 1px solid var(--border-gold);
        }
        .bg-title {
            color: var(--champagne-gold);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }
        .rts-breadcrumb-area-about h2 {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-light);
            margin: 0;
            letter-spacing: -1px;
            font-family: 'Red Hat Display', 'Outfit', sans-serif;
        }
        
        /* Sidebar Styles */
        .blog-sidebar {
            background: #ffffff;
            border: 1px solid var(--border-light);
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-luxury-light);
        }
        .sidebar-widget-title {
            font-size: 18px;
            font-weight: 700;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: var(--text-dark);
            letter-spacing: 0.5px;
            font-family: 'Red Hat Display', 'Outfit', sans-serif;
        }
        .sidebar-search-box {
            position: relative;
        }
        .sidebar-search-box input {
            width: 100%;
            background: var(--ivory-white);
            border: 1px solid var(--border-light);
            padding: 12px 45px 12px 16px;
            color: var(--text-dark);
            border-radius: 4px;
            outline: none;
        }
        .sidebar-search-box button {
            position: absolute;
            right: 15px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--champagne-gold);
            cursor: pointer;
        }
        .cat-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .cat-list li {
            margin-bottom: 12px;
        }
        .cat-list li a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s;
            display: flex;
            justify-content: space-between;
        }
        .cat-list li a:hover {
            color: var(--champagne-gold);
        }
        
        /* Blog Post Items */
        .featured-post-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 40px;
            transition: var(--transition);
            box-shadow: var(--shadow-luxury-light);
        }
        .featured-post-card:hover {
            border-color: var(--champagne-gold);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(26,26,26,0.06), 0 0 20px rgba(212, 175, 55, 0.08);
        }
        .featured-post-img img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        .blog-card-meta {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            gap: 15px;
        }
        .blog-card-meta span i {
            color: var(--champagne-gold);
            margin-right: 5px;
        }
        .featured-post-content {
            padding: 30px;
        }
        .featured-post-content h3 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 15px;
            font-family: 'Red Hat Display', 'Outfit', sans-serif;
        }
        .featured-post-content h3 a {
            color: var(--text-dark);
            text-decoration: none;
            transition: color 0.3s;
        }
        .featured-post-content h3 a:hover {
            color: var(--champagne-gold);
        }
        .featured-post-content p {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 20px;
        }
        
        .blog-grid-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            box-shadow: var(--shadow-luxury-light);
        }
        .blog-grid-card:hover {
            transform: translateY(-6px);
            border-color: var(--champagne-gold);
            box-shadow: 0 20px 40px rgba(26,26,26,0.06), 0 0 20px rgba(212, 175, 55, 0.08);
        }
        .blog-grid-img img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .blog-grid-content {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .blog-grid-content h4 {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 15px;
            margin-top: 5px;
            font-family: 'Red Hat Display', 'Outfit', sans-serif;
        }
        .blog-grid-content h4 a {
            color: var(--text-dark);
            text-decoration: none;
            transition: color 0.3s;
        }
        .blog-grid-content h4 a:hover {
            color: var(--champagne-gold);
        }
        .blog-grid-content p {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .read-more-btn {
            color: var(--champagne-gold);
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: gap 0.2s;
        }
        .read-more-btn:hover {
            gap: 12px;
            color: var(--rich-crimson);
        }
        
        /* Pagination */
        .blog-pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }
        .blog-pagination a, .blog-pagination span {
            width: 44px; height: 44px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: var(--shadow-luxury-light);
        }
        .blog-pagination a:hover, .blog-pagination span.active {
            background: var(--rich-crimson);
            color: var(--pearl-white);
            border-color: var(--rich-crimson);
        }
    </style>

  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GT-MJSH26NL');</script>
  <!-- End Google Tag Manager -->

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-7Q7C4XN7BF"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-7Q7C4XN7BF');
    gtag('config', 'AW-17674541365');
  </script>

  <!-- Microsoft Clarity -->
  <script type="text/javascript">
      (function(c,l,a,r,i,t,y){
          c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
          t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
          y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
      })(window, document, "clarity", "script", "xxlkeydv0r");
  </script>

  <!-- LHI Universal Tracking -->
  <script defer src="/assets/js/analytics-tracking.js"></script>

</head>
<body>

  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GT-MJSH26NL"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->


    <!-- MASTER HEADER -->
    <div id="master-header"></div>

    <!-- BREADCRUMB -->
    <div class="rts-breadcrumb-area-about">
        <div class="container">
            <span class="bg-title">Insight & Ideas</span>
            <h2>Luxury Construction Blog</h2>
        </div>
    </div>

    <!-- MAIN BLOG SECTION -->
    <div class="container pt--80 pb--80">
        <div class="row g-5">
            <div class="col-lg-8">
                
                <!-- Search/Filter Results Status -->
                <?php if (!empty($category_filter) || !empty($search_query)): ?>
                    <div style="margin-bottom:30px; border-bottom:1px solid var(--border-light); padding-bottom:15px;">
                        <h3 style="font-size:1.5rem; font-weight:800; font-family:'Red Hat Display', 'Outfit', sans-serif;">
                            Results for 
                            <?php if (!empty($category_filter)) echo "Category: <span style='color:var(--champagne-gold);'>" . htmlspecialchars($category_filter) . "</span>"; ?>
                            <?php if (!empty($search_query)) echo "Search: <span style='color:var(--champagne-gold);'>\"" . htmlspecialchars($search_query) . "\"</span>"; ?>
                        </h3>
                        <p style="color:var(--text-muted); margin-top:5px; font-size:0.95rem;"><?php echo $total_posts; ?> articles found</p>
                    </div>
                <?php endif; ?>

                <!-- Featured Post (Only show on page 1 of default lists) -->
                <?php if ($featured_post && $current_page == 1): ?>
                    <div class="featured-post-card animate-pop-in">
                        <div class="featured-post-img">
                            <a href="blog/<?php echo $featured_post['slug']; ?>">
                                <img src="<?php echo $featured_post['featured_image']; ?>" alt="<?php echo htmlspecialchars($featured_post['title']); ?>">
                            </a>
                        </div>
                        <div class="featured-post-content">
                            <div class="blog-card-meta">
                                <span><i class="far fa-circle-user"></i> <?php echo $featured_post['author']; ?></span>
                                <span><i class="far fa-clock"></i> <?php echo $featured_post['publish_date']; ?></span>
                                <span><i class="far fa-tags"></i> <?php echo $featured_post['category']; ?></span>
                            </div>
                            <h3><a href="blog/<?php echo $featured_post['slug']; ?>"><?php echo $featured_post['title']; ?></a></h3>
                            <p><?php echo $featured_post['excerpt']; ?></p>
                            <a href="blog/<?php echo $featured_post['slug']; ?>" class="read-more-btn">Read Article <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Posts Grid -->
                <?php if (!empty($paginated_posts)): ?>
                    <div class="row g-4">
                        <?php foreach ($paginated_posts as $post): ?>
                            <div class="col-md-6">
                                <div class="blog-grid-card">
                                    <div class="blog-grid-img">
                                        <a href="blog/<?php echo $post['slug']; ?>">
                                            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                                        </a>
                                    </div>
                                    <div class="blog-grid-content">
                                        <div class="blog-card-meta">
                                            <span><i class="far fa-clock"></i> <?php echo $post['publish_date']; ?></span>
                                            <span><i class="far fa-tags"></i> <?php echo $post['category']; ?></span>
                                        </div>
                                        <h4><a href="blog/<?php echo $post['slug']; ?>"><?php echo $post['title']; ?></a></h4>
                                        <p><?php echo $post['excerpt']; ?></p>
                                        <a href="blog/<?php echo $post['slug']; ?>" class="read-more-btn">Read Article <i class="fas fa-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="blog-pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?category=<?php echo urlencode($category_filter); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                            <?php endif; ?>
                            
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <?php if ($p == $current_page): ?>
                                    <span class="active"><?php echo $p; ?></span>
                                <?php else: ?>
                                    <a href="?category=<?php echo urlencode($category_filter); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?category=<?php echo urlencode($category_filter); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="text-align:center; padding: 60px 20px;">
                        <i class="fas fa-folder-open fa-3x" style="color:var(--champagne-gold); margin-bottom:20px;"></i>
                        <h4 style="font-weight:800; font-family:'Red Hat Display', 'Outfit', sans-serif;">No Articles Found</h4>
                        <p style="color:var(--text-secondary);">We couldn't find any articles matching your search filter. Try adjusting your keywords.</p>
                        <a href="blog" class="btn-primary-lhi" style="margin-top:20px; display:inline-block; text-decoration:none; padding:10px 25px;">Reset Feed</a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <aside>
                    <!-- Search Widget -->
                    <div class="blog-sidebar">
                        <h4 class="sidebar-widget-title">Search Hub</h4>
                        <form action="blog.php" method="GET" class="sidebar-search-box">
                            <input type="text" name="q" placeholder="Type keywords..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <?php if (!empty($category_filter)): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                            <?php endif; ?>
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <!-- Categories Widget -->
                    <div class="blog-sidebar">
                        <h4 class="sidebar-widget-title">Categories</h4>
                        <ul class="cat-list">
                            <li><a href="blog">All Topics <span>(<?php echo count($posts); ?>)</span></a></li>
                            <?php foreach ($all_categories as $cat): 
                                $cat_count = count(array_filter($posts, function($p) use ($cat) { return $p['category'] === $cat; }));
                            ?>
                                <li>
                                    <a href="?category=<?php echo urlencode(str_replace(' ', '-', strtolower($cat))); ?>">
                                        <?php echo $cat; ?> 
                                        <span>(<?php echo $cat_count; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Popular Posts Widget -->
                    <div class="blog-sidebar">
                        <h4 class="sidebar-widget-title">Popular Articles</h4>
                        <ul class="cat-list" style="display:block;">
                            <?php foreach ($popular_posts as $pop): ?>
                                <li style="margin-bottom: 18px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">
                                    <a href="blog/<?php echo $pop['slug']; ?>" style="display:block; color:var(--text-dark); font-weight:700; font-size:0.95rem; line-height:1.4; margin-bottom:5px; text-decoration:none;">
                                        <?php echo $pop['title']; ?>
                                    </a>
                                    <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo $pop['publish_date']; ?> | <?php echo $pop['reading_time']; ?> read</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <!-- NEWSLETTER CTA -->
    <section style="background:var(--ivory-white); border-top:1px solid var(--border-light); border-bottom:1px solid var(--border-light); text-align:center; padding:80px 20px;">
        <div class="container" style="max-width:650px;">
            <h3 style="color:var(--text-dark); font-weight:800; margin-bottom:10px; font-family:'Red Hat Display', 'Outfit', sans-serif;">Subscribe to LHI Digest</h3>
            <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:30px;">Get the latest luxury home blueprints, Vastu checklists, and construction material costing updates straight to your inbox.</p>
            <form action="" class="sidebar-search-box" style="max-width:450px; margin: 0 auto;" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                <input type="email" placeholder="Your Email Address" required style="background:#ffffff; border:1px solid var(--border-light); border-radius:4px; padding:15px; color:var(--text-dark); width:100%;">
                <button type="submit" style="right:15px; background:none; border:none; color:var(--champagne-gold);"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </section>

    <!-- MASTER FOOTER -->
    <div id="master-footer"></div>

    <!-- JS Core Scripts -->
    <script defer src="assets/js/plugins/jquery.js"></script>
    <script defer src="assets/js/vendor/bootstrap.min.js"></script>
    <script defer src="assets/js/plugins/metismenu.js"></script>
    <script defer src="assets/js/main.js"></script>
    <script defer src="assets/js/popup-trigger.js"></script>
    <script defer src="assets/js/analytics-tracking.js"></script>

    <!-- Load Header/Footer Dynamically -->
    <script>
        fetch("header.html")
            .then(res => res.text())
            .then(data => {
                document.getElementById('master-header').innerHTML = data;
                if (window.rtsJs && typeof window.rtsJs.sideMenu === 'function') {
                    window.rtsJs.sideMenu();
                }
            });

        fetch("footer.html")
            .then(res => res.text())
            .then(data => {
                document.getElementById('master-footer').innerHTML = data;
            });
    </script>
</body>
</html>
