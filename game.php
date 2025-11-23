<?php
// game.php
require_once 'config.php';
require_once 'functions.php';

$theme = dond_get_theme();
$state = dond_get_state();

// Validate and repair game state if needed
$errors = dond_validate_game_state($state);
if (!empty($errors)) {
    $state = dond_repair_game_state($state);
    dond_save_state($state);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$state['game_over']) {

    // Choose main case
    if (isset($_POST['action']) && $_POST['action'] === 'choose_case' && isset($_POST['case_id'])) {
        $case_id = (int) $_POST['case_id'];
        if ($state['chosen_case'] === null && array_key_exists($case_id, $state['cases'])) {
            $state['chosen_case'] = $case_id;
            dond_record_game_action($state, 'choose_case', $case_id);
        }
    }

    // Open a case
    if (isset($_POST['action']) && $_POST['action'] === 'open_case' && isset($_POST['case_id'])) {
        $case_id = (int) $_POST['case_id'];
        if ($state['chosen_case'] !== null &&
            !in_array($case_id, $state['opened'], true) &&
            $case_id !== $state['chosen_case'] &&
            $state['current_offer'] === null) {

            $state['opened'][] = $case_id;
            $state['total_opened']++;
            dond_record_game_action($state, 'open_case', $case_id);

            // After certain opens, make Banker offer
            if (dond_is_round_break($state)) {
                $offer = dond_compute_banker_offer($state);
                $state['current_offer'] = $offer;
                $state['banker_offers'][] = $offer;
                dond_record_game_action($state, 'banker_offer', $offer);
            }
        }
    }

    // Deal / No Deal decision
    if (isset($_POST['action']) && $_POST['action'] === 'deal' && $state['current_offer'] !== null) {
        dond_update_player_behavior($state, 'deal');
        $state['deal_amount'] = $state['current_offer'];
        $state['game_over'] = true;
        $state['final_amount'] = $state['deal_amount'];
        $state['current_offer'] = null;
        dond_record_game_action($state, 'accept_deal', $state['deal_amount']);
        setcookie('dond_can_resume', '', time() - 3600, '/');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'no_deal' && $state['current_offer'] !== null) {
        dond_update_player_behavior($state, 'no_deal');
        $state['round']++;
        $state['current_offer'] = null;
        $last_offer = $state['banker_offers'][count($state['banker_offers'])-1];
        dond_record_game_action($state, 'reject_deal', $last_offer);
    }

    // Final reveal when only 1 unopened case + chosen remain
    $remaining_ids = dond_remaining_case_ids($state);
    if (!$state['game_over'] && $state['chosen_case'] !== null && count($remaining_ids) === 1) {
        // Only one unopened besides chosen; reveal automatically
        $state['game_over'] = true;
        $chosen_value = $state['cases'][$state['chosen_case']];
        $state['final_amount'] = $chosen_value;
        $state['deal_amount'] = null;
        dond_record_game_action($state, 'game_end_natural', $chosen_value);
        setcookie('dond_can_resume', '', time() - 3600, '/');
    }

    dond_save_state($state);
    header('Location: game.php');
    exit;
}

$cases = $state['cases'];
$chosen_case = $state['chosen_case'];
$opened = $state['opened'];
$current_offer = $state['current_offer'];
$round = $state['round'];
$remaining_ids = dond_remaining_case_ids($state);

// Build remaining value list for side panel
$remaining_values = [];
foreach ($cases as $id => $val) {
    if (!in_array($id, $opened, true)) {
        $remaining_values[] = $val;
    }
}
sort($remaining_values);

