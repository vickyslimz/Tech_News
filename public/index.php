<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$pageTitle = "Home";
$postsPerPage = 5;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $postsPerPage;

// Fetch ads - Improved random selection
try {
    $currentDateTime = date('Y-m-d H:i:s');

    // First, count available ads
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM advertisements 
        WHERE is_active = TRUE 
        AND (valid_until IS NULL OR valid_until >= :currentDateTime)
    ");
    $countStmt->bindParam(':currentDateTime', $currentDateTime);
    $countStmt->execute();
    $adCount = $countStmt->fetchColumn();

    if ($adCount > 0) {
        // Pick a random offset
        $randomOffset = mt_rand(0, $adCount - 1);

        $adStmt = $pdo->prepare("
            SELECT * FROM advertisements 
            WHERE is_active = TRUE 
            AND (valid_until IS NULL OR valid_until >= :currentDateTime)
            LIMIT 1 OFFSET :offset
        ");
        $adStmt->bindParam(':currentDateTime', $currentDateTime);
        $adStmt->bindParam(':offset', $randomOffset, PDO::PARAM_INT);
        $adStmt->execute();
        $ad = $adStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $ad = null;
    }
} catch (PDOException $e) {
    error_log("Error fetching ads: " . $e->getMessage());
    $ad = null;
}

// Fetch blog posts
try {
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $postsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $totalPages = ceil($totalPosts / $postsPerPage);
} catch (PDOException $e) {
    die("Error fetching posts: " . $e->getMessage());
}

// PRELOAD CATEGORIES FOR ALL POSTS IN BULK
$categoriesByPostId = [];
if (!empty($posts)) {
    $postIds = array_column($posts, 'id');
    $placeholders = str_repeat('?,', count($postIds) - 1) . '?';

    $categoryStmt = $pdo->prepare("
        SELECT c.*, pc.post_id 
        FROM categories c
        JOIN post_categories pc ON c.id = pc.category_id
        WHERE pc.post_id IN ($placeholders)
        ORDER BY c.name
    ");
    $categoryStmt->execute($postIds);
    $allCategories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group categories by post_id
    foreach ($allCategories as $category) {
        $categoriesByPostId[$category['post_id']][] = $category;
    }
}

// Fetch featured posts (latest 5)
try {
    $featuredStmt = $pdo->prepare("
        SELECT p.* 
        FROM posts p
        JOIN featured_posts f ON f.post_id = p.id
        ORDER BY f.featured_at DESC
        LIMIT 5
    ");
    $featuredStmt->execute();
    $featured_posts = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching featured posts: " . $e->getMessage());
    $featured_posts = [];
}

include '../includes/header.php';
?>


<div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
    <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded">
            You have been logged out successfully.
        </div>
    <?php endif; ?>
    <!-- Dynamic Advertisement Section -->
    <?php if ($ad): ?>
        <section class="mb-8 bg-[#edf1ef]"> <!-- Horizontal padding for side spacing -->
            <div class="mx-auto"> <!-- Centered container with max-width -->
                <div class="bg-[#edf1ef] ">
                    <a href="<?= htmlspecialchars($ad['cta_link']) ?>"
                        target="_blank"
                        rel="nofollow sponsored"
                        class="block">
                        <img src="<?= htmlspecialchars($ad['image']) ?>"
                            alt="<?= htmlspecialchars($ad['alt_text'] ?? 'Sponsored Content') ?>"
                            class="w-full h-auto object-contain mx-auto"
                            style="max-height: 180px;"> <!-- Adjust height as needed -->
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Blog Posts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-5  gap-8">
        <section class="mb-16 lg:col-span-4">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold mb-6 pb-2 border-b border-gray-200">Latest News</h2>

                <?php if ($posts): ?>
                    <div class="grid gap-8">
                        <?php foreach ($posts as $post): ?>
                            <?php $postUrl = './single.php?slug=' . rawurlencode((string)$post['slug']); ?>
                            <article class="relative group">
                                <!-- Image with title overlay -->
                                <?php if (!empty($post['image'])): ?>
                                    <div class="relative rounded-lg overflow-hidden h-64">
                                        <img src="<?= htmlspecialchars($post['image']) ?>"
                                            alt="<?= htmlspecialchars($post['title']) ?>"
                                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">

                                        <!-- Title overlay -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end p-6">
                                            <h2 class="text-2xl font-bold text-white">
                                                <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" class="hover:underline">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </a>
                                            </h2>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Post meta and excerpt below image -->
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 mb-2">
                                        Posted on <?= formatDate($post['created_at']) ?>
                                        <?php
                                        // Use preloaded categories instead of individual queries
                                        $categories = $categoriesByPostId[$post['id']] ?? [];
                                        if ($categories): ?>
                                            <span class="ml-2">
                                                <?php foreach ($categories as $cat): ?>
                                                    <?php $categorySlug = isset($cat['slug']) ? (string)$cat['slug'] : ''; ?>
                                                    <?php $categoryUrl = '/php-blog/public/category.php?slug=' . rawurlencode($categorySlug); ?>
                                                    <a href="<?= htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                        class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded hover:bg-blue-200 transition">
                                                        <?= htmlspecialchars($cat['name'] ?? 'Uncategorized') ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-gray-700 mb-4">
                                        <?= htmlspecialchars($post['excerpt']) ?>
                                    </p>
                                    <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-block text-blue-600 font-medium hover:underline">
                                        <p>Read full story <?php if (!isset($_SESSION['user_id'])): ?>
                                                (Login Required)
                                            <?php endif; ?> →
                                        </p>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-600">No blog posts yet.</p>
                <?php endif; ?>
            </div>
        </section>
        <aside class="lg:col-span-1">
            <section class="bg-purple-600 text-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold">Featured Posts</h2>
                    <div class="bg-yellow-300 p-2 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    </div>
                </div>
                <ul class="space-y-4">
                    <?php if ($featured_posts): ?>
                        <?php foreach ($featured_posts as $p): ?>
                            <?php $featuredPostUrl = './single.php?slug=' . rawurlencode((string)$p['slug']); ?>
                            <li class="border-b border-purple-400 pb-2">
                                <a href="<?= htmlspecialchars($featuredPostUrl, ENT_QUOTES, 'UTF-8') ?>" class="block hover:underline">
                                    <?= htmlspecialchars($p['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-purple-200">No featured posts yet.</li>
                    <?php endif; ?>
                </ul>
            </section>
        </aside>
    </div>

    <!-- Pagination-->
    <?php if ($totalPages > 1): ?>
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-center mt-8 space-x-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Dynamic Advertisement Section -->
<?php if ($ad): ?>
    <section class=" bg-[#edf1ef]"> <!-- Horizontal padding for side spacing -->
        <div class="mx-auto"> <!-- Centered container with max-width -->
            <div class="bg-[#edf1ef] ">
                <a href="<?= htmlspecialchars($ad['cta_link']) ?>"
                    target="_blank"
                    rel="nofollow sponsored"
                    class="block">
                    <img src="<?= htmlspecialchars($ad['image']) ?>"
                        alt="<?= htmlspecialchars($ad['alt_text'] ?? 'Sponsored Content') ?>"
                        class="w-full h-auto object-contain mx-auto"
                        style="max-height: 180px;"> <!-- Adjust height as needed -->
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php include '../includes/footer.php'; ?>
