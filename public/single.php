<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Start session at the very beginning
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Redirect to login page with return URL
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../public/login.php");
    exit;
}

// Check if user has required role (admin, reader, or author)
$allowed_roles = ['admin', 'reader', 'author'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    // User doesn't have required role
    header("Location: ../public/unauthorized.php");
    exit;
}

if (!isset($_GET['slug'])) {
    header("Location: /");
    exit;
}

$slug = $_GET['slug'];

try {
    // Get the post
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE p.slug = ?");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: /");
        exit;
    }

    $pageTitle = $post['title'];

    // Get categories for this post
    $stmt = $pdo->prepare("SELECT c.name, c.slug FROM categories c
                          JOIN post_categories pc ON c.id = pc.category_id
                          WHERE pc.post_id = ?");
    $stmt->execute([$post['id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get comments for this post
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.role 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? AND c.is_approved = TRUE AND c.parent_id IS NULL 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post['id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get replies for each comment
    foreach ($comments as &$comment) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.role 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.parent_id = ? AND c.is_approved = TRUE 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$comment['id']]);
        $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
        $content = trim($_POST['comment_content']);
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if (!empty($content)) {
            $stmt = $pdo->prepare("
                INSERT INTO comments (post_id, user_id, content, parent_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$post['id'], $_SESSION['user_id'], $content, $parent_id]);

            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
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

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <article class="bg-purple-600 shadow-xl rounded-lg overflow-hidden mt-8">
        <!-- Header Section with Image and Title Side by Side -->
        <div class="flex flex-col lg:flex-row lg:items-start lg:gap-8">
            <!-- Post Image (if available) -->
            <?php if (!empty($post['image'])): ?>
                <div class="lg:w-1/2 w-full flex-shrink-0">
                    <img src="<?= htmlspecialchars($post['image']) ?>"
                        alt="<?= htmlspecialchars($post['title']) ?>"
                        class="w-full h-auto object-cover lg:h-96 rounded-lg">
                </div>
            <?php endif; ?>

            <!-- Title and Meta Information -->
            <div class="lg:w-1/2 w-full p-6 lg:p-8 flex flex-col justify-center">
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4 leading-tight">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>

                <!-- Meta information -->
                <div class="space-y-3 text-sm text-gray-600 mb-6">
                    <div class="flex items-center">
                        <span class="font-medium">By <?= htmlspecialchars($post['username']) ?></span>
                    </div>

                    <div class="flex items-center">
                        <span><?= formatDate($post['created_at']) ?></span>
                    </div>

                    <?php if (!empty($categories)): ?>
                        <div class="flex items-center flex-wrap gap-2">
                            <?php foreach ($categories as $cat): ?>
                                <a href="/php-blog/public/category.php?slug=<?= $cat['slug'] ?>"
                                    class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded hover:bg-blue-200 transition">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Excerpt (if available) -->
                <?php if (!empty($post['excerpt'])): ?>
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border-l-4 border-blue-500">
                        <p class="text-lg italic text-gray-700"><?= htmlspecialchars($post['excerpt']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <!-- Post Content -->
        <div class="p-6 lg:p-8 border-t border-gray-200">
            <div class="prose prose-lg max-w-none text-gray-800 leading-relaxed">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
            <!-- Comments Section -->
            <div class="max-w-4xl mx-auto mt-12 bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Comments</h2>

                <!-- Comment Form -->
                <div class="mb-8">
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="comment_content" class="block text-sm font-medium text-gray-700 mb-2">
                                Add a comment as <?= htmlspecialchars($_SESSION['username']) ?>
                            </label>
                            <textarea
                                id="comment_content"
                                name="comment_content"
                                rows="4"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Share your thoughts..."></textarea>
                        </div>
                        <button
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition">
                            Post Comment
                        </button>
                    </form>
                </div>

                <!-- Comments List -->
                <div class="space-y-6">
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <!-- Main Comment -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center space-x-3">
                                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($comment['username']) ?></span>
                                            <?php if ($comment['role'] === 'admin'): ?>
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Admin</span>
                                            <?php elseif ($comment['role'] === 'author'): ?>
                                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Author</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-sm text-gray-500"><?= formatDate($comment['created_at']) ?></span>
                                    </div>
                                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>

                                    <!-- Reply Button -->
                                    <button
                                        onclick="toggleReplyForm(<?= $comment['id'] ?>)"
                                        class="mt-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        <i class="fas fa-reply mr-1"></i>Reply
                                    </button>

                                    <!-- Reply Form (Hidden by default) -->
                                    <div id="reply-form-<?= $comment['id'] ?>" class="mt-4 hidden">
                                        <form method="POST" class="space-y-3">
                                            <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                            <textarea
                                                name="comment_content"
                                                rows="2"
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Write a reply..."></textarea>
                                            <div class="flex space-x-2">
                                                <button
                                                    type="submit"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1 px-4 rounded-lg transition">
                                                    Post Reply
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick="toggleReplyForm(<?= $comment['id'] ?>)"
                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm font-medium py-1 px-4 rounded-lg transition">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Replies -->
                                <?php if (count($comment['replies']) > 0): ?>
                                    <div class="ml-8 mt-4 space-y-4">
                                        <?php foreach ($comment['replies'] as $reply): ?>
                                            <div class="border-l-2 border-gray-300 pl-4">
                                                <div class="bg-white p-3 rounded-lg">
                                                    <div class="flex items-start justify-between mb-2">
                                                        <div class="flex items-center space-x-3">
                                                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($reply['username']) ?></span>
                                                            <?php if ($reply['role'] === 'admin'): ?>
                                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Admin</span>
                                                            <?php elseif ($reply['role'] === 'author'): ?>
                                                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Author</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="text-sm text-gray-500"><?= formatDate($reply['created_at']) ?></span>
                                                    </div>
                                                    <p class="text-gray-700 text-sm"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-comments text-4xl mb-4"></i>
                            <p>No comments yet. Be the first to share your thoughts!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                function toggleReplyForm(commentId) {
                    const form = document.getElementById('reply-form-' + commentId);
                    form.classList.toggle('hidden');
                }
            </script>

            <!-- Share buttons -->
            <div class="mt-10 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Share this post:</h3>
                <div class="flex space-x-4">
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>"
                        target="_blank"
                        class="text-blue-500 hover:text-blue-700 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>"
                        target="_blank"
                        class="text-blue-800 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>&title=<?= urlencode($post['title']) ?>"
                        target="_blank"
                        class="text-blue-700 hover:text-blue-500 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </article>
</div>

<!-- Dynamic Advertisement Section -->
<?php if ($ad): ?>
    <section class="my-8 bg-[#edf1ef]"> <!-- Horizontal padding for side spacing -->
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