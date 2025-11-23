<?php
// config.php
session_start();

// Original Deal or No Deal style values, trimmed to 12 for a smaller game
function dond_get_case_values(): array {
    return [
        1, 5, 10, 25, 50, 75,
        100, 200, 500, 1000, 5000, 10000
    ];
}

// Simple round structure: total opened so far where Banker makes offers
function dond_round_breaks(): array {
    return [3, 7, 9, 11]; // after 3, 7, 9, 11 opened cases
}

// Theme from cookie (dark casino default)
function dond_get_theme(): string {
    if (!empty($_COOKIE['dond_theme'])) {
        return $_COOKIE['dond_theme'];
    }
    return 'dark';
}
