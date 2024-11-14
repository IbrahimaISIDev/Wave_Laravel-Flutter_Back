<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur SamaXaalis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.16/tailwind.min.css">
    <style>
        /* Ajoutez des styles personnalisés ici */
        .brand-color {
            color: #007bff;
        }
        .brand-bg {
            background-color: #007bff;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="brand-bg py-4 px-6">
                <h1 class="text-2xl font-bold text-white">Bienvenue sur SamaXaalis</h1>
                <p class="text-white">Votre nouvelle expérience de transfert d'argent</p>
            </div>
            <div class="p-6">
                <h2 class="text-xl font-bold brand-color">Bonjour, {{ $userName }} !</h2>
                <p class="text-gray-700 mb-4">Nous sommes ravis de vous avoir parmi nous. Commencez par utiliser votre code unique :</p>
                <div class="bg-gray-200 px-4 py-3 rounded-lg mb-4">
                    <p class="text-gray-700 font-bold text-lg">{{ $code }}</p>
                </div>
                <p class="text-gray-700 mb-4">Vous pouvez également scanner ce code QR pour accéder à votre compte :</p>
                <img src="{{ $qrUrl }}" alt="Code QR" class="mb-4 mx-auto">
                @if ($cardPdfPath && file_exists($cardPdfPath))
                <p class="text-gray-700 mb-4">Votre carte SamaXaalis est jointe à cet e-mail.</p>
                @endif
                <p class="text-gray-700">Si vous avez des questions, n'hésitez pas à nous contacter.</p>
            </div>
        </div>
    </div>
</body>
</html>