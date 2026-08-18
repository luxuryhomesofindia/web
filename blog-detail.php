<?php
/**
 * LHI Dynamic Blog Detail Page
 * Loads a single post from blog-data/posts.json by slug
 * Renders dynamic TOC, FAQs, related items, and next/prev navigation.
 */

// Load posts database
$posts_file = __DIR__ . '/blog-data/posts.json';
$posts = [];
if (file_exists($posts_file)) {
    $posts = json_decode(file_get_contents($posts_file), true) ?: [];
}

// Get slug from URL
$slug = $_GET['slug'] ?? '';
$post = null;
$post_index = -1;

// Find post by slug
foreach ($posts as $idx => $p) {
    if ($p['slug'] === $slug) {
        $post = $p;
        $post_index = $idx;
        break;
    }
}

// 404 handling if post doesn't exist
if (!$post) {
    header("HTTP/1.1 404 Not Found");
    // Graceful fallback display
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><title>Article Not Found | Luxury Homes of India</title>
        <link rel="stylesheet" href="../assets/css/vendor/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/style.css">
    
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
    <body style="background:#050505; color:#fff; text-align:center; padding:100px 20px;">

  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GT-MJSH26NL"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->

        <h2 style="color:#c6a25a; font-weight:800; margin-bottom:20px;">Article Not Found</h2>
        <p>The requested blog article does not exist or has been moved.</p>
        <a href="/blog" class="rts-btn btn-primary" style="margin-top:20px; display:inline-block; text-decoration:none; padding:10px 25px; background:#c6a25a; color:#000;">Back to Blog Hub</a>
    </body>
    </html>
    <?php
    exit;
}

// ----------------------------------------------------
# 1. GENERATE DYNAMIC TABLE OF CONTENTS (TOC)
// ----------------------------------------------------
// Parse <h3> tags from post content
preg_match_all('/<h3[^>]*>(.*?)<\/h3>/s', $post['content'], $toc_matches);
$toc_items = [];
$content_with_ids = $post['content'];

if (!empty($toc_matches[0])) {
    foreach ($toc_matches[1] as $key => $heading_text) {
        $clean_heading = strip_tags($heading_text);
        $anchor_id = 'heading-' . $key;
        $toc_items[] = [
            'id' => $anchor_id,
            'text' => $clean_heading
        ];
        
        // Inject ID attribute into original content headers
        $old_header = $toc_matches[0][$key];
        $new_header = str_replace('<h3', '<h3 id="' . $anchor_id . '"', $old_header);
        $content_with_ids = str_replace($old_header, $new_header, $content_with_ids);
    }
}

// ----------------------------------------------------
# 2. RELATED ARTICLES (Matching Category, max 3)
// ----------------------------------------------------
$related_articles = array_filter($posts, function($p) use ($post) {
    return $p['category'] === $post['category'] && $p['id'] !== $post['id'];
});
$related_articles = array_slice($related_articles, 0, 3);

// ----------------------------------------------------
# 3. NEXT / PREVIOUS NAVIGATION
// ----------------------------------------------------
$prev_post = $post_index > 0 ? $posts[$post_index - 1] : null;
$next_post = $post_index < (count($posts) - 1) ? $posts[$post_index + 1] : null;

// Dynamic FAQ Page Schema injection
$faq_schema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => []
];
foreach ($post['faqs'] as $faq) {
    $faq_schema["mainEntity"][] = [
        "@type" => "Question",
        "name" => $faq['q'],
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => $faq['a']
        ]
    ];
}

