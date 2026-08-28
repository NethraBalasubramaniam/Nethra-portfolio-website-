<?php
// Admin login credentials. The password is stored as a salted bcrypt hash —
// never in plaintext — and checked with password_verify() at login.
//
// To change the password later, run this from a terminal and paste the
// output below:
//   php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT), PHP_EOL;"
return [
    'username' => 'admin',
    'password_hash' => '$2y$10$Knj0iNnw7m4JkEWmXGQYVejYymXn7zOBk9hGdM/jdntzKVq9sj6bC',
];
