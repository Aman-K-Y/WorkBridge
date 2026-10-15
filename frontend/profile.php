<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: browse-jobs.php');
    exit;
}

$currentUser = $_SESSION['user'] ?? null;
$isSelf = $currentUser && ((int)$currentUser['user_id'] === (int)$userId);

$successMsg = '';
$errorMsg = '';

// Handle Profile Updates (by the user themselves)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isSelf) {
    $status = trim($_POST['status'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $higherQual = trim($_POST['higher_qualification'] ?? '');
    $skills = trim($_POST['skills'] ?? '');

    $updateData = [];
    if ($status !== '') $updateData['status'] = $status;
    if ($phoneNumber !== '') $updateData['phone_number'] = $phoneNumber;

    if ($currentUser['role'] === 'freelancer') {
        if ($higherQual !== '') $updateData['higher_qualification'] = $higherQual;
        if ($skills !== '') $updateData['skills'] = $skills;

        // Handle Resume PDF Upload
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['resume'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $errorMsg = 'Only PDF resume files (.pdf) are allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errorMsg = 'Resume file size cannot exceed 5MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/resumes/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = 'resume_user_' . (int)$userId . '_' . time() . '.pdf';
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $updateData['resume_url'] = 'uploads/resumes/' . $filename;
                } else {
                    $errorMsg = 'Failed to save uploaded resume file.';
                }
            }
        }
    }

    if (!$errorMsg && !empty($updateData)) {
        $updateRes = apiPut('/users/profile', $updateData, true);
        if ($updateRes['status'] === 200) {
            $successMsg = 'Profile verification details updated successfully!';
            $_SESSION['user'] = array_merge($_SESSION['user'], $updateRes['body']['profile']);
        } else {
            $errorMsg = $updateRes['body']['error'] ?? 'Failed to update profile.';
        }
    }
}

// Fetch user profile from API (with privacy filter applied based on token)
$profileResult = apiGet("/users/$userId", true);
if ($profileResult['status'] !== 200) {
    $pageTitle = 'Profile Not Found';
    require_once 'includes/header.php';
    echo '<div class="empty-state"><p>This profile could not be found.</p></div>';
    require_once 'includes/footer.php';
    exit;
}
$profile = $profileResult['body']['profile'];

// Fetch reviews
$reviewsResult = apiGet("/reviews/user/$userId");
$reviewData = ($reviewsResult['status'] === 200) ? $reviewsResult['body'] : ['reviews' => [], 'average_rating' => null, 'total_reviews' => 0];

$pageTitle = $profile['name'] . ' Profile';
require_once 'includes/header.php';

$nameParts = explode(' ', trim($profile['name']));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

$completion = $profile['completion'] ?? ['percentage' => 50, 'is_complete' => false, 'missing_fields' => []];
?>

<?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

