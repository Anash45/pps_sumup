<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'MyApp') ?></title>

    <!-- ✅ Tailwind CSS v4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-gray-50">

    <!-- 🧭 Navbar -->
    <nav class="bg-white shadow-sm py-4">
        <div class="container mx-auto flex justify-center">
            <a href="/">
                <img src="/pps_logo.svg" alt="Logo" class="h-10 w-auto">
            </a>
        </div>
    </nav>

    <!-- 📄 Page Content -->
    <main class="p-6">
        <?= $this->renderSection('content') ?>
    </main>

</body>

</html>