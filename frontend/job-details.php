<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

$jobId = $_GET['id'] ?? null;
if (!$jobId) {
    header('Location: browse-jobs.php');
    exit;
}

$jobResult = apiGet("/jobs/$jobId");
if ($jobResult['status'] !== 200) {
    $pageTitle = 'Job Not Found';
    require_once 'includes/header.php';
    echo '<div class="empty-state"><p>This job could not be found.</p><a href="browse-jobs.php" class="btn btn-outline mt-1">Back to Browse Jobs</a></div>';
    require_once 'includes/footer.php';
    exit;
}
$job = $jobResult['body']['job'];

$currentUser = $_SESSION['user'] ?? null;
$isOwner = $currentUser && $currentUser['user_id'] == $job['client_id'];
$isFreelancer = $currentUser && $currentUser['role'] === 'freelancer';

$successMsg = '';
$error = '';

// Freelancer submitting a proposal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_proposal') {
    requireRole('freelancer');
    $bidAmount = $_POST['bid_amount'] ?? '';
    $coverNote = trim($_POST['cover_note'] ?? '');

    $result = apiPost('/proposals', [
        'job_id' => (int) $jobId,
        'bid_amount' => (float) $bidAmount,
        'cover_note' => $coverNote,
    ], true);

    if ($result['status'] === 201) {
        $successMsg = 'Your proposal was submitted!';
    } else {
        $error = $result['body']['error'] ?? 'Could not submit proposal.';
    }
}

// Client accepting/rejecting a proposal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_proposal') {
    requireLogin();
    $proposalId = $_POST['proposal_id'];
    $newStatus = $_POST['new_status'];

    $result = apiPut("/proposals/$proposalId", ['status' => $newStatus], true);
    if ($result['status'] === 200) {
        $successMsg = "Proposal $newStatus.";
        // refresh job in case status changed to in_progress
        $jobResult = apiGet("/jobs/$jobId");
        $job = $jobResult['body']['job'];
    } else {
        $error = $result['body']['error'] ?? 'Could not update proposal.';
    }
}

// Client marking job complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_complete') {
    requireLogin();
    $result = apiPut("/jobs/$jobId", ['status' => 'completed'], true);
    if ($result['status'] === 200) {
        $successMsg = 'Job marked as completed.';
        $job = $result['body']['job'];
    } else {
        $error = $result['body']['error'] ?? 'Could not update job.';
    }
}

// Client withdrawing / cancelling an open job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_job') {
    requireLogin();
    $result = apiPost("/jobs/$jobId/cancel", [], true);
    if ($result['status'] === 200) {
        $successMsg = 'This job has been withdrawn successfully.';
        $jobResult = apiGet("/jobs/$jobId");
        $job = $jobResult['body']['job'] ?? $result['body']['job'];
    } else {
        $error = $result['body']['error'] ?? 'Could not withdraw job.';
    }
}

// Load proposals if the current user owns this job
$proposals = [];
if ($isOwner) {
    $propResult = apiGet("/proposals/job/$jobId", true);
    $proposals = $propResult['body']['proposals'] ?? [];
}

// Load recommended freelancers (Java matching microservice) if the owner has skills set and the job is still open
$recommendations = null;
$recommendationsError = null;
if ($isOwner && !empty($job['required_skills']) && $job['status'] === 'open') {
    $recResult = apiGet("/jobs/$jobId/recommended-freelancers", true);
    if ($recResult['status'] === 200) {
        $recommendations = $recResult['body']['matches'] ?? [];
    } else {
        $recommendationsError = $recResult['body']['error'] ?? 'Could not load recommendations.';
    }
}

$pageTitle = $job['title'];
require_once 'includes/header.php';
?>

<a href="browse-jobs.php" class="text-muted">&larr; Back to Browse Jobs</a>

