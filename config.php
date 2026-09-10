<?php

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'v2404_registration');
define('DB_USER', 'v2404_reg');
define('DB_PASS', 'PaulHindemith');
define('DB_CHARSET', 'utf8mb4');

// Application
define('BASE_URL', 'http://localhost:8000/');
define('SECRET_KEY', 'w6V7xtl4AB/q0PuAJNBH727YQggMiy45lHQAWXMlhR8=');

// Email: set to true to write emails to logs/email.log instead of sending
define('TESTING', true);
define('EMAIL_FROM', 'koosmanguklubi@uuu.ee');
define('EMAIL_FROM_NAME', 'Koosmänguklubi');
define('EMAIL_REPLY_TO', 'koosmanguklubi@gmail.com');
define('ADMIN_EMAIL', 'koosmanguklubi@gmail.com');
define('LOG_FILE', __DIR__ . '/logs/email.log');

// Ensemble schedule config (ensemble_id => explicit list of session dates)
define('ENSEMBLE_SCHEDULES', serialize([
    1 => ['dates' => [
        // Classical — Sundays, from 2026-09-13, skipping 2026-11-15..2026-12-20
        '2026-09-13', '2026-09-20', '2026-09-27',
        '2026-10-04', '2026-10-11', '2026-10-18', '2026-10-25',
        '2026-11-01', '2026-11-08',
        '2026-12-27',
    ]],
    2 => ['dates' => [
        // Impro — Saturdays, from 2026-09-12, skipping 2026-10-10 and 2026-11-14..2026-12-19
        '2026-09-12', '2026-09-19', '2026-09-26',
        '2026-10-03', '2026-10-17', '2026-10-24', '2026-10-31',
        '2026-11-07',
        '2026-12-26',
    ]],
    3 => ['dates' => []],  // Beginners — no upcoming sessions scheduled
]));
