<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$pageTitle = "Verify Email";
$status = null;
$error = null;

$token = $_GET['token'] ?? '';
$token = is_string($token) ? trim($token) : '';

if ($token === '') {
    $error = "Verification link is invalid or missing.";
} else {
    $tokenHash = hashToken($token);

    $stmt = $pdo->prepare("
        SELECT id, username, email, email_verified_at, email_verification_token
        FROM users
        WHERE email_verification_token = :tokenHash
        LIMIT 1
    ");
    $stmt->execute([':tokenHash' => $tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "This verification link is invalid or has already been used.";
    } else {
        $parts = explode(':', $token, 2);
        if (count($parts) !== 2 || !ctype_digit($parts[0]) || $parts[1] === '') {
            $error = "This verification link is invalid.";
        } elseif (((int)$parts[0] + getEmailVerificationTtl()) < time()) {
            $error = "This verification link has expired. Please request a new one.";
            $_SESSION['pending_verification_email'] = $user['email'];
        } elseif (isEmailVerified($user['email_verified_at'])) {
            $status = "Your email has already been verified. You can log in below.";
        } else {
            $update = $pdo->prepare("
                UPDATE users
                SET email_verified_at = NOW(), email_verification_token = NULL
                WHERE id = :id
            ");
            $update->execute([':id' => $user['id']]);

            $_SESSION['success'] = "Email verified successfully! You can log in now.";
            unset($_SESSION['pending_verification_email']);
            header("Location: login.php");
            exit;
        }
    }
}

include '../includes/header.php';
?>

<div class="flex justify-center items-center min-h-[60vh] bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 px-4">
    <div class="w-full max-w-lg bg-white rounded-lg shadow-lg p-8 mt-18 mb-18">
        <h1 class="text-2xl font-bold text-center mb-4 text-gray-800">Email Verification</h1>

        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
                <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($status): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-700 rounded">
                <?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <p class="text-center text-sm text-gray-600 mb-2">
            Need a fresh link? <a href="resend-verification.php" class="text-blue-600 hover:underline">Resend verification email</a>.
        </p>
        <p class="text-center text-sm text-gray-600">
            Ready to sign in? <a href="login.php" class="text-blue-600 hover:underline">Go to login</a>.
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
