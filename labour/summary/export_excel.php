<?php
// Reuse the report's authentication, permission checks and exact calculations.
// A separate route avoids the UI-first loading response on the report page.
define('WINPOS_LABOUR_EXPORT', true);
require __DIR__ . '/index.php';
