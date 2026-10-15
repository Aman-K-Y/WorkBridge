<?php
require_once 'includes/config.php';
require_once 'includes/api.php';
requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];

// Extract user initials for avatar
$nameParts = explode(' ', trim($user['name']));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_job') {
    $cancelId = (int)($_POST['job_id'] ?? 0);
    if ($cancelId > 0) {
        $cancelResult = apiPost("/jobs/$cancelId/cancel", [], true);
        if ($cancelResult['status'] === 200) {
            $successMsg = "Job #$cancelId has been withdrawn successfully.";
        } else {
            $errorMsg = $cancelResult['body']['error'] ?? "Failed to withdraw job.";
        }
    }
}

if ($role === 'client') {
    $result = apiGet('/jobs/my-jobs', true);
    $jobs = $result['body']['jobs'] ?? [];

    $totalJobs = count($jobs);
    $openJobs = count(array_filter($jobs, fn($j) => $j['status'] === 'open'));
    $inProgressJobs = count(array_filter($jobs, fn($j) => $j['status'] === 'in_progress'));
    $completedJobs = count(array_filter($jobs, fn($j) => $j['status'] === 'completed'));
    $cancelledJobs = count(array_filter($jobs, fn($j) => $j['status'] === 'cancelled'));
    $totalBudget = array_sum(array_column($jobs, 'budget'));
} else {
    $result = apiGet('/proposals/my-proposals', true);
    $proposals = $result['body']['proposals'] ?? [];

    $totalProposals = count($proposals);
    $pendingProposals = count(array_filter($proposals, fn($p) => $p['status'] === 'pending'));
    $acceptedProposals = count(array_filter($proposals, fn($p) => $p['status'] === 'accepted'));
    $rejectedProposals = count(array_filter($proposals, fn($p) => $p['status'] === 'rejected'));
    $totalWonValue = array_sum(array_map(fn($p) => ($p['status'] === 'accepted') ? $p['bid_amount'] : 0, $proposals));
}

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
?>

<?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

<!-- ================= Dashboard Hero Banner ================= -->
<div class="dashboard-hero">
    <div class="dashboard-profile-lockup">
        <div class="dashboard-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></div>
        <div class="dashboard-profile-info">
            <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?></h1>
            <span class="dashboard-badge-pill">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <?php echo ucfirst($role); ?> Account
            </span>
        </div>
    </div>
    <div class="dashboard-actions">
        <?php if ($role === 'client'): ?>
            <a href="browse-jobs.php" class="btn btn-outline btn-sm">Explore Market</a>
            <a href="post-job.php" class="btn btn-primary btn-sm" role="button" aria-label="Post a New Job">+ Post a New Job</a>
        <?php else: ?>
            <a href="profile.php?id=<?php echo $user['user_id']; ?>" class="btn btn-outline btn-sm">My Portfolio</a>
            <a href="browse-jobs.php" class="btn btn-primary btn-sm" role="button" aria-label="Find New Jobs">Browse Open Jobs &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<!-- ================= KPI Summary Metric Cards ================= -->
<div class="dashboard-stats">
    <?php if ($role === 'client'): ?>
        <div class="stat-card">
            <div class="stat-icon primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $totalJobs; ?></span>
                <span class="stat-label">Total Jobs Posted</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $openJobs; ?></span>
                <span class="stat-label">Open For Proposals</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $inProgressJobs; ?></span>
                <span class="stat-label">In Progress Contracts</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value">₹<?php echo number_format($totalBudget); ?></span>
                <span class="stat-label">Total Budget Committed</span>
            </div>
        </div>

    <?php else: ?>
        <div class="stat-card">
            <div class="stat-icon primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $totalProposals; ?></span>
                <span class="stat-label">Submitted Proposals</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $acceptedProposals; ?></span>
                <span class="stat-label">Accepted Contracts</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $pendingProposals; ?></span>
                <span class="stat-label">Pending Client Review</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="stat-details">
                <span class="stat-value">₹<?php echo number_format($totalWonValue); ?></span>
                <span class="stat-label">Contract Value Won</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ================= Section Header & Filter Toolbar ================= -->
<div class="dashboard-section-header">
    <h2><?php echo ($role === 'client') ? 'My Job Listings' : 'My Project Proposals'; ?></h2>
    
    <div class="dashboard-toolbar">
        <div class="dashboard-search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="dash-search-input" placeholder="Quick search..." aria-label="Search items">
        </div>

        <div class="tab-filter-group" role="tablist">
            <button type="button" class="tab-btn active" data-filter="all">
                All <span class="tab-count"><?php echo ($role === 'client') ? $totalJobs : $totalProposals; ?></span>
            </button>
            <?php if ($role === 'client'): ?>
                <button type="button" class="tab-btn" data-filter="open">
                    Open <span class="tab-count"><?php echo $openJobs; ?></span>
                </button>
                <button type="button" class="tab-btn" data-filter="in_progress">
                    In Progress <span class="tab-count"><?php echo $inProgressJobs; ?></span>
                </button>
                <button type="button" class="tab-btn" data-filter="completed">
                    Completed <span class="tab-count"><?php echo $completedJobs; ?></span>
                </button>
                <button type="button" class="tab-btn" data-filter="cancelled">
                    Cancelled <span class="tab-count"><?php echo $cancelledJobs; ?></span>
                </button>
            <?php else: ?>
                <button type="button" class="tab-btn" data-filter="pending">
                    Pending <span class="tab-count"><?php echo $pendingProposals; ?></span>
                </button>
                <button type="button" class="tab-btn" data-filter="accepted">
                    Accepted <span class="tab-count"><?php echo $acceptedProposals; ?></span>
                </button>
                <button type="button" class="tab-btn" data-filter="rejected">
                    Rejected <span class="tab-count"><?php echo $rejectedProposals; ?></span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ================= Main Grid ================= -->
