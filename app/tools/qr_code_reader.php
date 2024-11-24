<?php
$appConfig = include __DIR__ . '/../config/app_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Reader</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <style>
        video {
            width: 100%;
            max-width: 80vw;
            border: var(--color-accent) solid 2px;
            margin-bottom: 10px;
        }

        #output {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../styles/app_styles.css">
</head>
<body>
<!-- Navigation Bar with Logout Button -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../index.php">
            <h1 class="navbar-brand">
                <img src="<?php echo $appConfig['logo_small'] ?>" alt="logo" width="30" height="30" class="d-inline-block align-top">
                <?php echo $appConfig['app_name']  ?>
            </h1>
        </a>
        <div class="ml-auto">
            <a href="../logout.php" class="btn btn-danger"><i class="bi bi-power"> </i>Logout</a>
        </div>
    </div>
</nav>

<div class="reader-container container mt-5" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <h1 style="text-align: center; color: var(--color-accent)">QR Code Reader</h1>
    <video id="video" autoplay></video>
    <canvas id="canvas" hidden></canvas>
    <div id="output" class="alert alert-info">
        Waiting for Permission to Access Camera...
    </div>
    <div id="error" class="alert alert-danger" role="alert" hidden></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const output = document.getElementById('output');
    const context = canvas.getContext('2d');

    // Request access to the user's camera
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            video.srcObject = stream;
            video.setAttribute('playsinline', true); // Required for iOS
            video.play();
            requestAnimationFrame(tick);

            output.innerHTML = 'Scanning for QR Code...';
        })
        .catch(err => {
            console.error('Error accessing camera: ', err);
            document.getElementById('error').innerText = 'Could not access the camera. Please allow camera access and refresh the page.';
            document.getElementById('error').removeAttribute('hidden');

            // Also hide the "Scanning for QR Code..." message
            output.hidden = true;
        });

    // Continuously capture frames from the video stream
    function tick() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const codeRead = jsQR(imageData.data, canvas.width, canvas.height);

            if (!codeRead) {
                output.innerHTML = 'Scanning for QR Code...';
                requestAnimationFrame(tick);
                return;
            }

            // Stop the video stream and processing
            video.srcObject.getTracks().forEach(track => track.stop());

            let data = codeRead.data;

            // After the `https://api.qrserver.com/v1/create-qr-code/?data=` part, the QR code data is appended. We need to extract everything after that.
            let target = data.split('https://api.qrserver.com/v1/create-qr-code/?data=')[1];

            // Now set the output as 'success' and redirect to the QR code data
            output.classList.remove('alert-info');
            output.classList.add('alert-success');
            output.textContent = `QR Code Data: ${target}`;

            // Wait half a second before redirecting to the target URL
            setTimeout(() => {
                console.info('Redirecting to: ', target);

                // Ensure target is an absolute URL
                let isNotAbsolute = !/^https?:\/\//i.test(target);
                if (isNotAbsolute) { target = 'https://' + target; }

                // Redirect to the target URL
                window.location = target;
            }, 500);

            return;
        }
        requestAnimationFrame(tick);
    }
</script>
</body>
</html>
