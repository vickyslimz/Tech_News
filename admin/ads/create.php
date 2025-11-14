<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /php-blog/login.php");
    exit;
}

$pageTitle = "Create Advertisement";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data (similar to previous upload_ad.php example)
    // Include validation and file upload handling
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../sidebar.php'; ?>
    
    <main class="flex-1 p-8 bg-gray-50">
        <h1 class="text-3xl font-bold mb-6">Create New Advertisement</h1>
        
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md space-y-4">
            <!-- Form fields (same as previous example) -->
            <!-- ... -->
            
            <div class="flex justify-end space-x-4 pt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Create Advertisement
                </button>
                <a href="manage.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>