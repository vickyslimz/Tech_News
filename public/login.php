<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Start session at the very beginning
session_start();

$pageTitle = "Login";
$error = null;
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
$identifier = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: /php-blog/admin/dashboard.php");
    } else {
        header("Location: /php-blog/public");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!isValidCsrfToken($postedToken)) {
        $error = "Invalid or expired form token. Please try again.";
        rotateCsrfToken();
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $error = "Please complete all fields.";
        } else {
            $throttleKey = buildLoginThrottleKey($identifier);
            $throttle = checkLoginThrottle($throttleKey);
            if ($throttle['locked']) {
                $minutes = (int)ceil($throttle['retry_after'] / 60);
                $error = "Too many login attempts. Please try again in {$minutes} minute(s).";
            } else {
                try {
                    // Check whether identifier is email or username
                    $isEmail = strpos($identifier, '@') !== false;

                    if ($isEmail) {
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
                        $stmt->execute([':email' => $identifier]);
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
                        $stmt->execute([':username' => $identifier]);
                    }

                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($password, $user['password'])) {
                        if (!isEmailVerified($user['email_verified_at'] ?? null)) {
                            recordLoginAttempt($throttleKey, true);
                            $_SESSION['pending_verification_email'] = $user['email'];
                            $resendLink = '/php-blog/public/resend-verification.php?email=' . urlencode($user['email']);
                            $error = "You need to verify your email before logging in. "
                                . "Visit {$resendLink} to request a new verification link.";
                        } else {
                            recordLoginAttempt($throttleKey, true);
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['user_role'] = $user['role'];
                            rotateCsrfToken();

                            // Check if there's a redirect URL stored
                            if (isset($_SESSION['redirect_url'])) {
                                $redirect_url = $_SESSION['redirect_url'];
                                unset($_SESSION['redirect_url']);
                                if (is_string($redirect_url) && strpos($redirect_url, '/') === 0 && strpos($redirect_url, '//') !== 0) {
                                    header("Location: " . $redirect_url);
                                    exit;
                                }
                            }

                            // Default redirect based on role
                            if ($user['role'] === 'admin') {
                                header("Location: /php-blog/admin/dashboard.php");
                            } else {
                                header("Location: /php-blog/public");
                            }
                            exit;
                        }
                    } else {
                        recordLoginAttempt($throttleKey, false);
                        $error = "Invalid username/email or password.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<!-- Gradient background wrapper -->
<div class="pt-10 pb-10 flex justify-center items-center min-h-[70vh] px-4 bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200">
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-6 mt-30 mb-30">
        <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">Login</h1>

        <?php if ($successMessage): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars((string)$successMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label for="identifier" class="block text-sm font-medium text-gray-700">Username or Email</label>
                <input type="text" id="identifier" name="identifier" required
                       value="<?= htmlspecialchars((string)$identifier, ENT_QUOTES, 'UTF-8') ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
                Login
            </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-4">
            Don't have an account?
            <a href="/php-blog/public/register.php" class="text-blue-600 hover:text-blue-800 font-medium">Register here</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
