<?php
require_once 'includes/config.php';
require_once 'includes/api.php';
requireLogin();

$user = $_SESSION['user'];

if ($user['role'] === 'client') {
    $result = apiGet('/jobs/my-jobs', true);
    $jobs = $result['body']['jobs'] ?? [];
} else {
    $result = apiGet('/proposals/my-proposals', true);
    $proposals = $result['body']['proposals'] ?? [];
}

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
?>

<h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?></h2>
<p class="text-muted"><?php echo ucfirst($user['role']); ?> Dashboard</p>

<?php if ($user['role'] === 'client'): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin: 1.5rem 0;">
        <h3>My Posted Jobs</h3>
        <a href="post-job.php" class="btn btn-primary btn-sm">+ Post a New Job</a>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="empty-state">
            <p>You haven't posted any jobs yet.</p>
            <a href="post-job.php" class="btn btn-primary mt-1">Post Your First Job</a>
        </div>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
            <div class="card job-card">
                <div style="display:flex; justify-content:space-between; align-items:start;">
                    <h3><a href="job-details.php?id=<?php echo $job['job_id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></h3>
                    <span class="job-budget">₹<?php echo number_format($job['budget']); ?></span>
                </div>
                <span class="badge badge-<?php echo $job['status']; ?>"><?php echo str_replace('_', ' ', $job['status']); ?></span>
                <p class="mt-1"><a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="btn btn-outline btn-sm">Manage &rarr;</a></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else: ?>
    <h3 class="mt-2">My Proposals</h3>

    <?php if (empty($proposals)): ?>
        <div class="empty-state">
            <p>You haven't submitted any proposals yet.</p>
            <a href="browse-jobs.php" class="btn btn-primary mt-1">Browse Open Jobs</a>
        </div>
    <?php else: ?>
        <?php foreach ($proposals as $p): ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:start;">
                    <h4><a href="job-details.php?id=<?php echo $p['job_id']; ?>">Job #<?php echo $p['job_id']; ?></a></h4>
                    <span class="job-budget">₹<?php echo number_format($p['bid_amount']); ?></span>
                </div>
                <span class="badge badge-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span>
                <p class="text-muted mt-1"><?php echo htmlspecialchars($p['cover_note']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
