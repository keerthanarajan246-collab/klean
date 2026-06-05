<?php
/**
 * PHPMailer Automatic Downloader Utility
 * Run this in your browser: http://localhost/klean/download-phpmailer.php
 */

$targetDir = __DIR__ . '/includes/PHPMailer/src/';

// Create directories if they do not exist
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$files = [
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php'      => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'
];

$results = [];

foreach ($files as $filename => $url) {
    $outputPath = $targetDir . $filename;
    
    // Try downloading via file_get_contents
    $content = false;
    if (ini_get('allow_url_fopen')) {
        $content = @file_get_contents($url);
    }
    
    // Fallback to cURL if allow_url_fopen is disabled
    if ($content === false && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $content = curl_exec($ch);
        curl_close($ch);
    }
    
    if ($content !== false && !empty($content)) {
        if (file_put_contents($outputPath, $content) !== false) {
            $results[$filename] = ['status' => 'success', 'message' => 'Successfully downloaded and saved.'];
        } else {
            $results[$filename] = ['status' => 'danger', 'message' => 'Failed to save file locally. Check write permissions.'];
        }
    } else {
        $results[$filename] = ['status' => 'danger', 'message' => 'Failed to download from GitHub. Check your internet connection.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHPMailer Downloader</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card-custom { background: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02); max-width: 500px; width: 100%; overflow: hidden; }
  </style>
</head>
<body>
  <div class="card card-custom p-4 p-md-5">
    <div class="text-center mb-4">
      <h3 class="fw-800 text-dark">PHPMailer Installer</h3>
      <p class="text-muted small">Downloading library files directly from GitHub sources.</p>
    </div>
    
    <div class="list-group mb-4">
      <?php foreach ($results as $file => $res): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-0 border-bottom">
          <div>
            <h6 class="mb-0 fw-700"><?= htmlspecialchars($file) ?></h6>
            <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($res['message']) ?></small>
          </div>
          <span class="badge bg-<?= $res['status'] ?> rounded-pill px-3 py-2">
            <?= $res['status'] === 'success' ? 'SUCCESS' : 'FAILED' ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    
    <?php 
    $allSuccess = count(array_filter($results, function($r) { return $r['status'] === 'success'; })) === 3;
    if ($allSuccess): 
    ?>
      <div class="alert alert-success rounded-3 small text-center mb-4">
        <strong>All files installed!</strong> SMTP email notifications are now active. You can safely delete this downloader script.
      </div>
      <a href="index.php" class="btn btn-primary w-100 py-2 rounded-3 fw-700" style="background-color:#6C3FF4; border-color:#6C3FF4;">Go to Homepage</a>
    <?php else: ?>
      <div class="alert alert-danger rounded-3 small text-center mb-4">
        <strong>Installation failed!</strong> Please check your internet connection or check write permissions on the <code>/includes/</code> directory.
      </div>
      <a href="download-phpmailer.php" class="btn btn-outline-secondary w-100 py-2 rounded-3 fw-700">Retry Download</a>
    <?php endif; ?>
  </div>
</body>
</html>
