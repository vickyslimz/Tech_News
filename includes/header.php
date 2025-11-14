<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'My Blog'; ?></title>
    <link href="/php-blog/assets/css/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" />
    <script src="https://kit.fontawesome.com/314d088d52.js" crossorigin="anonymous"></script>

    <style>
        /* Ensures consistent spacing */
        :root {
            --content-padding: 1rem;
            --content-max-width: 1280px;
        }

        @media (min-width: 640px) {
            :root {
                --content-padding: 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            :root {
                --content-padding: 2rem;
            }
        }
    </style>
</head>

<body class="font-sans min-h-screen flex flex-col">
    <!-- Loading Spinner -->
    <div id="globalLoadingSpinner" class="fixed inset-0 bg-white bg-opacity-80 items-center justify-center z-50 hidden">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-gray-600">Loading...</p>
        </div>
    </div>
    <?php
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Determine user role for navigation
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    ?>

    <header class="sticky top-0 z-50 bg-gray-800 text-white shadow-md w-full">
        <div class="mx-auto w-full max-w-[var(--content-max-width)] px-[var(--content-padding)] py-3">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/php-blog/public" class="flex-shrink-0">
                    <img class="w-[120px] h-[24px]" src="/php-blog/assets/images/logo.png" alt="Logo">
                </a>

                <!-- Hamburger Button (Mobile) -->
                <button id="menu-btn" class="md:hidden flex items-center justify-center w-10 h-10 rounded hover:bg-gray-700 transition focus:outline-none">
                    <!-- Hamburger Icon -->
                    <svg id="hamburger-icon" class="w-6 h-6 block" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <!-- X Icon -->
                    <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Navigation -->
                <nav id="menu" class="hidden md:block absolute md:static top-full left-0 w-full md:w-auto bg-gray-800 md:bg-transparent">
                    <ul class="flex flex-col md:flex-row md:space-x-6 md:mt-0">
                        <li>
                            <a href="/php-blog/public" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fa-solid fa-house"></i> Home</a>
                        </li>
                        <li>
                            <a href="/php-blog/public/about.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-users"></i> About</a>
                        </li>
                        <li>
                            <a href="/php-blog/public/contact.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-envelope"></i> Contact</a>
                        </li>

                        <!-- Search for all logged-in users -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li>
                                <a href="#" id="search-btn" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-search"></i> Search</a>
                            </li>
                        <?php endif; ?>

                        <!-- User-specific menu items -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Admin specific menu -->
                            <?php if ($user_role === 'admin'): ?>
                                <li>
                                    <a href="/php-blog/admin/dashboard.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                </li>
                                <li>
                                    <a href="/php-blog/admin/manage-posts.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-newspaper"></i> Manage Posts</a>
                                </li>
                                <li>
                                    <a href="/php-blog/admin/manage-users.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-users-cog"></i> Manage Users</a>
                                </li>
                            <?php endif; ?>

                            <!-- Author specific menu -->
                            <?php if ($user_role === 'author'): ?>
                                <li>
                                    <a href="/php-blog/author/dashboard.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-pencil-alt"></i> Author Dashboard</a>
                                </li>
                                <li>
                                    <a href="/php-blog/author/create-post.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-plus"></i> New Post</a>
                                </li>
                                <li>
                                    <a href="/php-blog/author/my-posts.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-list"></i> My Posts</a>
                                </li>
                            <?php endif; ?>

                            <!-- Reader specific menu -->
                            <?php if ($user_role === 'reader'): ?>
                                <li>
                                    <a href="/php-blog/reader/profile.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-user"></i> My Profile</a>
                                </li>
                                <li>
                                    <a href="/php-blog/reader/saved-posts.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-bookmark"></i> Saved Posts</a>
                                </li>
                            <?php endif; ?>

                            <!-- Common menu items for all logged-in users -->
                            <li class="relative group">
                                <a href="#" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition">
                                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?> ▼
                                </a>
                                <ul class="absolute hidden group-hover:block bg-gray-800 rounded-md shadow-lg mt-1 w-48 z-50">
                                    <li><a href="/php-blog/public/profile.php" class="block px-4 py-2 hover:bg-gray-700">Profile</a></li>
                                    <li><a href="/php-blog/public/settings.php" class="block px-4 py-2 hover:bg-gray-700">Settings</a></li>
                                    <li>
                                        <hr class="border-gray-700">
                                    </li>
                                    <li><a href="/php-blog/public/logout.php" class="block px-4 py-2 hover:bg-gray-700 text-red-300"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                                </ul>
                            </li>

                        <?php else: ?>
                            <!-- Guest menu items -->
                            <li>
                                <a href="/php-blog/public/login.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                            </li>
                            <li>
                                <a href="/php-blog/public/register.php" class="block py-2 px-4 hover:bg-gray-700 md:hover:bg-transparent hover:text-gray-300 transition"><i class="fas fa-user-plus"></i> Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Mobile Menu JS -->
        <script>
            const menuBtn = document.getElementById('menu-btn');
            const menu = document.getElementById('menu');
            const hamburgerIcon = document.getElementById('hamburger-icon');
            const closeIcon = document.getElementById('close-icon');

            menuBtn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
                hamburgerIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.group')) {
                    document.querySelectorAll('.group ul').forEach(dropdown => {
                        dropdown.classList.add('hidden');
                    });
                }
            });
            // Global spinner functions
            window.showSpinner = function() {
                document.getElementById('globalLoadingSpinner').classList.remove('hidden');
            };

            window.hideSpinner = function() {
                document.getElementById('globalLoadingSpinner').classList.add('hidden');
            };

            // Show spinner on page navigation
            document.addEventListener('DOMContentLoaded', function() {
                const links = document.querySelectorAll('a[href]');

                links.forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Don't show spinner for external links or same-page anchors
                        if (this.target === '_blank' || this.href.includes('javascript:') || this.getAttribute('href').startsWith('#')) {
                            return;
                        }

                        // Show spinner for internal navigation
                        showSpinner();
                    });
                });

                // Hide spinner when page is fully loaded
                window.addEventListener('load', hideSpinner);

                // Also hide spinner after 5 seconds as a fallback
                setTimeout(hideSpinner, 5000);
            });
        </script>
    </header>

    <main>