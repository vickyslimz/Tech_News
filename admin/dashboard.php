<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /php-blog/login.php");
    exit;
}

$pageTitle = "Admin Dashboard";

// Get stats
try {
    $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $recentPosts = $pdo->query("SELECT id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-8 bg-gray-50">
        <h1 class="text-3xl font-bold mb-6">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></h1>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <h3 class="text-lg font-semibold text-gray-600">Total Posts</h3>
                <p class="text-3xl font-bold text-blue-600"><?= $totalPosts ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <h3 class="text-lg font-semibold text-gray-600">Total Users</h3>
                <p class="text-3xl font-bold text-green-600"><?= $totalUsers ?></p>
            </div>
        </div>

        <!-- Recent Posts -->
        <section class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Recent Posts</h2>
            <?php if (!empty($recentPosts)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 border">Title</th>
                                <th class="px-4 py-2 border">Date</th>
                                <th class="px-4 py-2 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $post): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 border"><?= htmlspecialchars($post['title']) ?></td>
                                    <td class="px-4 py-2 border"><?= formatDate($post['created_at']) ?></td>
                                    <td class="px-4 py-2 border text-center">
                                        <a href="/php-blog/admin/posts/edit.php?id=<?= $post['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No posts found.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>