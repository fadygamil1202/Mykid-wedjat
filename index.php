<?php
// Fallback entry point when the web server opens this folder directly.
// The real front controller is public/index.php (the root .htaccess routes there).
require __DIR__ . '/public/index.php';
