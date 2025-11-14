<?php
// Format date for display
function formatDate($dateString)
{
    return date('F j, Y', strtotime($dateString));
}

// Generate slug from title
function generateSlug($title)
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}

// Sanitize output
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Upload image and return path
function uploadImage($file)
{
    $targetDir = "assets/images/uploads/";
    $fileName = uniqid() . '-' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;

    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['error' => 'File is not an image'];
    }

    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['error' => 'File is too large'];
    }

    // Allow certain file formats
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['error' => 'Only JPG, JPEG, PNG & GIF files are allowed'];
    }

    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['path' => $targetFile];
    } else {
        return ['error' => 'Error uploading file'];
    }
}
function cleanImagePath($path) {
    // Remove duplicate php-blog/ if exists
    $path = str_replace('php-blog/', '', $path);
    // Ensure path starts with /
    return '/' . ltrim($path, '/');
}

function getPostCategories($pdo, $postId) {
    $stmt = $pdo->prepare("
        SELECT c.name 
        FROM post_categories pc
        JOIN categories c ON pc.category_id = c.id
        WHERE pc.post_id = ?
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ensure an active session is available for helpers that rely on $_SESSION.
 */
function ensureSessionActive(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Retrieve (and lazily generate) a CSRF token for the current session.
 */
function getCsrfToken(): string
{
    ensureSessionActive();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Invalidate the current CSRF token to avoid token reuse.
 */
function rotateCsrfToken(): void
{
    ensureSessionActive();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Validate the provided CSRF token against the one stored in the session.
 */
function isValidCsrfToken(?string $token): bool
{
    ensureSessionActive();

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Basic username rules: 3-32 characters, alphanumeric or underscore only.
 */
function validateUsername(string $username, ?string &$error = null): bool
{
    if ($username === '') {
        $error = 'Username is required.';
        return false;
    }

    $length = strlen($username);
    if ($length < 3 || $length > 32) {
        $error = 'Username must be between 3 and 32 characters.';
        return false;
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';
        return false;
    }

    return true;
}

/**
 * Validate email address format.
 */
function validateEmailAddress(string $email, ?string &$error = null): bool
{
    if ($email === '') {
        $error = 'Email is required.';
        return false;
    }

    // Basic format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
        return false;
    }

    // Extract domain part
    $domain = substr(strrchr($email, "@"), 1);
    
    // Check if domain has valid MX records (can receive email)
    if (!checkdnsrr($domain, 'MX')) {
        $error = 'The email domain does not appear to exist or cannot receive emails.';
        return false;
    }

    return true;
}

/**
 * Require passwords to be at least 8 chars with both letters and numbers, upercase, lowercase.
 */
function validatePassword(string $password, ?string &$error = null): bool
{
    if ($password === '') {
        $error = 'Password is required.';
        return false;
    }

    $lengthErrors = [];
    $characterErrors = [];

    // Check length
    if (strlen($password) < 8) {
        $lengthErrors[] = 'at least 8 characters';
    }

    // Check character types
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecialChr = preg_match('/[!@#$%^&*()_+\-=\[\]{};:",.<>?\/\\|`~]/', $password);

    if (!$hasLowercase) $characterErrors[] = 'lowercase letter';
    if (!$hasUppercase) $characterErrors[] = 'uppercase letter';
    if (!$hasNumber) $characterErrors[] = 'number';
    if (!$hasSpecialChr) $characterErrors[] = 'special character';

    // If no errors, return true
    if (empty($lengthErrors) && empty($characterErrors)) {
        return true;
    }

    // Build detailed error message
    $error = 'Password must contain ';
    $allErrors = array_merge($lengthErrors, $characterErrors);
    
    if (count($allErrors) === 1) {
        $error .= 'a ' . $allErrors[0];
    } else {
        $lastError = array_pop($allErrors);
        $error .= implode(', ', $allErrors) . ' and a ' . $lastError;
    }
    $error .= '.';

    return false;
}

/**
 * Build a throttle key based on identifier (username/email) and client IP.
 */
function buildLoginThrottleKey(string $identifier): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', strtolower($identifier) . '|' . $ip);
}

/**
 * Check whether login attempts should be throttled for the provided key.
 *
 * @return array{locked:bool,retry_after:int}
 */
function checkLoginThrottle(string $key, int $maxAttempts = 5, int $decaySeconds = 900): array
{
    ensureSessionActive();

    $attempts = $_SESSION['login_attempts'][$key] ?? null;
    if (!$attempts) {
        return ['locked' => false, 'retry_after' => 0];
    }

    $elapsed = time() - $attempts['last_attempt'];
    if ($elapsed >= $decaySeconds) {
        unset($_SESSION['login_attempts'][$key]);
        return ['locked' => false, 'retry_after' => 0];
    }

    if ($attempts['count'] < $maxAttempts) {
        return ['locked' => false, 'retry_after' => 0];
    }

    return ['locked' => true, 'retry_after' => $decaySeconds - $elapsed];
}

/**
 * Record the outcome of a login attempt for throttling.
 */
function recordLoginAttempt(string $key, bool $success): void
{
    ensureSessionActive();

    if ($success) {
        unset($_SESSION['login_attempts'][$key]);
        return;
    }

    $attempts = $_SESSION['login_attempts'][$key] ?? ['count' => 0, 'last_attempt' => 0];
    $attempts['count']++;
    $attempts['last_attempt'] = time();

    $_SESSION['login_attempts'][$key] = $attempts;
}

/**
 * Generate a cryptographically secure random token.
 */
function generateToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/**
 * Hash a token before persisting to storage.
 */
function hashToken(string $token): string
{
    return hash('sha256', $token);
}

function getEmailVerificationTtl(): int
{
    return 3600; // 1 hour
}

/**
 * Store a fresh email verification token for the given user and return the raw token.
 */
function createEmailVerificationToken(PDO $pdo, int $userId): string
{
    $issuedAt = time();
    $rawToken = $issuedAt . ':' . generateToken();
    $hashed = hashToken($rawToken);

    $update = $pdo->prepare("
        UPDATE users
        SET email_verification_token = :token
        WHERE id = :id
    ");
    $update->execute([
        ':token' => $hashed,
        ':id' => $userId,
    ]);

    return $rawToken;
}

/**
 * Determine whether a verification email can be resent (basic cooldown).
 */
function canResendVerificationEmail(string $email, int $cooldownSeconds = 300): bool
{
    ensureSessionActive();
    $key = strtolower($email);
    $lastSent = $_SESSION['verification_email_cooldown'][$key] ?? 0;
    return (time() - $lastSent) >= $cooldownSeconds;
}

/**
 * Track the timestamp that a verification email was sent.
 */
function markVerificationEmailSent(string $email): void
{
    ensureSessionActive();
    $key = strtolower($email);
    $_SESSION['verification_email_cooldown'][$key] = time();
}

/**
 * Resolve the application's base URL for building absolute links.
 */
function getApplicationBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/php-blog/public';
}

/**
 * Development mail dispatch helper.
 * TODO: Replace this stub with a real SMTP/transactional email provider before production.
 */
function sendEmail(string $to, string $subject, string $htmlBody, string $textBody = ''): void
{
    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logFile = $logDir . '/mail.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] To: {$to}\nSubject: {$subject}\n\n{$htmlBody}\n---\n{$textBody}\n\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Build and dispatch an email verification message.
 */
function sendVerificationEmail(string $email, string $username, string $token): void
{
    $baseUrl = rtrim(getApplicationBaseUrl(), '/');
    $verificationLink = $baseUrl . '/verify-email.php?token=' . urlencode($token);
    $minutes = (int)ceil(getEmailVerificationTtl() / 60);

    $subject = 'Verify your account';
    $htmlBody = "
        <p>Hello " . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . ",</p>
        <p>Thanks for signing up! Please verify your email address by clicking the link below:</p>
        <p><a href=\"{$verificationLink}\">Verify Email</a></p>
        <p>This link will expire in approximately {$minutes} minutes. If you did not create an account, you can safely ignore this email.</p>
    ";

    $textBody = "Hello {$username},\n\n"
        . "Thanks for signing up! Please verify your email address by visiting the link below:\n"
        . "{$verificationLink}\n\n"
        . "This link will expire in approximately {$minutes} minutes. If you did not create an account, you can ignore this message.\n";

    sendEmail($email, $subject, $htmlBody, $textBody);
    markVerificationEmailSent($email);
}

/**
 * Helper to determine whether a user record is verified.
 */
function isEmailVerified(?string $verifiedAt): bool
{
    return !empty($verifiedAt);
}
