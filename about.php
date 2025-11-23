<?php
// about.php - Rules and information page
require_once 'config.php';
$theme = dond_get_theme();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About & Rules - Deal or No Deal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="theme-<?php echo htmlspecialchars($theme); ?>">
<div class="container">
    <header class="game-header">
        <div>
            <h1>Deal or No Deal</h1>
            <p class="subtitle">Game Rules & Information</p>
        </div>
        <a href="index.php" class="btn secondary">Back to Home</a>
    </header>

    <div class="card">
        <h2>How to Play</h2>
        <ol style="margin-left: 1.5rem; margin-top: 0.5rem;">
            <li><strong>Choose Your Case:</strong> Select one briefcase as your main case at the start</li>
            <li><strong>Open Cases:</strong> Open other cases to reveal and eliminate their values</li>
            <li><strong>Banker Offers:</strong> After certain rounds, the Banker will make you an offer</li>
            <li><strong>Decide:</strong> Accept the offer (DEAL) and end the game, or continue (NO DEAL)</li>
            <li><strong>Final Reveal:</strong> If you refuse all offers, you keep what's in your original case</li>
        </ol>
    </div>

    <div class="card">
        <h2>Banker's Advanced Strategy</h2>
        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
            <li>The Banker's offers are based on remaining values and market conditions</li>
            <li>Market events can make the Banker more generous or cautious</li>
            <li>Your risk-taking behavior influences future offers</li>
            <li>High-value cases remaining = lower offers</li>
            <li>Aggressive players receive less generous offers</li>
            <li>Game phase affects offer calculations (early vs late game)</li>
        </ul>
    </div>

    <div class="card">
        <h2>Volatile Market Events</h2>
        <p>Random market events can occur during the game, affecting the Banker's offers:</p>
        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
            <li>📉 <strong>Market Crash:</strong> Banker becomes more cautious (-15%)</li>
            <li>📈 <strong>Economic Boom:</strong> Banker becomes more generous (+10%)</li>
            <li>⚡ <strong>High Volatility:</strong> More unpredictable offers (+25%)</li>
            <li>🎭 <strong>Banker Bluff:</strong> Psychological pressure tactics (-8%)</li>
            <li>💸 <strong>Interest Rate Hike:</strong> Banker offers less (-12%)</li>
            <li>🚀 <strong>Market Rally:</strong> Better offers available (+15%)</li>
        </ul>
    </div>

    <div class="card">
        <h2>Advanced Features</h2>
        <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
            <li><strong>Player Analytics:</strong> Track your risk profile and strategy performance</li>
            <li><strong>Dynamic Difficulty:</strong> Game adapts to your skill level</li>
            <li><strong>Statistical Analysis:</strong> Detailed post-game analytics and insights</li>
            <li><strong>Game History:</strong> Complete record of all decisions and events</li>
            <li><strong>Strategy Scoring:</strong> Evaluate your decision-making against optimal play</li>
        </ul>
    </div>
</div>
</body>
</html>