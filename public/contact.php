<?php 
$pageTitle = "Contact";
include '../includes/header.php'; 

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $message = trim($_POST['message']);

    if ($name && $email && $message) {
        $to = "victormosunmola@gmail.com";
        $subject = "New Contact Form Message from $name";
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        if (mail($to, $subject, $body, $headers)) {
            $success = "Thank you, $name! Your message has been sent successfully.";
            $_POST = []; // clear form after success
        } else {
            $error = "There was a problem sending your message. Please try again later.";
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}
?>

<div class="flex justify-center items-center min-h-[70vh] px-4 bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200">
    <div class="w-full max-w-lg bg-white rounded-lg shadow-xl p-8 mt-17 mb-17">
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Contact Us</h1>
        <p class="text-center text-gray-600 mb-8">
            We’d love to hear from you! Fill out the form below and we’ll get back to you soon.
        </p>

        <!-- Feedback Messages -->
        <?php if ($success): ?>
            <div class="mb-6 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php elseif ($error): ?>
            <div class="mb-6 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Your Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700">Your Message</label>
                <textarea id="message" name="message" rows="5" required
                          class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring focus:ring-blue-300"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition">
                Send Message
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


