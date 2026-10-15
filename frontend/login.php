<?php
require_once 'includes/config.php';
require_once 'includes/api.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } else {
        $result = apiPost('/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($result['status'] === 200) {
            $_SESSION['token'] = $result['body']['token'];
            $_SESSION['user'] = $result['body']['user'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['body']['error'] ?? 'Login failed. Please try again.';
        }
    }
}

$pageTitle = 'Log In';
require_once 'includes/header.php';
?>

<div class="form-container">
    <h2 class="text-center">Welcome back</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-input-wrap">
                <input type="password" id="password" name="password" required autocomplete="current-password">
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

        <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <p class="text-center text-muted mt-1">Don't have an account? <a href="signup.php">Sign up</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
