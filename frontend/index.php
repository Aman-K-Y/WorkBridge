<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="eyebrow">Zero commission. Ever.</div>
    <h1>Where clients and freelancers<br>connect directly.</h1>
    <p>WorkBridge is a free freelance marketplace. No platform fees, no commission cuts — just people getting hired, and freelancers keeping what they earn.</p>
    <div class="hero-cta">
        <?php if (isLoggedIn()): ?>
            <a href="browse-jobs.php" class="btn btn-primary">Browse Jobs</a>
            <a href="dashboard.php" class="btn btn-outline">Go to Dashboard</a>
        <?php else: ?>
            <a href="signup.php" class="btn btn-primary">Get Started</a>
            <a href="browse-jobs.php" class="btn btn-outline">Browse Jobs</a>
        <?php endif; ?>
    </div>
</section>

<div class="bridge-divider">
    <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
</div>

<section class="mt-2">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <div class="eyebrow">For Clients</div>
            <h3>Post a job in minutes</h3>
            <p class="text-muted">Describe what you need, set your budget, and start receiving proposals from freelancers — no fees deducted from either side.</p>
        </div>
        <div class="card">
            <div class="eyebrow">For Freelancers</div>
            <h3>Keep 100% of what you earn</h3>
            <p class="text-muted">Browse open jobs, submit proposals, and get paid directly by clients. WorkBridge never takes a cut.</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
