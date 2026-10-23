<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Belajar Tailwind</title>

    @vite('resources/css/app.css')
</head>
<body>
    <div class="min-h-screen bg-gray-100 p-6">
    <div class="mx-auto max-w-5xl">

        <h1 class="mb-2 text-3xl font-bold text-blue-800">
            Tracking Mitra
        </h1>

        <p class="mb-6 text-gray-600">
            Dashboard pemantauan kegiatan mitra
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <div class="rounded-xl bg-white p-6 shadow">
                <p class="text-gray-500">Total Mitra</p>
                <h2 class="mt-2 text-3xl font-bold text-blue-600">
                    120
                </h2>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <p class="text-gray-500">Tugas Selesai</p>
                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    85
                </h2>
            </div>

        </div>
    </div>
</div>
</body>
</html>
