<?php
// functions.php
require_once 'config.php';
require_once 'events.php';

function dond_init_game(): void {
    $values = dond_get_case_values();
    shuffle($values);

    // Map case id => value (case ids 1..N)
    $cases = [];
    foreach ($values as $i => $val) {
        $cases[$i + 1] = $val;
    }

    $_SESSION['dond'] = [
        'cases' => $cases,
        'chosen_case' => null,
        'opened' => [],
        'round' => 1,
        'total_opened' => 0,
        'current_offer' => null,
        'banker_offers' => [],
        'game_over' => false,
        'final_amount' => null,
        'deal_amount' => null,
        'risk_profile' => 0.5,
        'event_message' => null,
        'last_event' => null,
        'game_history' => [],
        'event_history' => [],
        'player_aggression' => 0.5,
        'start_time' => time()
    ];

    // mark that we have a resume-able game
    setcookie('dond_can_resume', '1', time() + 7 * 24 * 3600, '/');
}

function dond_has_active_game(): bool {
    return isset($_SESSION['dond']) && !$_SESSION['dond']['game_over'];
}

function dond_reset_game(): void {
    unset($_SESSION['dond']);
    setcookie('dond_can_resume', '', time() - 3600, '/');
}

function dond_get_state(): array {
    if (!isset($_SESSION['dond'])) {
        dond_init_game();
    }
    return $_SESSION['dond'];
}

function dond_save_state(array $state): void {
    $_SESSION['dond'] = $state;
}

// Enhanced banker offer with advanced strategy and events
function dond_compute_banker_offer(array $state): int {
    $remaining_values = dond_get_remaining_values_cached($state);

    if (empty($remaining_values)) {
        return 0;
    }

    $avg = array_sum($remaining_values) / count($remaining_values);
    $max_remaining = max($remaining_values);
    $min_remaining = min($remaining_values);
    
    // Advanced strategic factors
    $round = $state['round'];
    $game_phase = count($state['opened']) / count($state['cases']);
    $high_value_ratio = count(array_filter($remaining_values, fn($v) => $v > 500)) / count($remaining_values);
    $risk_profile = $state['risk_profile'] ?? 0.5;
    $aggression_score = $state['player_aggression'] ?? 0.5;
    
    // Base strategy with phase awareness
    $base_factor = 0.5 + ($round * 0.03);
    
    // High value adjustment - banker gets more cautious with high values remaining
    if ($high_value_ratio > 0.3) {
        $base_factor -= 0.1;
    }
    
    // Risk profile adjustment - if player takes risks, banker offers less
    $base_factor -= ($risk_profile * 0.1);
    
    // Aggression penalty - aggressive players get worse offers
    $base_factor -= ($aggression_score * 0.08);
    
    // Game phase adjustment - late game offers are more conservative
    if ($game_phase > 0.7) {
        $base_factor -= 0.05;
    }
    
    // Volatility from market events
    $event = dond_get_random_event();
    $event_message = '';
    if ($event) {
        $avg = dond_apply_event_effect($avg, $event);
        $event_message = $event['message'];
        $_SESSION['dond']['last_event'] = $event;
        $_SESSION['dond']['event_history'][] = $event;
    }
    
    // Dynamic difficulty scaling
    $difficulty_modifier = dond_calculate_difficulty_modifier($state);
    
    $volatility = (rand(85, 115) / 100.0);
    $offer = $avg * $base_factor * $volatility * $difficulty_modifier;

    // Store event message for display
    if ($event_message) {
        $_SESSION['dond']['event_message'] = $event_message;
    }

    return (int) round(max($offer, $min_remaining * 0.5));
}

function dond_is_round_break(array $state): bool {
    $breaks = dond_round_breaks();
    return in_array($state['total_opened'], $breaks, true);
}

function dond_remaining_case_ids(array $state): array {
    $all_ids = array_keys($state['cases']);
    return array_values(array_diff($all_ids, $state['opened']));
}

// Track player risk profile and aggression
function dond_update_player_behavior(array $state, string $decision): void {
    $risk_change = ($decision === 'no_deal') ? 0.1 : -0.1;
    $aggression_change = ($decision === 'no_deal') ? 0.15 : -0.15;
    
    $current_risk = $state['risk_profile'] ?? 0.5;
    $current_aggression = $state['player_aggression'] ?? 0.5;
    
    $new_risk = max(0.1, min(0.9, $current_risk + $risk_change));
    $new_aggression = max(0.1, min(0.9, $current_aggression + $aggression_change));
    
    $_SESSION['dond']['risk_profile'] = $new_risk;
    $_SESSION['dond']['player_aggression'] = $new_aggression;
}

