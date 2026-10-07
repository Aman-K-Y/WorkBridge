<?php
http_response_code(500);
$pageTitle = 'Server Error - WorkBridge';
require_once 'includes/header.php';
?>

<div class="empty-state" style="margin-top: 4rem;">
    <h1 style="font-size: 4rem; color: var(--danger); margin-bottom: 0;">500</h1>
    <h2>Internal Server Error</h2>
    <p class="text-muted" style="max-width: 500px; margin: 1rem auto;">Something went wrong on our end. We're working to fix it as quickly as possible. Please try again later.</p>
    <a href="index.php" class="btn btn-primary mt-1" role="button" aria-label="Return Home">Return to Homepage</a>
</div>

<?php require_once 'includes/footer.php'; ?>
