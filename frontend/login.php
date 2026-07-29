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
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <p class="text-center text-muted mt-1">Don't have an account? <a href="signup.php">Sign up</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
