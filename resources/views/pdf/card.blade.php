<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Carte SamaXaalis</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: A4 portrait;
        }
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: white;
        }
        .card {
            width: 1240px;
            height: 800px;
            position: relative;
            background: #0088FF;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .pattern-bg {
            position: absolute;
            top: 20;
            left: 20;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(45deg, transparent 45%, rgba(255, 255, 255, 0.1) 45%, rgba(255, 255, 255, 0.1) 55%, transparent 55%),
                linear-gradient(-45deg, transparent 45%, rgba(255, 255, 255, 0.1) 45%, rgba(255, 255, 255, 0.1) 55%, transparent 55%);
            background-size: 20px 20px;
            opacity: 0.5;
        }
        .diamond {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(45deg);
        }
        .diamond-1 { top: 20px; left: 20px; }
        .diamond-2 { top: 20px; right: 20px; }
        .diamond-3 { bottom: 20px; left: 20px; }
        .diamond-4 { bottom: 20px; right: 20px; }
        .qr-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .qr-code {
            width: 400px;
            height: 400px;
            display: block;
        }
        .scanner-text {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .penguin-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 24px;
        }
        .page-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="card">
            <div class="pattern-bg"></div>
            <!-- Diamonds décoratifs -->
            <div class="diamond diamond-1"></div>
            <div class="diamond diamond-2"></div>
            <div class="diamond diamond-3"></div>
            <div class="diamond diamond-4"></div>
            
            <!-- QR Code -->
            <div class="qr-container">
                <img src="{{ $qrUrl }}" alt="QR Code" class="qr-code">
            </div>
            
            <!-- Texte Scanner -->
            <div class="scanner-text">Scanner le code QR</div>
            
            <!-- Icône Pingouin -->
            <div class="penguin-icon">
                <svg viewBox="0 0 24 24" fill="white">
                    <path d="M19,16C19,17.72 18.37,19.3 17.34,20.5C17.75,20.89 18,21.41 18,22H6C6,21.41 6.25,20.89 6.66,20.5C5.63,19.3 5,17.72 5,16C5,13.24 7.24,11 10,11H14C16.76,11 19,13.24 19,16M9,2H15L13,7H11L9,2M10,9H14C14.55,9 15,9.45 15,10C15,10.55 14.55,11 14,11H10C9.45,11 9,10.55 9,10C9,9.45 9.45,9 10,9Z"/>
                </svg>
            </div>
        </div>
    </div>
</body>
</html>