$game_stats = dond_get_game_statistics($state);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game - Deal or No Deal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="theme-<?php echo htmlspecialchars($theme); ?>">
<div class="container">
    <header class="game-header">
        <div>
            <h1>Deal or No Deal</h1>
            <p class="subtitle">High-Stakes PHP Vault</p>
        </div>
        <div class="meta">
            <span>Round: <?php echo (int)$round; ?></span>
            <span>Opened: <?php echo (int)$state['total_opened']; ?></span>
            <span>Risk: <?php echo $game_stats['player_risk_tendency']; ?>%</span>
            <a href="analytics.php" class="btn tiny">Analytics</a>
        </div>
    </header>

    <main class="layout">
        <section class="cases-panel card">
            <h2>Briefcases</h2>
            <p class="hint">
                <?php if ($chosen_case === null): ?>
                    Choose your main case.
                <?php elseif ($state['game_over']): ?>
                    Game over. See your result below.
                <?php elseif ($current_offer !== null): ?>
                    Banker has made an offer. Deal or No Deal?
                <?php else: ?>
                    Open a case to eliminate its value.
                <?php endif; ?>
            </p>

            <div class="cases-grid">
                <?php foreach ($cases as $id => $val): ?>
                    <?php
                    $is_chosen = ($id === $chosen_case);
                    $is_opened = in_array($id, $opened, true);
                    $label = $is_opened ? '$' . number_format($val) : $id;
                    $class = 'case';
                    if ($is_chosen) $class .= ' chosen';
                    if ($is_opened) $class .= ' opened';
                    ?>
                    <div class="<?php echo $class; ?>">
                        <span><?php echo htmlspecialchars((string)$label); ?></span>
                        <?php if (!$state['game_over'] && !$is_opened): ?>
                            <?php if ($chosen_case === null): ?>
                                <form method="post">
                                    <input type="hidden" name="case_id" value="<?php echo $id; ?>">
                                    <button type="submit" name="action" value="choose_case" class="btn tiny">
                                        Keep
                                    </button>
                                </form>
                            <?php elseif ($chosen_case !== null && $id !== $chosen_case && $current_offer === null): ?>
                                <form method="post">
                                    <input type="hidden" name="case_id" value="<?php echo $id; ?>">
                                    <button type="submit" name="action" value="open_case" class="btn tiny">
                                        Open
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="side-panel">
            <div class="card">
                <h2>Remaining Values</h2>
                <ul class="values-list">
                    <?php foreach ($remaining_values as $val): ?>
                        <li>$<?php echo number_format($val); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card banker-card <?php echo $current_offer !== null ? 'has-offer' : ''; ?>">
                <h2>Banker</h2>
                
                <!-- Market Event Display -->
                <?php if (isset($state['event_message']) && $state['event_message']): ?>
                    <div class="event-notice">
                        <small>📢 <?php echo htmlspecialchars($state['event_message']); ?></small>
                    </div>
                    <?php unset($_SESSION['dond']['event_message']); ?>
                <?php endif; ?>
                
                <?php if ($state['game_over']): ?>
                    <div class="game-result">
                        <?php if ($state['deal_amount'] !== null): ?>
                            <p>You took the deal: <strong>$<?php echo number_format($state['deal_amount']); ?></strong></p>
                            <p>Your original case contained: <strong>$<?php echo number_format($cases[$chosen_case]); ?></strong></p>
                            <p class="strategy-score">Strategy Score: <?php echo $game_stats['optimal_strategy_score']; ?>%</p>
                        <?php else: ?>
                            <p>You went all the way! Your case contained:</p>
                            <p class="big-amount">$<?php echo number_format($state['final_amount']); ?></p>
                            <p class="strategy-score">Strategy Score: <?php echo $game_stats['optimal_strategy_score']; ?>%</p>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <a href="analytics.php" class="btn secondary">View Analytics</a>
                        <a href="index.php" class="btn primary">Back to Home</a>
                    </div>
                <?php elseif ($current_offer !== null): ?>
                    <p>Banker offers:</p>
                    <p class="big-amount">$<?php echo number_format($current_offer); ?></p>
                    <p class="offer-context">
                        Offer #<?php echo count($state['banker_offers']); ?> 
                        (Round <?php echo $round; ?>)
                    </p>
                    <form method="post" class="deal-form">
                        <button type="submit" name="action" value="deal" class="btn primary">Deal</button>
                        <button type="submit" name="action" value="no_deal" class="btn danger">No Deal</button>
                    </form>
                <?php else: ?>
                    <?php if ($chosen_case === null): ?>
                        <p>Select your main case to begin.</p>
                    <?php else: ?>
                        <p>Open more cases to trigger the Banker's next offer.</p>
                        <p class="game-progress">
                            <?php
                            $next_break = null;
                            $breaks = dond_round_breaks();
                            foreach ($breaks as $break) {
                                if ($break > $state['total_opened']) {
                                    $next_break = $break;
                                    break;
                                }
                            }
                            if ($next_break !== null): ?>
                                Next offer after <?php echo ($next_break - $state['total_opened']); ?> more cases.
                            <?php else: ?>
                                Final round - keep opening cases!
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!$state['game_over'] && $chosen_case !== null): ?>
                <div class="card stats-card">
                    <h2>Game Stats</h2>
                    <div class="stats-grid">
                        <div class="stat">
                            <label>Risk Level</label>
                            <span><?php echo $game_stats['player_risk_tendency']; ?>%</span>
                        </div>
                        <div class="stat">
                            <label>Offers Received</label>
                            <span><?php echo $game_stats['total_offers_received']; ?></span>
                        </div>
                        <div class="stat">
                            <label>Market Events</label>
                            <span><?php echo $game_stats['market_events_occurred']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>