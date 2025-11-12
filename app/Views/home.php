<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="flex flex-col items-center justify-center min-h-[60vh] space-y-6">
    <h1 class="text-3xl font-semibold text-gray-900">Welcome to Home Page</h1>

    <form action="/build-pdf" method="POST" enctype="multipart/form-data"
        class="mt-4 flex flex-col space-y-4 w-full max-w-md">
        <!-- Language selection -->
        <label class="font-medium text-gray-700">Select Template:</label>
        <select name="language" required
            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="de">German</option>
            <option value="it">Italian</option>
            <option value="fr">French</option>
        </select>

        <!-- CSV file upload -->
        <label class="font-medium text-gray-700">Upload CSV:</label>
        <input type="file" name="csv_file" accept=".csv" required
            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">

        <!-- Submit button -->
        <button type="submit"
            class="px-6 py-3 bg-blue-600 cursor-pointer text-white font-medium rounded-lg shadow hover:bg-blue-700 focus:outline-none transition">
            Build PDF
        </button>
    </form>

    <!-- Flash messages -->
    <?php if (session()->getFlashdata('message')): ?>
        <div class="mt-6 text-green-600 text-lg font-medium">
            <?= esc(session()->getFlashdata('message')) ?>
        </div>
    <?php endif; ?>

    <!-- PDF download button -->
    <?php if (session()->getFlashdata('pdf_link')): ?>
        <a href="<?= esc(session()->getFlashdata('pdf_link')) ?>" target="_blank"
            class="inline-block px-6 py-3 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">
            Download PDF
        </a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>