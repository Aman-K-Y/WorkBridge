<?php
http_response_code(404);
$pageTitle = 'Page Not Found - WorkBridge';
require_once 'includes/header.php';
?>

<div class="empty-state" style="margin-top: 4rem;">
    <h1 style="font-size: 4rem; color: var(--amber-dark); margin-bottom: 0;">404</h1>
    <h2>Oops! Page Not Found</h2>
    <p class="text-muted" style="max-width: 500px; margin: 1rem auto;">We can't seem to find the page you're looking for. It might have been removed, had its name changed, or is temporarily unavailable.</p>
    <a href="index.php" class="btn btn-primary mt-1" role="button" aria-label="Return Home">Return to Homepage</a>
</div>

<?php require_once 'includes/footer.php'; ?>