<div class="job-grid" id="dashboard-items-grid">
    <?php if ($role === 'client'): ?>
        <?php if (empty($jobs)): ?>
            <div class="empty-state-card">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <h3>No jobs posted yet</h3>
                <p>Post your project requirements and receive competitive proposals from vetted freelancers within minutes.</p>
                <a href="post-job.php" class="btn btn-primary" role="button" aria-label="Post First Job">+ Post Your First Job</a>
            </div>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <?php 
                    $skills = array_filter(array_map('trim', explode(',', $job['required_skills'] ?? '')));
                    $dateStr = $job['created_at'] ? date('M j, Y', strtotime($job['created_at'])) : '';
                ?>
                <div class="dash-card" data-status="<?php echo htmlspecialchars($job['status']); ?>" data-text="<?php echo htmlspecialchars(strtolower($job['title'] . ' ' . $job['description'] . ' ' . ($job['category'] ?? '') . ' ' . ($job['required_skills'] ?? ''))); ?>">
                    <div>
                        <div class="dash-card-header">
                            <h3 class="dash-card-title">
                                <a href="job-details.php?id=<?php echo $job['job_id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a>
                            </h3>
                            <span class="badge badge-<?php echo $job['status']; ?> dash-card-badge">
                                <?php echo str_replace('_', ' ', $job['status']); ?>
                            </span>
                        </div>

                        <div class="dash-card-meta">
                            <?php if (!empty($job['category'])): ?>
                                <span class="dash-card-meta-item">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                    <?php echo htmlspecialchars($job['category']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($dateStr): ?>
                                <span class="dash-card-meta-item">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    Posted <?php echo $dateStr; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="dash-card-desc"><?php echo htmlspecialchars($job['description']); ?></p>

                        <?php if (!empty($skills)): ?>
                            <div class="dash-skills-list">
                                <?php foreach (array_slice($skills, 0, 4) as $s): ?>
                                    <span class="skill-pill"><?php echo htmlspecialchars($s); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($skills) > 4): ?>
                                    <span class="skill-pill">+<?php echo count($skills) - 4; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dash-card-footer">
                        <div class="dash-budget">
                            <span class="dash-budget-caption">Project Budget</span>
                            <span class="dash-budget-amount">₹<?php echo number_format($job['budget']); ?></span>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <?php if ($job['status'] === 'open'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to withdraw this job? Any pending proposals will be rejected.');">
                                    <input type="hidden" name="action" value="cancel_job">
                                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger); border-color:var(--danger);" role="button" aria-label="Withdraw Job">
                                        Withdraw
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="btn btn-outline btn-sm" role="button" aria-label="Manage Job">
                                Manage &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>
        <?php if (empty($proposals)): ?>
            <div class="empty-state-card">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <h3>No proposals submitted yet</h3>
                <p>Browse through hundreds of open client projects matching your skillset and start winning contracts.</p>
                <a href="browse-jobs.php" class="btn btn-primary" role="button" aria-label="Browse Jobs">Find Open Opportunities</a>
            </div>
        <?php else: ?>
            <?php foreach ($proposals as $p): ?>
                <?php $dateStr = $p['created_at'] ? date('M j, Y', strtotime($p['created_at'])) : ''; ?>
                <div class="dash-card" data-status="<?php echo htmlspecialchars($p['status']); ?>" data-text="<?php echo htmlspecialchars(strtolower($p['job_title'] . ' ' . $p['cover_note'] . ' ' . $p['status'])); ?>">
                    <div>
                        <div class="dash-card-header">
                            <h3 class="dash-card-title">
                                <a href="job-details.php?id=<?php echo $p['job_id']; ?>"><?php echo htmlspecialchars($p['job_title'] ?? ('Job #' . $p['job_id'])); ?></a>
                            </h3>
                            <span class="badge badge-<?php echo $p['status']; ?> dash-card-badge">
                                <?php echo ucfirst($p['status']); ?>
                            </span>
                        </div>

                        <div class="dash-card-meta">
                            <?php if ($dateStr): ?>
                                <span class="dash-card-meta-item">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    Submitted <?php echo $dateStr; ?>
                                </span>
                            <?php endif; ?>
                            <span class="dash-card-meta-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                Job Status: <?php echo ucfirst($p['job_status'] ?? 'Active'); ?>
                            </span>
                        </div>

                        <?php if (!empty($p['cover_note'])): ?>
                            <div class="proposal-cover-preview">
                                "<?php echo htmlspecialchars($p['cover_note']); ?>"
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dash-card-footer">
                        <div class="dash-budget">
                            <span class="dash-budget-caption">Your Proposed Bid</span>
                            <span class="dash-budget-amount">₹<?php echo number_format($p['bid_amount']); ?></span>
                        </div>
                        <a href="job-details.php?id=<?php echo $p['job_id']; ?>" class="btn btn-outline btn-sm" role="button" aria-label="View Job Details">
                            View Discussion &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Pure client-side instantaneous filtering & search
document.addEventListener('DOMContentLoaded', function () {
    const filterBtns = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('dash-search-input');
    const cards = document.querySelectorAll('#dashboard-items-grid .dash-card');

    let currentFilter = 'all';
    let currentQuery = '';

    function applyFilters() {
        let visibleCount = 0;
        cards.forEach(card => {
            const status = card.getAttribute('data-status');
            const text = card.getAttribute('data-text') || '';

            const matchesStatus = (currentFilter === 'all') || (status === currentFilter);
            const matchesQuery = !currentQuery || text.includes(currentQuery);

            if (matchesStatus && matchesQuery) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentQuery = this.value.trim().toLowerCase();
            applyFilters();
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
