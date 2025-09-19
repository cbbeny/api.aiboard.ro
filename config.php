<?php
// All in English (as requested)

// DB connection
const DB_HOST = 'localhost';
const DB_NAME = 'aiboard_companies';
const DB_USER = 'aiboard_companies';
const DB_PASS = 'uhwLpGb]53s?';
const DB_CHARSET = 'utf8mb4';

// CORS allowed origins (adjust)
const ALLOWED_ORIGINS = [
  'https://app.aiboard.ro',
];

// Optional: simple API key (leave empty to disable)
const API_KEY = '9f7a1c5f2b3440a29b4d5e13d0f54c78a4d5d8e261fbb6d37f47e78c0a76d812'; // e.g. 'my-secret-key';

// Rate limit (per IP) for search endpoint
const RATE_LIMIT_MAX = 120;   // requests
const RATE_LIMIT_WINDOW = 60; // seconds

function pdo(): PDO {
  static $pdo;
  if ($pdo) return $pdo;
  $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
