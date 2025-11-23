<?php
// analytics.php - Game statistics and analysis
require_once 'config.php';
require_once 'functions.php';

$theme = dond_get_theme();
$state = dond_get_state();
$stats = dond_get_game_statistics($state);
$event_history = dond_get_event_history($state);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Analytics - Deal or No Deal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="theme-<?php echo htmlspecialchars($theme); ?>">
<div class="container">
    <header class="game-header">
        <div>
            <h1>Game Analytics</h1>
            <p class="subtitle">Detailed statistics and performance analysis</p>
        </div>
        <div class="actions">
            <a href="game.php" class="btn secondary">Back to Game</a>
            <a href="index.php" class="btn">Home</a>
        </div>
    </header>

    <div class="layout">
        <div class="card">
            <h2>Player Profile</h2>
            <div class="stats-grid large">
                <div class="stat">
                    <label>Risk Tendency</label>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $stats['player_risk_tendency']; ?>%"></div>
                    </div>
                    <span><?php echo $stats['player_risk_tendency']; ?>%</span>
                </div>
                <div class="stat">
                    <label>Aggression Score</label>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $stats['player_aggression_score']; ?>%"></div>
                    </div>
                    <span><?php echo $stats['player_aggression_score']; ?>%</span>
                </div>
                <div class="stat">
                    <label>Strategy Score</label>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $stats['optimal_strategy_score']; ?>%"></div>
                    </div>
                    <span><?php echo $stats['optimal_strategy_score']; ?>%</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Game Performance</h2>
            <div class="stats-grid">
                <div class="stat">
                    <label>Total Offers</label>
                    <span class="big-number"><?php echo $stats['total_offers_received']; ?></span>
                </div>
                <div class="stat">
                    <label>Offers Declined</label>
                    <span class="big-number"><?php echo $stats['offers_declined']; ?></span>
                </div>
                <div class="stat">
                    <label>Market Events</label>
                    <span class="big-number"><?php echo $stats['market_events_occurred']; ?></span>
                </div>
                <div class="stat">
                    <label>Game Duration</label>
                    <span><?php echo gmdate("i:s", $stats['game_duration_seconds']); ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($event_history)): ?>
        <div class="card">
            <h2>Market Event History</h2>
            <div class="event-history">
                <?php foreach ($event_history as $event): ?>
                    <div class="event-item">
                        <strong><?php echo htmlspecialchars($event['name']); ?></strong>
                        <span><?php echo htmlspecialchars($event['message']); ?></span>
                        <small>Effect: <?php echo ($event['effect'] > 0 ? '+' : '') . ($event['effect'] * 100); ?>%</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($state['banker_offers'])): ?>
        <div class="card">
            <h2>Offer History</h2>
            <div class="offer-history">
                <?php foreach ($state['banker_offers'] as $index => $offer): ?>
                    <div class="offer-item <?php echo ($index === count($state['banker_offers'])-1 && $state['current_offer'] !== null) ? 'current' : ''; ?>">
                        <span class="offer-round">Round <?php echo $index + 1; ?></span>
                        <span class="offer-amount">$<?php echo number_format($offer); ?></span>
                        <?php if ($index < count($state['banker_offers'])-1 || $state['game_over']): ?>
                            <span class="offer-decision <?php echo ($state['deal_amount'] !== null && $index === count($state['banker_offers'])-1) ? 'accepted' : 'declined'; ?>">
                                <?php echo ($state['deal_amount'] !== null && $index === count($state['banker_offers'])-1) ? 'ACCEPTED' : 'DECLINED'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Strategy Analysis</h2>
            <div class="strategy-tips">
                <?php if ($stats['player_risk_tendency'] > 70): ?>
                    <p>🎯 <strong>High-Risk Player:</strong> You tend to decline offers. Consider that early high offers might be better than potential later outcomes.</p>
                <?php elseif ($stats['player_risk_tendency'] < 30): ?>
                    <p>🛡️ <strong>Low-Risk Player:</strong> You prefer safe deals. Remember that sometimes taking calculated risks can lead to higher rewards.</p>
                <?php else: ?>
                    <p>⚖️ <strong>Balanced Player:</strong> You have a good risk-reward balance. Continue evaluating each offer based on remaining values.</p>
                <?php endif; ?>

                <?php if ($stats['optimal_strategy_score'] >= 80): ?>
                    <p>🏆 <strong>Excellent Strategy:</strong> Your decision-making aligns well with optimal game theory principles.</p>
                <?php elseif ($stats['optimal_strategy_score'] >= 60): ?>
                    <p>👍 <strong>Good Strategy:</strong> You're making solid decisions. Focus on value assessment for improvement.</p>
                <?php else: ?>
                    <p>💡 <strong>Development Area:</strong> Consider the probability distribution of remaining values when evaluating offers.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>