<?php if ($successMsg): ?><div class="alert alert-success mt-1"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error mt-1"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="card mt-1">
    <div style="display:flex; justify-content:space-between; align-items:start;">
        <h2><?php echo htmlspecialchars($job['title']); ?></h2>
        <span class="job-budget">₹<?php echo number_format($job['budget']); ?></span>
    </div>
    <div class="job-meta">
        <?php if ($job['category']): ?><span><?php echo htmlspecialchars($job['category']); ?></span><?php endif; ?>
        <span class="badge badge-<?php echo $job['status']; ?>"><?php echo str_replace('_', ' ', $job['status']); ?></span>
        <?php if ($job['deadline']): ?><span>Due <?php echo htmlspecialchars($job['deadline']); ?></span><?php endif; ?>
        <?php if (isset($job['client_name'])): ?><span>Posted by <a href="profile.php?id=<?php echo $job['client_id']; ?>"><?php echo htmlspecialchars($job['client_name']); ?></a></span><?php endif; ?>
    </div>
    <p style="white-space:pre-wrap;"><?php echo htmlspecialchars($job['description']); ?></p>

    <?php if (!empty($job['required_skills'])): ?>
        <div class="mt-1">
            <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                <span class="badge badge-open" style="margin-right:0.4rem; margin-bottom:0.4rem; display:inline-block;"><?php echo htmlspecialchars(trim($skill)); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($isOwner && $job['status'] === 'in_progress'): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="mark_complete">
            <button type="submit" class="btn btn-secondary btn-sm">Mark Job as Completed</button>
        </form>
    <?php endif; ?>

    <?php if ($isOwner && $job['status'] === 'open'): ?>
        <form method="POST" style="display:inline; margin-top: 1rem;" onsubmit="return confirm('Are you sure you want to withdraw this job? Any pending proposals will be automatically rejected.');">
            <input type="hidden" name="action" value="cancel_job">
            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger); border-color:var(--danger);" role="button" aria-label="Withdraw Job">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:4px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Withdraw Job
            </button>
        </form>
    <?php endif; ?>

    <?php if ($isOwner && $job['status'] === 'completed'): ?>
        <a href="job-details.php?id=<?php echo $jobId; ?>#review" class="btn btn-outline btn-sm">Leave a Review</a>
    <?php endif; ?>
</div>

<?php if ($isFreelancer && !$isOwner && $job['status'] === 'open'): ?>
    <div class="card mt-2">
        <h3>Submit a Proposal</h3>
        <form method="POST">
            <input type="hidden" name="action" value="submit_proposal">
            <div class="form-group">
                <label for="bid_amount">Your bid (₹)</label>
                <input type="number" id="bid_amount" name="bid_amount" min="0" required>
            </div>
            <div class="form-group">
                <label for="cover_note">Cover note</label>
                <textarea id="cover_note" name="cover_note" placeholder="Introduce yourself and explain why you're a good fit."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Proposal</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($isOwner && !empty($job['required_skills']) && $job['status'] === 'open'): ?>
    <div class="mt-2">
        <h3>Recommended Freelancers</h3>
        <p class="text-muted" style="font-size:0.9rem;">Based on skill overlap with what this job requires.</p>

        <?php if ($recommendationsError): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($recommendationsError); ?></div>
        <?php elseif (empty($recommendations)): ?>
            <p class="text-muted">No freelancers with matching skills found yet.</p>
        <?php else: ?>
            <?php foreach ($recommendations as $rec): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <h4><a href="profile.php?id=<?php echo $rec['userId']; ?>"><?php echo htmlspecialchars($rec['freelancer_name']); ?></a></h4>
                        <span class="job-budget"><?php echo $rec['matchPercentage']; ?>% match</span>
                    </div>
                    <div class="job-meta">
                        <?php foreach ($rec['matchedSkills'] as $skill): ?>
                            <span class="badge badge-accepted"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isOwner): ?>
    <div class="mt-2">
        <h3>Proposals Received (<?php echo count($proposals); ?>)</h3>
        <?php if (empty($proposals)): ?>
            <p class="text-muted">No proposals yet.</p>
        <?php else: ?>
            <?php foreach ($proposals as $p): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <h4><a href="profile.php?id=<?php echo $p['freelancer_id']; ?>"><?php echo htmlspecialchars($p['freelancer_name']); ?></a></h4>
                        <span class="job-budget">₹<?php echo number_format($p['bid_amount']); ?></span>
                    </div>
                    <span class="badge badge-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span>
                    <p><?php echo htmlspecialchars($p['cover_note']); ?></p>

                    <?php if ($p['status'] === 'pending' && $job['status'] === 'open'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_proposal">
                            <input type="hidden" name="proposal_id" value="<?php echo $p['proposal_id']; ?>">
                            <input type="hidden" name="new_status" value="accepted">
                            <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_proposal">
                            <input type="hidden" name="proposal_id" value="<?php echo $p['proposal_id']; ?>">
                            <input type="hidden" name="new_status" value="rejected">
                            <button type="submit" class="btn btn-outline btn-sm">Reject</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($p['status'] === 'accepted'): ?>
                        <a href="messages.php?job_id=<?php echo $jobId; ?>&with=<?php echo $p['freelancer_id']; ?>" class="btn btn-secondary btn-sm">Message</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($job['status'] === 'completed' && $currentUser): ?>
    <div class="card mt-2" id="review">
        <h3>Leave a Review</h3>
        <form method="POST" action="submit-review.php">
            <input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
            <div class="form-group">
                <label for="rating">Rating (1–5)</label>
                <select name="rating" id="rating" required>
                    <option value="5">5 — Excellent</option>
                    <option value="4">4 — Good</option>
                    <option value="3">3 — Average</option>
                    <option value="2">2 — Below Average</option>
                    <option value="1">1 — Poor</option>
                </select>
            </div>
            <div class="form-group">
                <label for="comment">Comment</label>
                <textarea name="comment" id="comment" placeholder="How was your experience?"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
