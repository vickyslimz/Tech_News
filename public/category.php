<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if category slug is provided
if (!isset($_GET['slug'])) {
    header("Location: /php-blog/public");
    exit;
}

$category_slug = $_GET['slug'];
$pageTitle = "Category";
$postsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $postsPerPage;

try {
    // Get category information
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$category_slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        header("Location: /php-blog/public");
        exit;
    }
    
    $pageTitle = $category['name'] . " - Category";
    
    // Get posts in this category - FIXED: Use consistent parameter style
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        JOIN post_categories pc ON p.id = pc.post_id 
        WHERE pc.category_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $category['id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $postsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total posts count for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM posts p 
        JOIN post_categories pc ON p.id = pc.post_id 
        WHERE pc.category_id = ?
    ");
    $stmt->execute([$category['id']]);
    $totalPosts = $stmt->fetchColumn();
    $totalPages = ceil($totalPosts / $postsPerPage);
    
    // Get all categories for sidebar
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error fetching category data: " . $e->getMessage());
}

// Fetch ads 
try {
    $currentDateTime = date('Y-m-d H:i:s');
    $adStmt = $pdo->prepare("
        SELECT * FROM advertisements 
        WHERE is_active = TRUE 
        AND (valid_until IS NULL OR valid_until >= :currentDateTime)
        ORDER BY RAND() 
        LIMIT 1
    ");
    $adStmt->bindParam(':currentDateTime', $currentDateTime);
    $adStmt->execute();
    $ad = $adStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching ads: " . $e->getMessage());
    $ad = null;
}

include '../includes/header.php';
?>

    <!-- Dynamic Advertisement Section -->
    <?php if ($ad): ?>
        <section class="mb-8 bg-[#edf1ef]">
            <div class="mx-auto">
                <div class="bg-[#edf1ef]">
                    <a href="<?= htmlspecialchars($ad['cta_link']) ?>"
                        target="_blank"
                        rel="nofollow sponsored"
                        class="block">
                        <img src="<?= htmlspecialchars($ad['image']) ?>"
                            alt="<?= htmlspecialchars($ad['alt_text'] ?? 'Sponsored Content') ?>"
                            class="w-full h-auto object-contain mx-auto"
                            style="max-height: 180px;">
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content -->
        <main class="lg:w-3/4">
            <!-- Category Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($category['name']) ?></h1>
                        <?php if (!empty($category['description'])): ?>
                            <p class="text-gray-600"><?= htmlspecialchars($category['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                        <?= $totalPosts ?> post<?= $totalPosts !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <!-- Posts Grid -->
            <?php if ($posts): ?>
                <div class="grid gap-6 md:grid-cols-2">
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <?php if (!empty($post['image'])): ?>
                                <div class="h-48 overflow-hidden">
                                    <img src="<?= htmlspecialchars($post['image']) ?>" 
                                         alt="<?= htmlspecialchars($post['title']) ?>" 
                                         class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-gray-900 mb-3">
                                    <a href="./single.php?slug=<?= htmlspecialchars($post['slug']) ?>" 
                                       class="hover:text-blue-600 transition-colors">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h2>
                                
                                <p class="text-gray-600 mb-4 line-clamp-3">
                                    <?= htmlspecialchars($post['excerpt'] ?? substr($post['content'], 0, 150) . '...') ?>
                                </p>
                                
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?= formatDate($post['created_at']) ?></span>
                                    <span>By <?= htmlspecialchars($post['username']) ?></span>
                                </div>
                                <a href="./single.php?slug=<?= htmlspecialchars($post['slug']) ?>" 
                                   class="mt-4 inline-block text-blue-600 font-medium hover:text-blue-800 transition-colors">
                                    Read more →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <div class="text-gray-400 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No posts found</h3>
                    <p class="text-gray-500">There are no posts in this category yet.</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="?slug=<?= $category_slug ?>&page=<?= $currentPage - 1 ?>" 
                               class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?slug=<?= $category_slug ?>&page=<?= $i ?>" 
                               class="px-3 py-2 rounded border <?= $i === $currentPage ? 'bg-blue-600 border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?slug=<?= $category_slug ?>&page=<?= $currentPage + 1 ?>" 
                               class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </main>

        <!-- Sidebar -->
        <aside class="lg:w-1/4">
            <!-- Categories List -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">All Categories</h3>
                <ul class="space-y-2">
                    <?php foreach ($all_categories as $cat): ?>
                        <li>
                            <a href="?slug=<?= $cat['slug'] ?>" 
                               class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors <?= $cat['slug'] === $category_slug ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' ?>">
                                <span><?= htmlspecialchars($cat['name']) ?></span>
                                <?php 
                                // Get post count for each category
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_categories WHERE category_id = ?");
                                $stmt->execute([$cat['id']]);
                                $post_count = $stmt->fetchColumn();
                                ?>
                                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                    <?= $post_count ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Popular Posts in this Category -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Popular in <?= htmlspecialchars($category['name']) ?></h3>
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT p.* 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        WHERE pc.category_id = ? 
                        ORDER BY p.created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->execute([$category['id']]);
                    $popular_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $popular_posts = [];
                }
                ?>
                
                <?php if ($popular_posts): ?>
                    <ul class="space-y-3">
                        <?php foreach ($popular_posts as $post): ?>
                            <li class="border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                                <a href="./single.php?slug=<?= htmlspecialchars($post['slug']) ?>" 
                                   class="text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                                <p class="text-xs text-gray-500 mt-1"><?= formatDate($post['created_at']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No popular posts yet.</p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

    <!-- Dynamic Advertisement Section -->
    <?php if ($ad): ?>
        <section class="my-8 bg-[#edf1ef]">
            <div class="mx-auto">
                <div class="bg-[#edf1ef]">
                    <a href="<?= htmlspecialchars($ad['cta_link']) ?>"
                        target="_blank"
                        rel="nofollow sponsored"
                        class="block">
                        <img src="<?= htmlspecialchars($ad['image']) ?>"
                            alt="<?= htmlspecialchars($ad['alt_text'] ?? 'Sponsored Content') ?>"
                            class="w-full h-auto object-contain mx-auto"
                            style="max-height: 180px;">
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php include '../includes/footer.php'; ?>