// Calculate player aggression based on decisions
function dond_calculate_player_aggression(array $state): float {
    if (empty($state['banker_offers'])) {
        return 0.5;
    }
    
    $offers_declined = count($state['banker_offers']) - ($state['deal_amount'] !== null ? 1 : 0);
    $total_offers = count($state['banker_offers']);
    
    return $offers_declined / max(1, $total_offers);
}

// Optimized value calculations with caching
function dond_get_remaining_values_cached(array $state): array {
    static $cache = [];
    $cache_key = md5(serialize($state['opened']));
    
    if (!isset($cache[$cache_key])) {
        $remaining = [];
        foreach ($state['cases'] as $id => $val) {
            if (!in_array($id, $state['opened'], true)) {
                $remaining[] = $val;
            }
        }
        $cache[$cache_key] = $remaining;
        
        // Limit cache size for memory management
        if (count($cache) > 10) {
            array_shift($cache);
        }
    }
    
    return $cache[$cache_key];
}

// Dynamic difficulty based on player performance
function dond_calculate_difficulty_modifier(array $state): float {
    $base_difficulty = 1.0;
    
    // Player is doing well - increase difficulty
    if ($state['risk_profile'] > 0.7) {
        $base_difficulty *= 0.9; // Banker offers less
    }
    
    // Player is struggling - ease up
    if (count($state['banker_offers']) > 2 && 
        max($state['banker_offers']) < (max($state['cases']) * 0.3)) {
        $base_difficulty *= 1.1; // Banker offers more
    }
    
    return $base_difficulty;
}

// Game statistics and analytics
function dond_get_game_statistics(array $state): array {
    $game_duration = time() - ($state['start_time'] ?? time());
    
    return [
        'player_risk_tendency' => round(($state['risk_profile'] ?? 0.5) * 100),
        'player_aggression_score' => round(($state['player_aggression'] ?? 0.5) * 100),
        'total_offers_received' => count($state['banker_offers']),
        'offers_declined' => count(array_filter($state['banker_offers'], 
            function($offer, $index) use ($state) {
                return $index < count($state['banker_offers']) - 1 || $state['deal_amount'] === null;
            }, ARRAY_FILTER_USE_BOTH
        )),
        'highest_offer_missed' => $state['deal_amount'] === null ? 
            (count($state['banker_offers']) > 0 ? max($state['banker_offers']) : 0) : null,
        'market_events_occurred' => count($state['event_history'] ?? []),
        'game_duration_seconds' => $game_duration,
        'optimal_strategy_score' => dond_calculate_optimal_play_score($state)
    ];
}

// Calculate optimal play score
function dond_calculate_optimal_play_score(array $state): int {
    if ($state['game_over'] && $state['deal_amount'] !== null) {
        $chosen_value = $state['cases'][$state['chosen_case']];
        return $state['deal_amount'] >= $chosen_value ? 100 : 50;
    }
    return 75; // Default score for ongoing games
}

// Record game actions for history/replay
function dond_record_game_action(array $state, string $action, $data = null): void {
    if (!isset($state['game_history'])) {
        $state['game_history'] = [];
    }
    
    $state['game_history'][] = [
        'round' => $state['round'],
        'action' => $action,
        'data' => $data,
        'remaining_cases' => dond_remaining_case_ids($state),
        'remaining_values' => array_values(array_diff_key(
            $state['cases'], 
            array_flip($state['opened'])
        )),
        'timestamp' => time()
    ];
    
    dond_save_state($state);
}

// Enhanced error handling and state validation
function dond_validate_game_state(array $state): array {
    $errors = [];
    
    // Validate case consistency
    if (count($state['cases']) !== count(dond_get_case_values())) {
        $errors[] = "Case count mismatch";
    }
    
    // Validate opened cases are valid
    foreach ($state['opened'] as $caseId) {
        if (!array_key_exists($caseId, $state['cases'])) {
            $errors[] = "Invalid case ID in opened list: $caseId";
        }
    }
    
    // Validate game progression makes sense
    if ($state['total_opened'] !== count($state['opened'])) {
        $errors[] = "Opened case count mismatch";
    }
    
    return $errors;
}

// Auto-recovery mechanism
function dond_repair_game_state(array $state): array {
    // Remove invalid opened cases
    $state['opened'] = array_filter($state['opened'], 
        function($id) use ($state) {
            return array_key_exists($id, $state['cases']);
        }
    );
    
    // Recalculate total opened
    $state['total_opened'] = count($state['opened']);
    
    // Reset invalid offers
    if ($state['current_offer'] !== null && !dond_is_round_break($state)) {
        $state['current_offer'] = null;
    }
    
    return $state;
}
?>