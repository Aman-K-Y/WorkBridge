<?php
require_once 'includes/config.php';
require_once 'includes/api.php';
requireLogin();

$jobId = $_GET['job_id'] ?? null;
$withUserId = $_GET['with'] ?? null;

if (!$jobId || !$withUserId) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');
    if ($text) {
        $result = apiPost('/messages', [
            'job_id' => (int) $jobId,
            'receiver_id' => (int) $withUserId,
            'text' => $text,
        ], true);
        if ($result['status'] !== 201) {
            $error = $result['body']['error'] ?? 'Could not send message.';
        }
    }
}

$msgResult = apiGet("/messages/$jobId", true);
$messages = $msgResult['body']['messages'] ?? [];

$jobResult = apiGet("/jobs/$jobId");
$job = $jobResult['body']['job'] ?? null;

$currentUserId = $_SESSION['user']['user_id'];

$pageTitle = 'Messages';
require_once 'includes/header.php';
?>

<a href="job-details.php?id=<?php echo $jobId; ?>" class="text-muted">&larr; Back to Job</a>

<h2 class="mt-1"><?php echo $job ? htmlspecialchars($job['title']) : 'Conversation'; ?></h2>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="chat-thread">
    <?php if (empty($messages)): ?>
        <p class="text-muted text-center">No messages yet. Say hello!</p>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
            <?php $isMine = $msg['sender_id'] == $currentUserId; ?>
            <div class="chat-bubble <?php echo $isMine ? 'chat-bubble-mine' : 'chat-bubble-theirs'; ?>">
                <div><?php echo htmlspecialchars($msg['text']); ?></div>
                <div class="chat-timestamp"><?php echo htmlspecialchars(substr($msg['timestamp'], 0, 16)); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<form method="POST" style="display:flex; gap:0.75rem;">
    <input type="text" name="text" placeholder="Type a message…" required style="flex:1; padding:0.65rem 0.9rem; border:1px solid var(--border); border-radius:5px;">
    <button type="submit" class="btn btn-primary">Send</button>
</form>

<?php require_once 'includes/footer.php'; ?>