<!-- ================= Profile Header ================= -->
<div class="profile-header-card">
    <div class="profile-top">
        <div class="profile-user-lockup">
            <div class="profile-avatar-large" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></div>
            <div>
                <h1 class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></h1>
                <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
                    <span class="badge badge-<?php echo ($profile['role'] === 'client') ? 'open' : 'pending'; ?>">
                        <?php echo ucfirst($profile['role']); ?>
                    </span>

                    <?php if (!empty($profile['status'])): ?>
                        <span class="verification-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Verified <?php echo ucfirst($profile['status']); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($reviewData['average_rating']): ?>
                        <span class="rating-stars" style="font-size:0.95rem;">
                            <?php echo str_repeat('★', round($reviewData['average_rating'])) . str_repeat('☆', 5 - round($reviewData['average_rating'])); ?>
                            <span class="text-muted" style="font-size:0.85rem;"> <?php echo $reviewData['average_rating']; ?>/5 (<?php echo $reviewData['total_reviews']; ?>)</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($isSelf): ?>
            <button type="button" class="btn btn-outline btn-sm" id="toggle-edit-btn" onclick="toggleEditProfile()">
                ✏️ Edit Verification Info
            </button>
        <?php endif; ?>
    </div>

    <!-- ================= Completion Percentage Widget (for current user) ================= -->
    <?php if ($isSelf): ?>
        <div class="completion-box">
            <div class="completion-header">
                <span class="completion-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Profile Verification & Trust Score
                </span>
                <span class="completion-pct"><?php echo $completion['percentage']; ?>% Complete</span>
            </div>
            
            <div class="progress-track" role="progressbar" aria-valuenow="<?php echo $completion['percentage']; ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-fill" style="width: <?php echo $completion['percentage']; ?>%;"></div>
            </div>

            <?php if (!$completion['is_complete'] && !empty($completion['missing_fields'])): ?>
                <div style="font-size:0.85rem; color:var(--ink-soft); margin-top:0.4rem;">
                    <strong>Pending information to reach 100% verified status:</strong>
                    <ul class="missing-list">
                        <?php foreach ($completion['missing_fields'] as $missing): ?>
                            <li>Add your <strong><?php echo htmlspecialchars($missing); ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p style="font-size:0.85rem; color:var(--success); margin:0; font-weight:600;">
                    🎉 Excellent! Your profile information is 100% complete and verified.
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ================= Info Grid ================= -->
    <div class="profile-info-grid">
        <!-- 1. Email (Visible to everyone) -->
        <div class="info-item">
            <div class="info-item-label">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Email Address
            </div>
            <div class="info-item-value"><?php echo htmlspecialchars($profile['email']); ?></div>
        </div>

        <!-- 2. Status (Student / Professional / Unemployed) -->
        <div class="info-item">
            <div class="info-item-label">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Current Status
            </div>
            <div class="info-item-value">
                <?php echo !empty($profile['status']) ? ucfirst($profile['status']) : '<span class="text-muted" style="font-size:0.88rem; font-weight:400;">Not specified yet</span>'; ?>
            </div>
        </div>

        <!-- 3. Phone Number (Privacy-aware for Client) -->
        <div class="info-item">
            <div class="info-item-label">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                Phone Number
            </div>
            <?php if (!empty($profile['phone_hidden'])): ?>
                <div class="info-item-value locked">
                    🔒 Confidential
                    <div style="font-size:0.75rem; color:var(--slate); margin-top:2px;">
                        <?php echo htmlspecialchars($profile['phone_hidden_reason'] ?? 'Shared only after proposal acceptance'); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-item-value">
                    <?php echo !empty($profile['phone_number']) ? htmlspecialchars($profile['phone_number']) : '<span class="text-muted" style="font-size:0.88rem; font-weight:400;">Not specified</span>'; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Freelancer Specific: Higher Qualification -->
        <?php if ($profile['role'] === 'freelancer'): ?>
            <div class="info-item">
                <div class="info-item-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                    Higher Qualification
                </div>
                <div class="info-item-value">
                    <?php echo !empty($profile['higher_qualification']) ? htmlspecialchars($profile['higher_qualification']) : '<span class="text-muted" style="font-size:0.88rem; font-weight:400;">Not specified</span>'; ?>
                </div>
            </div>

            <!-- Freelancer Specific: Skills -->
            <div class="info-item">
                <div class="info-item-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    Skills
                </div>
                <div class="info-item-value">
                    <?php if (!empty($profile['skills'])): ?>
                        <div style="display:flex; flex-wrap:wrap; gap:0.35rem; margin-top:3px;">
                            <?php foreach (explode(',', $profile['skills']) as $sk): ?>
                                <span class="skill-pill"><?php echo htmlspecialchars(trim($sk)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:0.88rem; font-weight:400;">No skills listed</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Freelancer Specific: Resume PDF -->
            <div class="info-item">
                <div class="info-item-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    Resume / CV
                </div>
                <div class="info-item-value">
                    <?php if (!empty($profile['resume_url'])): ?>
                        <a href="<?php echo htmlspecialchars($profile['resume_url']); ?>" target="_blank" class="btn btn-outline btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.75rem;">
                            📄 View Resume (PDF) &rarr;
                        </a>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:0.88rem; font-weight:400;">No resume uploaded</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ================= Edit Profile Form Modal / Section ================= -->
<?php if ($isSelf): ?>
    <div class="card mt-2" id="edit-profile-section" style="<?php echo (!$completion['is_complete'] || isset($_GET['edit'])) ? 'display:block;' : 'display:none;'; ?>">
        <h3>Update Verification & Personal Info</h3>
        <p class="text-muted" style="font-size:0.88rem; margin-bottom:1.5rem;">
            Ensure your details are accurate to build trust with fellow clients and freelancers on WorkBridge.
        </p>

        <form method="POST" enctype="multipart/form-data">
            <?php if ($currentUser['role'] === 'client'): ?>
                <div class="form-group">
                    <label for="status">Current Status</label>
                    <select name="status" id="status" required>
                        <option value="">Select your status</option>
                        <option value="student" <?php echo ($profile['status'] === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="professional" <?php echo ($profile['status'] === 'professional') ? 'selected' : ''; ?>>Professional</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone_number">Contact Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>" placeholder="+91 9876543210" required>
                    <small class="text-muted">🔒 Confidential: Your phone number will only be visible to freelancers whose proposal you accept.</small>
                </div>

            <?php else: ?>
                <!-- Freelancer Fields -->
                <div class="form-group">
                    <label for="status">Current Status</label>
                    <select name="status" id="status" required>
                        <option value="">Select your status</option>
                        <option value="student" <?php echo ($profile['status'] === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="unemployed" <?php echo ($profile['status'] === 'unemployed') ? 'selected' : ''; ?>>Unemployed</option>
                        <option value="professional" <?php echo ($profile['status'] === 'professional') ? 'selected' : ''; ?>>Professional</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="higher_qualification">Higher Qualification</label>
                    <input type="text" name="higher_qualification" id="higher_qualification" value="<?php echo htmlspecialchars($profile['higher_qualification'] ?? ''); ?>" placeholder="e.g. B.Tech in Computer Science, MBA, etc." required>
                </div>

                <div class="form-group">
                    <label for="skills">Skills (comma separated)</label>
                    <input type="text" name="skills" id="skills" value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>" placeholder="e.g. Python, Flask, React, MongoDB" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Contact Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>" placeholder="+91 9876543210" required>
                </div>

                <div class="form-group">
                    <label for="resume">Resume / CV (PDF file only)</label>
                    <input type="file" name="resume" id="resume" accept="application/pdf">
                    <?php if (!empty($profile['resume_url'])): ?>
                        <small class="text-muted" style="display:block; margin-top:4px;">
                            Current file: <a href="<?php echo htmlspecialchars($profile['resume_url']); ?>" target="_blank">View uploaded PDF</a> (upload new file to replace)
                        </small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">Save Verification Details</button>
                <button type="button" class="btn btn-outline" onclick="toggleEditProfile()">Cancel</button>
            </div>
        </form>
    </div>

    <script>
    function toggleEditProfile() {
        const sec = document.getElementById('edit-profile-section');
        if (sec.style.display === 'none' || sec.style.display === '') {
            sec.style.display = 'block';
            sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            sec.style.display = 'none';
        }
    }
    </script>
<?php endif; ?>

<!-- ================= Reviews Section ================= -->
<div class="card mt-2">
    <h3>Client & Freelancer Reviews</h3>
    <?php if (empty($reviewData['reviews'])): ?>
        <p class="text-muted">This user hasn't received any reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviewData['reviews'] as $review): ?>
            <div class="review-card">
                <p class="rating-stars" style="font-size:0.95rem;">
                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                </p>
                <p><?php echo htmlspecialchars($review['comment']); ?></p>
                <p class="text-muted" style="font-size:0.85rem;">— <?php echo htmlspecialchars($review['reviewer_name']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
