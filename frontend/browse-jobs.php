<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

$category = $_GET['category'] ?? '';
$minBudget = $_GET['min_budget'] ?? '';
$maxBudget = $_GET['max_budget'] ?? '';

$query = [];
if ($category) $query['category'] = $category;
if ($minBudget) $query['min_budget'] = $minBudget;
if ($maxBudget) $query['max_budget'] = $maxBudget;

$endpoint = '/jobs' . (count($query) ? '?' . http_build_query($query) : '');
$result = apiGet($endpoint);
$jobs = $result['body']['jobs'] ?? [];

$pageTitle = 'Browse Jobs';
require_once 'includes/header.php';
?>

<h2>Browse Jobs</h2>
<p class="text-muted">Open opportunities posted by clients. No fees — connect and get hired directly.</p>

<form method="GET" action="browse-jobs.php" class="filters-bar">
    <input type="text" name="category" placeholder="Category (e.g. Web Development)" value="<?php echo htmlspecialchars($category); ?>">
    <input type="number" name="min_budget" placeholder="Min budget" value="<?php echo htmlspecialchars($minBudget); ?>">
    <input type="number" name="max_budget" placeholder="Max budget" value="<?php echo htmlspecialchars($maxBudget); ?>">
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($category || $minBudget || $maxBudget): ?>
        <a href="browse-jobs.php" class="btn btn-outline btn-sm">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($jobs)): ?>
    <div class="empty-state">
        <p>No jobs match these filters right now.</p>
        <?php if (isLoggedIn() && $_SESSION['user']['role'] === 'client'): ?>
            <a href="post-job.php" class="btn btn-primary mt-1">Post the first one</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($jobs as $job): ?>
        <div class="card job-card">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <h3><a href="job-details.php?id=<?php echo $job['job_id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></h3>
                <span class="job-budget">₹<?php echo number_format($job['budget']); ?></span>
            </div>
            <div class="job-meta">
                <?php if ($job['category']): ?><span><?php echo htmlspecialchars($job['category']); ?></span><?php endif; ?>
                <span class="badge badge-<?php echo $job['status']; ?>"><?php echo str_replace('_', ' ', $job['status']); ?></span>
                <?php if ($job['deadline']): ?><span>Due <?php echo htmlspecialchars($job['deadline']); ?></span><?php endif; ?>
            </div>
            <p><?php echo htmlspecialchars(substr($job['description'], 0, 150)); ?><?php echo strlen($job['description']) > 150 ? '…' : ''; ?></p>
            <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="btn btn-outline btn-sm">View Details</a>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
