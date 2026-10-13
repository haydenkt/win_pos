<?php

if (!isset($conn)) {
    include __DIR__ . "/../config/database.php";
}

function getSetting($conn, $group, $key)
{
    // Request-local only: one round trip for header/sidebar settings, with
    // fresh values on the next page load (no stale session or disk cache).
    static $loadedConnection = null;
    static $settings = [];
    if ($loadedConnection !== $conn) {
        $settings = [];
        $query = $conn->query('SELECT setting_group, setting_key, setting_value FROM settings');
        if (!$query) return '';
        while ($row = $query->fetch_assoc()) {
            $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
        }
        $query->free();
        $loadedConnection = $conn;
    }
    return $settings[$group][$key] ?? '';
}
