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
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <p class="text-center text-muted mt-1">Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
