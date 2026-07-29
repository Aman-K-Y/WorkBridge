<?php
require_once 'includes/config.php';
require_once 'includes/api.php';
requireRole('client');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = $_POST['budget'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $requiredSkills = trim($_POST['required_skills'] ?? '');
    $deadline = $_POST['deadline'] ?? '';

    if (!$title || !$description || !$budget) {
        $error = 'Title, description, and budget are required.';
    } else {
        $result = apiPost('/jobs', [
            'title' => $title,
            'description' => $description,
            'budget' => (float) $budget,
            'category' => $category ?: null,
            'required_skills' => $requiredSkills ?: null,
            'deadline' => $deadline ?: null,
        ], true);

        if ($result['status'] === 201) {
            header('Location: job-details.php?id=' . $result['body']['job']['job_id']);
            exit;
        } else {
            $error = $result['body']['error'] ?? 'Could not post job. Please try again.';
        }
    }
}

$pageTitle = 'Post a Job';
require_once 'includes/header.php';
?>

<div class="form-container wide">
    <h2>Post a Job</h2>
    <p class="text-muted" style="margin-bottom:1.5rem;">No fees to post, no commission when you hire. Ever.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="post-job.php">
        <div class="form-group">
            <label for="title">Job title</label>
            <input type="text" id="title" name="title" placeholder="e.g. Build a responsive portfolio website" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Describe what you need done, any requirements, and expectations." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" placeholder="e.g. Web Development, Graphic Design" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="required_skills">Required skills <span class="text-muted" style="font-weight:400;">(comma-separated)</span></label>
            <input type="text" id="required_skills" name="required_skills" placeholder="e.g. Python, React, MongoDB" value="<?php echo htmlspecialchars($_POST['required_skills'] ?? ''); ?>">
            <p class="text-muted" style="font-size:0.8rem; margin-top:0.3rem;">Used to recommend freelancers whose skills match this job.</p>
        </div>

        <div class="form-group">
            <label for="budget">Budget (₹)</label>
            <input type="number" id="budget" name="budget" min="0" step="1" value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="deadline">Deadline (optional)</label>
            <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Post Job</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
