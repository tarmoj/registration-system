<?php

require_once __DIR__ . '/../config.php';

/**
 * Send an email, or in TESTING mode append it to the log file.
 */
function sendEmail(string $to, string $subject, string $body, bool $isHtml = false): void {
    if (TESTING) {
        $entry = sprintf(
            "[%s]\nTO: %s\nSUBJECT: %s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_repeat('-', 60),
            $body
        );
        $logDir = dirname(LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }
        file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
    } else {
        $contentType = $isHtml ? 'text/html' : 'text/plain';
        $headers = implode("\r\n", [
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
            'Content-Type: ' . $contentType . '; charset=UTF-8',
            'MIME-Version: 1.0',
        ]);
        mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }
}
