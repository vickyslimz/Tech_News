<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /php-blog/login.php");
    exit;
}

$pageTitle = "Manage Advertisements";

// Get all ads
try {
    $ads = $pdo->query("SELECT * FROM advertisements ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../sidebar.php'; ?>
    
    <main class="flex-1 p-8 bg-gray-50">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Manage Advertisements</h1>
            <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Create New Ad
            </a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <?php if (!empty($ads)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 border">Title</th>
                                <th class="px-4 py-2 border">Sponsor</th>
                                <th class="px-4 py-2 border">Status</th>
                                <th class="px-4 py-2 border">Expires</th>
                                <th class="px-4 py-2 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border"><?= htmlspecialchars($ad['title']) ?></td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($ad['sponsor_name'] ?? 'N/A') ?></td>
                                <td class="px-4 py-2 border">
                                    <span class="px-2 py-1 text-xs rounded-full <?= $ad['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $ad['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 border">
                                    <?= $ad['valid_until'] ? formatDate($ad['valid_until']) : 'Never' ?>
                                </td>
                                <td class="px-4 py-2 border text-center space-x-2">
                                    <a href="edit.php?id=<?= $ad['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                                    <form action="toggle.php" method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                        <button type="submit" class="text-<?= $ad['is_active'] ? 'red' : 'green' ?>-600 hover:underline">
                                            <?= $ad['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No advertisements found.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>