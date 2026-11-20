<?php
// Copy to config.php OUTSIDE your public/ document root; never commit actual credentials.
return [
  'DB_DSN' => 'mysql:host=127.0.0.1;port=3306;dbname=sck;charset=utf8mb4',
  'DB_USER' => 'change_me',
  'DB_PASS' => 'change_me',
  'APP_ORIGIN' => 'https://culture.example.id',
  'FORCE_HTTPS' => 'true',
  'HERITAGE_MEDIA_DIR' => '/var/lib/budayago/private-media',
  'HERITAGE_PUBLICATION_ENABLED' => 'false',
  'LOGIN_RATE_LIMIT_DIR' => '/var/lib/budayago/login-throttle',
];
