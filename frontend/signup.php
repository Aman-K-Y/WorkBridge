<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$name || !$email || !$password || !$role) {
        $error = 'Please fill in all fields and choose a role.';
    } else {
        $result = apiPost('/auth/signup', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        if ($result['status'] === 201) {
            $_SESSION['token'] = $result['body']['token'];
            $_SESSION['user'] = $result['body']['user'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['body']['error'] ?? 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Sign Up';
require_once 'includes/header.php';
?>

<div class="form-container">
    <h2 class="text-center">Create your account</h2>
    <p class="text-muted text-center mt-1" style="margin-bottom:1.5rem;">Join WorkBridge — free, no commission, ever.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="signup.php">
        <div class="role-toggle">
            <label>
                <input type="radio" name="role" value="client" <?php echo (($_POST['role'] ?? '') === 'client') ? 'checked' : ''; ?>>
                <span>I'm a Client</span>
            </label>
            <label>
                <input type="radio" name="role" value="freelancer" <?php echo (($_POST['role'] ?? '') === 'freelancer') ? 'checked' : ''; ?>>
                <span>I'm a Freelancer</span>
            </label>
        </div>

        <div class="form-group">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-input-wrap">
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password">
                    <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <p class="text-center text-muted mt-1">Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