// Article Schema injection
$article_schema = [
    "@context" => "https://schema.org",
    "@type" => "BlogPosting",
    "mainEntityOfPage" => [
        "@type" => "WebPage",
        "@id" => "https://luxuryhomesofindia.in/blog/" . $post['slug']
    ],
    "headline" => $post['title'],
    "description" => $post['excerpt'],
    "image" => "https://luxuryhomesofindia.in/" . $post['featured_image'],
    "author" => [
        "@type" => "Organization",
        "name" => "Luxury Homes of India"
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => "Luxury Homes of India",
        "logo" => [
            "@type" => "ImageObject",
            "url" => "https://luxuryhomesofindia.in/assets/images/logo/logo_dark.png"
        ]
    ],
    "datePublished" => $post['timestamp'],
    "dateModified" => $post['timestamp']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($post['title']); ?> | Luxury Homes of India</title>
    <?php
    $meta_desc = $post['excerpt'] ?? '';
    if (mb_strlen($meta_desc) > 160) {
        $meta_desc = mb_substr($meta_desc, 0, 157) . '...';
    }
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <link rel="canonical" href="https://luxuryhomesofindia.in/blog/<?php echo $post['slug']; ?>" />
    <link rel="shortcut icon" type="image/x-icon" href="../assets/images/fav.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet preload" href="../assets/css/vendor/bootstrap.min.css" as="style">
    <link rel="stylesheet preload" href="../assets/css/style.css" as="style">
    <link rel="stylesheet" href="../assets/css/premium-popup.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Red+Hat+Display:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet preload" as="style">

    <!-- Schema Markup -->
    <script type="application/ld+json">
    <?php echo json_encode($article_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <script type="application/ld+json">
    <?php echo json_encode($faq_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>

    <style>
        body {
            background-color: var(--pearl-white);
            color: var(--text-dark);
            font-family: 'DM Sans', sans-serif;
            line-height: 1.85;
        }
        
        /* Reading Progress Bar */
        .progress-bar-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 4px;
            z-index: 100002;
            background: rgba(26,26,26,0.05);
        }
        .progress-bar-fill {
            height: 100%; width: 0%;
            background: var(--rich-crimson);
            transition: width 0.1s ease;
        }

        .rts-breadcrumb-area-about {
            background: var(--charcoal-black);
            padding: 80px 0;
            text-align: center;
            border-bottom: 1px solid var(--border-gold);
        }
        .bg-title {
            color: var(--champagne-gold);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }
        .rts-breadcrumb-area-about h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-light);
            margin: 0;
            line-height: 1.3;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
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
        
        /* Sticky TOC */
        .sticky-toc {
            position: sticky;
            top: 100px;
        }
        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc-list li {
            margin-bottom: 12px;
            border-left: 2px solid var(--border-light);
            padding-left: 15px;
        }
        .toc-list li.active {
            border-left-color: var(--champagne-gold);
        }
        .toc-list li a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s;
        }
        .toc-list li.active a, .toc-list li a:hover {
            color: var(--champagne-gold);
        }

        /* Article Details */
        .article-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 15px;
        }
        .article-meta span i {
            color: var(--champagne-gold);
            margin-right: 5px;
        }

        .article-body-wrapper {
            font-size: 16px;
            line-height: 1.85;
            color: var(--text-secondary);
        }
        .article-body-wrapper p {
            margin-bottom: 25px;
        }
        .article-body-wrapper h3 {
            color: var(--text-dark) !important;
            font-weight: 800;
            font-family: 'Red Hat Display', 'Outfit', sans-serif;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .article-body-wrapper table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
            background: #ffffff !important;
            border: 1px solid var(--border-light) !important;
            text-align: left;
            box-shadow: var(--shadow-luxury-light);
        }
        .article-body-wrapper thead tr {
            background: var(--charcoal-black) !important;
            color: var(--text-light) !important;
        }
        .article-body-wrapper tbody tr {
            border-bottom: 1px solid var(--border-light) !important;
        }
        .article-body-wrapper td {
            padding: 12px 18px;
            color: var(--text-secondary) !important;
        }
        .article-body-wrapper tr td:first-child {
            color: var(--text-dark) !important;
            font-weight: 700;
        }
        .article-body-wrapper blockquote {
            background: var(--ivory-white) !important;
            border-left: 3px solid var(--champagne-gold) !important;
            padding: 25px;
            margin: 40px 0;
            border-radius: 4px;
        }
        .article-body-wrapper blockquote p {
            color: var(--text-dark) !important;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 10px;
            font-style: italic;
        }
        .article-body-wrapper blockquote cite {
            color: var(--champagne-gold) !important;
            font-size: 14px;
            font-weight: 500;
        }

        /* Author Profile */
        .author-box {
            display: flex;
            gap: 20px;
            background: var(--ivory-white);
            border: 1px solid var(--border-light);
            padding: 25px;
            border-radius: 8px;
            margin-top: 50px;
            align-items: center;
        }
        .author-box i.author-avatar {
            font-size: 48px;
            color: var(--champagne-gold);
            background: rgba(212, 175, 55, 0.08);
            padding: 15px;
            border-radius: 50%;
        }
        .author-info h5 {
            margin: 0 0 5px 0;
            font-weight: 700;
            color: var(--text-dark);
        }
        .author-info p {
            margin: 0;
            font-size: 15px;
            color: var(--text-secondary);
        }

        /* Next/Prev Navigation */
        .post-navigation {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 50px;
            border-top: 1px solid var(--border-light);
            padding-top: 30px;
        }
        .nav-link-box {
            width: 50%;
            background: #ffffff;
            border: 1px solid var(--border-light);
            padding: 20px;
            border-radius: 6px;
            text-decoration: none !important;
            transition: var(--transition);
            box-shadow: var(--shadow-luxury-light);
        }
        .nav-link-box:hover {
            border-color: var(--champagne-gold);
            transform: translateY(-2px);
        }
        .nav-link-box.next {
            text-align: right;
        }
        .nav-link-box span {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--rich-crimson);
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .nav-link-box h5 {
            margin: 0;
            font-size: 15px;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* FAQ Accordion */
        .faq-item {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-luxury-light);
        }
        .faq-header {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .faq-header h4 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: var(--text-dark);
            font-family: 'Red Hat Display', 'Outfit', sans-serif;
        }
        .faq-header i {
            color: var(--champagne-gold);
            font-size: 12px;
            transition: transform 0.3s;
        }
        .faq-content {
            padding: 0 20px 16px;
            display: none;
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.6;
        }
        .faq-item.active .faq-content {
            display: block;
        }
        .faq-item.active .faq-header i {
            transform: rotate(180deg);
        }

        /* Share Buttons */
        .share-links-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 30px;
        }
        .share-btn {
            width: 36px; height: 36px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none !important;
            transition: opacity 0.2s;
        }
        .share-btn:hover {
            opacity: 0.8;
            color: #fff;
        }
        .share-btn.fb { background: #3b5998; }
        .share-btn.tw { background: #1da1f2; }
        .share-btn.ln { background: #0077b5; }
        .share-btn.wa { background: #25d366; }

        /* Banner CTA */
        .article-banner-cta {
            background: linear-gradient(135deg, var(--rich-crimson) 0%, #b21820 100%);
            border: 1px solid var(--border-gold);
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            margin-top: 50px;
        }

        /* Comments ready box */
        .comments-ready-box {
            border-top: 1px solid var(--border-light);
            padding-top: 40px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Reading Progress Bar -->
    <div class="progress-bar-container">
        <div class="progress-bar-fill" id="progressBar"></div>
    </div>

    <!-- MASTER HEADER -->
    <div id="master-header"></div>

    <!-- BREADCRUMB -->
    <div class="rts-breadcrumb-area-about">
        <div class="container">
            <span class="bg-title"><?php echo $post['category']; ?></span>
            <h1><?php echo $post['title']; ?></h1>
        </div>
    </div>

    <!-- MAIN BODY -->
    <div class="container pt--80 pb--80">
        <div class="row g-5">
            <div class="col-lg-8">
                
                <!-- Meta tags -->
                <div class="article-meta">
                    <span><i class="far fa-circle-user"></i> <?php echo $post['author']; ?></span>
                    <span><i class="far fa-clock"></i> <?php echo $post['publish_date']; ?></span>
                    <span><i class="far fa-tags"></i> <?php echo $post['category']; ?></span>
                </div>

                <!-- Featured Image -->
                <div style="border-radius:8px; overflow:hidden; border:1px solid var(--border-light); margin-bottom:40px; box-shadow: var(--shadow-luxury-light);">
                    <img src="../<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width:100%; height:auto; display:block;">
                </div>

                <!-- Article Content -->
                <article class="article-body-wrapper">
                    <?php echo $content_with_ids; ?>
                </article>

                <!-- Social Share Block -->
                <div class="share-links-wrapper">
                    <span style="font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:var(--champagne-gold); margin-right:15px;">Share Post:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://luxuryhomesofindia.in/blog/'.$post['slug']); ?>" target="_blank" class="share-btn fb"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://luxuryhomesofindia.in/blog/'.$post['slug']); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" class="share-btn tw"><i class="fab fa-x-twitter"></i></a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://luxuryhomesofindia.in/blog/'.$post['slug']); ?>&title=<?php echo urlencode($post['title']); ?>" target="_blank" class="share-btn ln"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . ' - https://luxuryhomesofindia.in/blog/'.$post['slug']); ?>" target="_blank" class="share-btn wa"><i class="fab fa-whatsapp"></i></a>
                </div>

                <!-- Author Bio Box -->
                <div class="author-box">
                    <i class="far fa-user-tie author-avatar"></i>
                    <div class="author-info">
                        <h5>Written by <?php echo $post['author']; ?></h5>
                        <p>Luxury design consultant and engineering auditor at Luxury Homes of India. Expert in high-end structural blueprints and premium material selections across South India.</p>
                    </div>
                </div>

                <!-- FAQ Accordion -->
                <?php if (!empty($post['faqs'])): ?>
                    <div style="margin-top:50px;">
                        <h3 style="color:var(--text-dark); font-size:1.6rem; font-weight:800; margin-bottom:25px; font-family:'Red Hat Display', 'Outfit', sans-serif;">Frequently Asked Questions</h3>
                        <div class="faq-container">
                            <?php foreach ($post['faqs'] as $idx => $faq): ?>
                                <div class="faq-item">
                                    <div class="faq-header" onclick="toggleFaq(<?php echo $idx; ?>)">
                                        <h4><?php echo $faq['q']; ?></h4>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div id="faq-answer-<?php echo $idx; ?>" class="faq-content">
                                        <p><?php echo $faq['a']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Next/Prev Navigation -->
                <div class="post-navigation">
                    <?php if ($prev_post): ?>
                        <a href="<?php echo $prev_post['slug']; ?>" class="nav-link-box prev">
                            <span>Previous Article</span>
                            <h5><?php echo $prev_post['title']; ?></h5>
                        </a>
                    <?php else: ?>
                        <div style="width:50%;"></div>
                    <?php endif; ?>

                    <?php if ($next_post): ?>
                        <a href="<?php echo $next_post['slug']; ?>" class="nav-link-box next">
                            <span>Next Article</span>
                            <h5><?php echo $next_post['title']; ?></h5>
                        </a>
                    <?php else: ?>
                        <div style="width:50%;"></div>
                    <?php endif; ?>
                </div>

                <!-- Banner CTA -->
                <div class="article-banner-cta">
                    <h3 style="color:var(--text-light) !important; font-weight:800; font-size:1.6rem; margin-bottom:10px; font-family:'Red Hat Display', 'Outfit', sans-serif;">Ready to Plan Your Dream Villa?</h3>
                    <p style="color:rgba(255,255,255,0.85); font-size:0.95rem; margin-bottom:25px; max-width:550px; margin-left:auto; margin-right:auto;">Connect with our design desks for a preliminary estimate, Vastu analysis, and customized floor layouts.</p>
                    <a href="javascript:void(0)" class="rts-btn btn-primary open-lead-popup" style="padding:15px 35px; text-decoration:none; background:var(--champagne-gold) !important; color:var(--charcoal-black) !important; font-weight:700 !important; border-radius:4px; text-transform:uppercase; letter-spacing:1px; border:none;">Book Free Architect Call</a>
                </div>

                <!-- Comments Ready Architecture -->
                <div class="comments-ready-box">
                    <h4 style="color:var(--text-dark); font-weight:800; margin-bottom:20px; font-size:1.3rem; font-family:'Red Hat Display', 'Outfit', sans-serif;">Discussion & Comments (0)</h4>
                    <div style="background:var(--ivory-white); border:1px solid var(--border-light); padding:25px; border-radius:6px; text-align:center; color:var(--text-secondary); box-shadow:var(--shadow-luxury-light);">
                        <i class="far fa-comments fa-2x" style="color:var(--champagne-gold); margin-bottom:10px;"></i>
                        <p style="font-size:0.9rem; margin:0;">Comments have been disabled for this article. Submit your enquiries directly via the contact desk.</p>
                    </div>
                </div>

            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <aside class="sticky-toc">
                    <!-- Dynamic Table of Contents -->
                    <?php if (!empty($toc_items)): ?>
                        <div class="blog-sidebar">
                            <h4 class="sidebar-widget-title">Table of Contents</h4>
                            <ul class="toc-list" id="tocList">
                                <?php foreach ($toc_items as $item): ?>
                                    <li><a href="#<?php echo $item['id']; ?>"><?php echo $item['text']; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Related Articles -->
                    <?php if (!empty($related_articles)): ?>
                        <div class="blog-sidebar">
                            <h4 class="sidebar-widget-title">Related Guides</h4>
                            <ul class="cat-list" style="display:block;">
                                <?php foreach ($related_articles as $rel): ?>
                                    <li style="margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 10px;">
                                        <a href="<?php echo $rel['slug']; ?>" style="display:block; color:#fff; font-weight:600; font-size:0.9rem; line-height:1.4; margin-bottom:5px;">
                                            <?php echo $rel['title']; ?>
                                        </a>
                                        <span style="font-size:0.75rem; color:#888;"><?php echo $rel['publish_date']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Services Links -->
                    <div class="blog-sidebar">
                        <h4 class="sidebar-widget-title">Our Services</h4>
                        <ul class="cat-list">
                            <li><a href="..//chennai/luxury-home-construction/"><i class="fas fa-arrow-right" style="font-size:10px; color:#c6a25a; margin-right:8px;"></i> Civil Engineering</a></li>
                            <li><a href="..//chennai/architecture/"><i class="fas fa-arrow-right" style="font-size:10px; color:#c6a25a; margin-right:8px;"></i> Architectural Planning</a></li>
                            <li><a href="..//chennai/interior-design/"><i class="fas fa-arrow-right" style="font-size:10px; color:#c6a25a; margin-right:8px;"></i> Luxury Interiors</a></li>
                            <li><a href="..//chennai/smart-home/"><i class="fas fa-arrow-right" style="font-size:10px; color:#c6a25a; margin-right:8px;"></i> Smart Home Tech</a></li>
                            <li><a href="..//chennai/turnkey-home-construction/"><i class="fas fa-arrow-right" style="font-size:10px; color:#c6a25a; margin-right:8px;"></i> Turnkey Projects</a></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <!-- MASTER FOOTER -->
    <div id="master-footer"></div>

    <!-- PREMIUM LEAD CAPTURE FORM POPUP MARKUP -->
    <div id="premiumLeadPopupOverlay">
        <div class="premium-popup">
            <button class="premium-popup-close">&times;</button>
            <div class="premium-popup-content">
                <h3>Build Your Dream Luxury Home</h3>
                <p class="popup-sub">Book a FREE consultation, concept discussion, and preliminary construction estimate with our luxury home specialists.</p>
                
                <form id="premiumLeadForm" action="" method="POST">
                    <input type="text" name="website_url_check" style="display:none !important;" tabindex="-1" autocomplete="off">
                    <input type="hidden" name="utm_source" value="">
                    <input type="hidden" name="utm_medium" value="">
                    <input type="hidden" name="utm_campaign" value="">
                    <input type="hidden" name="utm_term" value="">
                    <input type="hidden" name="referrer_url" value="">
                    <input type="hidden" name="landing_page_url" value="">

                    <div class="premium-form-group">
                        <label class="group-label">Personal Details</label>
                        <div class="premium-form-row">
                            <input type="text" name="name" placeholder="Full Name *" required>
                            <input type="tel" name="mobile" placeholder="Mobile Number *" required>
                        </div>
                        <input type="email" name="email" placeholder="Email Address *" required style="margin-top:12px;">
                    </div>

                    <div class="premium-form-group" style="margin-top:25px;">
                        <label class="group-label">Project Details</label>
                        <div class="premium-form-row">
                            <input type="text" name="project_location" placeholder="Project Location *" required>
                            <input type="text" name="plot_location" placeholder="Plot Location (e.g. Layout, Sector)">
                        </div>
                        <div class="premium-form-row" style="margin-top:12px;">
                            <input type="text" name="plot_size" placeholder="Plot Size (e.g. 40x60, 2400 sqft)">
                            <input type="text" name="built_up_area" placeholder="Proposed Built-up Area (sqft)">
                        </div>
                    </div>

                    <div class="premium-form-group" style="margin-top:25px;">
                        <div class="premium-form-row">
                            <div>
                                <label class="group-label">Approx Budget</label>
                                <select name="budget" required>
                                    <option value="">Select Budget Range</option>
                                    <option value="₹50L–₹1Cr">₹50L–₹1Cr</option>
                                    <option value="₹1Cr–₹2Cr">₹1Cr–₹2Cr</option>
                                    <option value="₹2Cr–₹5Cr">₹2Cr–₹5Cr</option>
                                    <option value="₹5Cr+">₹5Cr+</option>
                                </select>
                            </div>
                            <div>
                                <label class="group-label">Timeline</label>
                                <select name="timeline" required>
                                    <option value="">Expected Start</option>
                                    <option value="Immediately">Immediately</option>
                                    <option value="Within 1 Month">Within 1 Month</option>
                                    <option value="Within 3 Months">Within 3 Months</option>
                                    <option value="Within 6 Months">Within 6 Months</option>
                                    <option value="Planning Stage">Planning Stage</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="premium-form-group" style="margin-top:25px;">
                        <label class="group-label">Services Required</label>
                        <div class="services-checkbox-grid">
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Luxury Home Construction"> Luxury Home Construction</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Luxury Villa Construction" checked> Luxury Villa Construction</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Architecture"> Architecture</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Structural Design"> Structural Design</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Turnkey Construction"> Turnkey Construction</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Interior Design"> Interior Design</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Renovation"> Renovation</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Swimming Pool"> Swimming Pool</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Landscape"> Landscape</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Smart Home"> Smart Home Integration</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Home Automation"> Home Automation</label>
                            <label class="checkbox-label"><input type="checkbox" name="services[]" value="Other"> Other</label>
                        </div>
                    </div>

                    <div class="premium-form-group" style="margin-top:25px;">
                        <label class="group-label">Additional Notes</label>
                        <textarea name="notes" placeholder="Describe any specific design details, basement requirements, or structural needs..."></textarea>
                    </div>

                    <label class="consent-container">
                        <input type="checkbox" name="consent" checked required>
                        <span class="consent-text">I agree to be contacted by Luxury Homes of India regarding my construction requirements.</span>
                    </label>

                    <button type="submit">Submit Enquiry</button>
                </form>
                <div id="premiumLeadMsg" style="margin-top:15px;"></div>
            </div>
        </div>
    </div>

    <!-- JS Core Scripts -->
    <script defer src="../assets/js/plugins/jquery.js"></script>
    <script defer src="../assets/js/vendor/bootstrap.min.js"></script>
    <script defer src="../assets/js/plugins/metismenu.js"></script>
    <script defer src="../assets/js/main.js"></script>
    <script defer src="../assets/js/popup-trigger.js"></script>
    <script defer src="../assets/js/analytics-tracking.js"></script>

    <!-- Load Header/Footer and Scroll progress -->
    <script>
        fetch("../header.html")
            .then(res => res.text())
            .then(data => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data;
                
                // Fix relative paths in header
                tempDiv.querySelectorAll('a').forEach(a => {
                    const href = a.getAttribute('href');
                    if (href && !href.startsWith('http') && !href.startsWith('#') && !href.startsWith('tel:') && !href.startsWith('mailto:')) {
                        a.setAttribute('href', '../' + href);
                    }
                });
                tempDiv.querySelectorAll('img').forEach(img => {
                    const src = img.getAttribute('src');
                    if (src && !src.startsWith('http') && !src.startsWith('data:')) {
                        img.setAttribute('src', '../' + src);
                    }
                });
                
                document.getElementById('master-header').innerHTML = tempDiv.innerHTML;
                if (window.rtsJs && typeof window.rtsJs.sideMenu === 'function') {
                    window.rtsJs.sideMenu();
                }
            });

        fetch("../footer.html")
            .then(res => res.text())
            .then(data => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data;
                
                // Fix relative paths in footer
                tempDiv.querySelectorAll('a').forEach(a => {
                    const href = a.getAttribute('href');
                    if (href && !href.startsWith('http') && !href.startsWith('#') && !href.startsWith('tel:') && !href.startsWith('mailto:')) {
                        a.setAttribute('href', '../' + href);
                    }
                });
                tempDiv.querySelectorAll('img').forEach(img => {
                    const src = img.getAttribute('src');
                    if (src && !src.startsWith('http') && !src.startsWith('data:')) {
                        img.setAttribute('src', '../' + src);
                    }
                });
                document.getElementById('master-footer').innerHTML = tempDiv.innerHTML;
            });

        // Toggle Accordion answers
        function toggleFaq(index) {
            const item = document.getElementById('faq-answer-' + index).parentNode;
            const wasActive = item.classList.contains('active');
            
            document.querySelectorAll('.faq-item').forEach(i => {
                i.classList.remove('active');
                i.querySelector('.faq-content').style.display = 'none';
            });
            
            if (!wasActive) {
                item.classList.add('active');
                document.getElementById('faq-answer-' + index).style.display = 'block';
            }
        }

        // Reading progress calculation
        window.addEventListener('scroll', function() {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            if (scrollHeight > 0) {
                const scrollPercent = (scrollTop / scrollHeight) * 100;
                document.getElementById('progressBar').style.width = scrollPercent + '%';
            }
        });

        // Simple TOC highlight tracking
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('article h3');
            const scrollPos = window.scrollY || document.documentElement.scrollTop;
            let currentActive = '';
            
            sections.forEach(sec => {
                const secTop = sec.offsetTop - 150;
                if (scrollPos >= secTop) {
                    currentActive = sec.getAttribute('id');
                }
            });
            
            if (currentActive) {
                const tocLinks = document.querySelectorAll('#tocList li');
                tocLinks.forEach(li => {
                    li.classList.remove('active');
                    const link = li.querySelector('a');
                    if (link.getAttribute('href') === '#' + currentActive) {
                        li.classList.add('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
