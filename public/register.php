<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!isValidCsrfToken($postedToken)) {
        $errors[] = "Invalid or expired form token. Please try again.";
        rotateCsrfToken();
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $fieldError = null;
        if (!validateUsername($username, $fieldError)) {
            $errors[] = $fieldError;
        }

        if (!validateEmailAddress($email, $fieldError)) {
            $errors[] = $fieldError;
        }

        if (!validatePassword($password, $fieldError)) {
            $errors[] = $fieldError;
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords don't match.";
        }

        if (empty($errors)) {
            // Check if user exists (case-sensitive for username, case-insensitive for email)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR LOWER(email) = LOWER(:email)");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
            ]);
            if ($stmt->fetch()) {
                $errors[] = "Username or email already exists.";
            }
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'reader'; // Default role

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                $userId = (int) $pdo->lastInsertId();
                $rawToken = createEmailVerificationToken($pdo, $userId);
                sendVerificationEmail($email, $username, $rawToken);
                rotateCsrfToken();
                $_SESSION['pending_verification_email'] = $email;
                header("Location: verification-pending.php");
                exit;
            }

            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$pageTitle = "Register";
include '../includes/header.php';
?>

<div class="flex justify-center items-center min-h-[80vh] bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 px-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8 mt-18 mb-18">
        <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">Create Your Account</h1>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" required 
                    value="<?= htmlspecialchars((string)$username, ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required 
                    value="<?= htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" required 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
                Register
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            Already have an account? 
            <a href="login.php" class="text-blue-600 hover:underline">Login here</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
