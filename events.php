<?php
// events.php - Volatile market events system

function dond_get_random_event(): ?array {
    $events = [
        [
            'name' => 'Market Crash',
            'message' => 'The market crashed! Banker is more cautious.',
            'effect' => -0.15
        ],
        [
            'name' => 'Economic Boom', 
            'message' => 'Economic boom! Banker is feeling generous.',
            'effect' => +0.10
        ],
        [
            'name' => 'High Volatility',
            'message' => 'High market volatility detected.',
            'effect' => +0.25 // More volatility in offers
        ],
        [
            'name' => 'Banker Bluff',
            'message' => 'The Banker is bluffing about your chances...',
            'effect' => -0.08
        ],
        [
            'name' => 'Interest Rate Hike',
            'message' => 'Interest rates rose! Banker offers less.',
            'effect' => -0.12
        ],
        [
            'name' => 'Market Rally',
            'message' => 'Market rally! Better offers available.',
            'effect' => +0.15
        ]
    ];
    
    // 30% chance of an event occurring
    if (rand(1, 100) <= 30) {
        return $events[array_rand($events)];
    }
    
    return null;
}

function dond_apply_event_effect(float $base_offer, array $event): float {
    return $base_offer * (1 + $event['effect']);
}

// Get event history for analytics
function dond_get_event_history(array $state): array {
    return $state['event_history'] ?? [];
}
?>