<?php
session_start();
require_once '../includes/functions.php';

$pageTitle = "Verify Your Email";
$email = $_SESSION['pending_verification_email'] ?? '';
$statusMessage = $_SESSION['verification_status_message'] ?? null;
unset($_SESSION['verification_status_message']);

include '../includes/header.php';
?>

<div class="flex justify-center items-center min-h-[60vh] bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 px-4">
    <div class="w-full max-w-xl bg-white rounded-lg shadow-lg p-8 mt-18 mb-18">
        <h1 class="text-2xl font-bold text-center mb-4 text-gray-800">Check Your Email</h1>
        <?php if ($email): ?>
            <p class="text-gray-700 text-center mb-6">
                We sent a verification link to
                <strong><?= htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8') ?></strong>.
                Please click the link in that email to activate your account.
            </p>
        <?php else: ?>
            <p class="text-gray-700 text-center mb-6">
                We sent a verification link to your email address. Please click the link in that email to activate your account.
            </p>
        <?php endif; ?>

        <?php if ($statusMessage): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-700 rounded">
                <?= htmlspecialchars((string)$statusMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <p class="text-sm text-gray-600 mb-6 text-center">
            Didn't get the email? Check your spam folder, then you can request another verification link below.
        </p>

        <form method="POST" action="resend-verification.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email"
                       name="email"
                       required
                       value="<?= htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
                Resend Verification Email
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            Need help? <a href="contact.php" class="text-blue-600 hover:underline">Contact support</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
