<?php

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'v2404_registration');
define('DB_USER', 'v2404_reg');
define('DB_PASS', 'PaulHindemith');
define('DB_CHARSET', 'utf8mb4');

// Application
define('BASE_URL', 'http://localhost/registration-system');
define('SECRET_KEY', 'change-this-to-a-long-random-string-in-production');

// Email: set to true to write emails to logs/email.log instead of sending
define('TESTING', true);
define('EMAIL_FROM', 'koosmanguklubi@gmail.com');
define('EMAIL_FROM_NAME', 'Koosmänguklubi');
define('LOG_FILE', __DIR__ . '/logs/email.log');

// Ensemble schedule config (ensemble_id => day-of-week, 0=Sun 6=Sat)
define('ENSEMBLE_SCHEDULES', serialize([
    1 => ['dow' => 0, 'cutoff' => '2026-06-14'],  // Classical — Sundays
    2 => ['dow' => 6, 'cutoff' => '2026-06-13'],  // Impro — Saturdays
    3 => ['dow' => 0, 'cutoff' => '2026-06-14'],  // Beginners — Sundays
]));
