<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$pageTitle = "Resend Verification Email";
$success = null;
$errors = [];
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {
    $emailValue = trim((string)$_GET['email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!isValidCsrfToken($postedToken)) {
        $errors[] = "Invalid or expired form token. Please try again.";
        rotateCsrfToken();
    } else {
        $emailValue = trim($_POST['email'] ?? '');
        $fieldError = null;
        if (!validateEmailAddress($emailValue, $fieldError)) {
            $errors[] = $fieldError;
        } elseif (!canResendVerificationEmail($emailValue)) {
            $errors[] = "Please wait a few minutes before requesting another verification email.";
        } else {
            $stmt = $pdo->prepare("
                SELECT id, username, email_verified_at
                FROM users
                WHERE LOWER(email) = LOWER(:email)
                LIMIT 1
            ");
            $stmt->execute([':email' => $emailValue]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && isEmailVerified($user['email_verified_at'])) {
                $success = "Your email is already verified. You can log in.";
            } elseif ($user) {
                $rawToken = createEmailVerificationToken($pdo, (int)$user['id']);
                sendVerificationEmail($emailValue, $user['username'], $rawToken);
                rotateCsrfToken();
                $_SESSION['verification_status_message'] = "A fresh verification link has been sent to {$emailValue}.";
                $_SESSION['pending_verification_email'] = $emailValue;
                header("Location: verification-pending.php");
                exit;
            } else {
                // Avoid disclosing whether the email exists.
                $success = "If an account exists for {$emailValue}, a verification email has been sent.";
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="flex justify-center items-center min-h-[60vh] bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 px-4">
    <div class="w-full max-w-xl bg-white rounded-lg shadow-lg p-8 mt-18 mb-18">
        <h1 class="text-2xl font-bold text-center mb-4 text-gray-800">Resend Verification Email</h1>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-700 rounded">
                <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required
                       value="<?= htmlspecialchars((string)$emailValue, ENT_QUOTES, 'UTF-8') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
                Send Verification Email
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            Remembered your password? <a href="login.php" class="text-blue-600 hover:underline">Sign in instead</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
