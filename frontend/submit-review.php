<?php
require_once 'includes/config.php';
require_once 'includes/api.php';
requireLogin();

$jobId = $_POST['job_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if ($jobId && $rating) {
    apiPost('/reviews', [
        'job_id' => (int) $jobId,
        'rating' => (int) $rating,
        'comment' => $comment,
    ], true);
}

header('Location: job-details.php?id=' . $jobId);
exit;
