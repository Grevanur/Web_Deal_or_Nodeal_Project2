<?php
// index.php
require_once 'config.php';
require_once 'functions.php';

$theme = dond_get_theme();

// Handle theme selection or new game
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['theme'])) {
        $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
        setcookie('dond_theme', $theme, time() + 30 * 24 * 3600, '/');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'new') {
        dond_reset_game();
        dond_init_game();
        header('Location: game.php');
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'resume') {
        // just go to game; state stays in session
        header('Location: game.php');
        exit;
    }
}

$can_resume = !empty($_COOKIE['dond_can_resume']) && dond_has_active_game();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deal or No Deal - High-Stakes PHP Vault</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="theme-<?php echo htmlspecialchars($theme); ?>">
<div class="container">
    <h1>Deal or No Deal: High-Stakes PHP Vault</h1>
    <p class="subtitle">Choose your vault and face the Banker.</p>

    <form method="post" class="card">
        <h2>Theme</h2>
        <label>
            <input type="radio" name="theme" value="dark" <?php echo $theme === 'dark' ? 'checked' : ''; ?>>
            Dark Casino
        </label>
        <label>
            <input type="radio" name="theme" value="light" <?php echo $theme === 'light' ? 'checked' : ''; ?>>
            Light Minimal
        </label>
        <div class="actions">
            <button type="submit" class="btn secondary">Save Theme</button>
        </div>
    </form>

    <form method="post" class="card">
        <h2>Game</h2>
        <p>Start a new high-stakes game or resume your last session.</p>
        <div class="actions">
            <button type="submit" name="action" value="new" class="btn primary">Start New Game</button>
            <?php if ($can_resume): ?>
                <button type="submit" name="action" value="resume" class="btn">Resume Game</button>
            <?php endif; ?>
        </div>
    </form>

    <form action="about.php" class="card">
        <h2>Learn the Game</h2>
        <p>Read the rules and learn about Banker strategies and market events.</p>
        <div class="actions">
            <button type="submit" class="btn secondary">View Rules & About</button>
        </div>
    </form>

    <?php if ($can_resume): ?>
        <div class="card">
            <h2>Game Analytics</h2>
            <p>View detailed statistics and analysis of your current game.</p>
            <div class="actions">
                <a href="analytics.php" class="btn secondary">View Analytics</a>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>