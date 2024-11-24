<?php
$appConfig = include __DIR__ . '/config/app_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download PWA</title>
    <link rel="manifest" href="/manifest.json">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../styles/app_styles.css">
    <link rel="stylesheet" href="../styles/pwa_download.css">
</head>
<body>
<div id="pwa-install-container">
    <a href="../index.php">
        <img src=<?php echo $appConfig['logo_large'] ?>>
    </a>

    <p id="pwa-install-text">Click the button below to download the PWA.</p>
    <button id="pwa-install-btn" style="display: none">
        <img id="download-button-icon" src=<?php echo $appConfig['logo_small'] ?>>
        <strong>Download PWA</strong>
    </button>
    <div class="alert alert-info" id="manual-instructions" style="display: none;">
        <h4 class="alert-heading">
            Don't see the installation button?
        </h4>
        If you cant see the button, your browser does not support automatic installation.
        You can manually add this app to your home screen through your browser's menu options.
        <br>
        <strong>On Firefox:</strong> Look for "Install" in the browser's main menu or address bar.
        <br>
        <strong>On Safari (iOS):</strong> Tap the "Share" button and select "Add to Home Screen."
    </div>
</div>

<script>
    let deferredPrompt;

    // Listen for 'beforeinstallprompt' event for supported browsers (Chrome, Edge)
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the default mini-infobar
        e.preventDefault();
        // Save the event for later
        deferredPrompt = e;

        // Show the install button
        const pwaInstallBtn = document.getElementById('pwa-install-btn');
        pwaInstallBtn.style.display = 'block';

        // When the user clicks the install button
        pwaInstallBtn.addEventListener('click', () => {
            // Show the native install prompt
            deferredPrompt.prompt();

            // Wait for the user's response
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                deferredPrompt = null;
            });
        });
    });

    // Fallback: Show manual instructions for browsers that don't support 'beforeinstallprompt'
    window.addEventListener('load', () => {
        const pwaInstallBtn = document.getElementById('pwa-install-btn');
        const manualInstructions = document.getElementById('manual-instructions');

        // Check if the browser is a known unsupported browser (Safari, Firefox)
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        const isFirefox = typeof InstallTrigger !== 'undefined';

        if (!window.matchMedia('(display-mode: standalone)').matches) {
            if (!deferredPrompt && (isSafari || isFirefox)) {
                // Show manual instructions for Firefox or Safari
                manualInstructions.style.display = 'block';
            }
        }
    });
</script>
</body>
</html>
