<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: browse-jobs.php');
    exit;
}

$result = apiGet("/reviews/user/$userId");
if ($result['status'] !== 200) {
    $pageTitle = 'Profile Not Found';
    require_once 'includes/header.php';
    echo '<div class="empty-state"><p>This profile could not be found.</p></div>';
    require_once 'includes/footer.php';
    exit;
}
$data = $result['body'];

$pageTitle = $data['user_name'];
require_once 'includes/header.php';
?>

<div class="card">
    <h2><?php echo htmlspecialchars($data['user_name']); ?></h2>
    <?php if ($data['average_rating']): ?>
        <p class="rating-stars">
            <?php echo str_repeat('★', round($data['average_rating'])) . str_repeat('☆', 5 - round($data['average_rating'])); ?>
            <span class="text-muted" style="font-size:0.9rem;"> <?php echo $data['average_rating']; ?> / 5 (<?php echo $data['total_reviews']; ?> review<?php echo $data['total_reviews'] === 1 ? '' : 's'; ?>)</span>
        </p>
    <?php else: ?>
        <p class="text-muted">No reviews yet.</p>
    <?php endif; ?>
</div>

<h3 class="mt-2">Reviews</h3>
<?php if (empty($data['reviews'])): ?>
    <p class="text-muted">This user hasn't received any reviews yet.</p>
<?php else: ?>
    <?php foreach ($data['reviews'] as $review): ?>
        <div class="review-card">
            <p class="rating-stars" style="font-size:0.95rem;">
                <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
            </p>
            <p><?php echo htmlspecialchars($review['comment']); ?></p>
            <p class="text-muted" style="font-size:0.85rem;">— <?php echo htmlspecialchars($review['reviewer_name']); ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
