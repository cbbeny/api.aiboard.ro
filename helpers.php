<?php
require_once __DIR__.'/config.php';

function cors(): void {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if (in_array($origin, ALLOWED_ORIGINS, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
  }
  header('Access-Control-Allow-Methods: GET, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-Api-Key');
  header('Access-Control-Max-Age: 86400');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function json($data, int $code = 200): void {
  header('Content-Type: application/json; charset=utf-8');
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function requireApiKey(): void {
  if (!API_KEY) return;
  $k = $_SERVER['HTTP_X_API_KEY'] ?? '';
  if (!hash_equals(API_KEY, $k)) {
    json(['error' => 'Unauthorized'], 401);
  }
}

function rateLimit(string $bucket, int $max = RATE_LIMIT_MAX, int $window = RATE_LIMIT_WINDOW): void {
  // Simple file-based limiter (works on shared hosting). For production, move to Redis.
  $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $key = sys_get_temp_dir()."/apirtl_".md5($bucket.$ip);
  $now = time();
  $data = ['start' => $now, 'hits' => 0];

  if (is_file($key)) {
    $data = json_decode((string)@file_get_contents($key), true) ?: $data;
    if (($now - ($data['start'] ?? $now)) < $window) {
      $data['hits'] = ($data['hits'] ?? 0) + 1;
    } else {
      $data = ['start' => $now, 'hits' => 1];
    }
  } else {
    $data['hits'] = 1;
  }
  @file_put_contents($key, json_encode($data), LOCK_EX);

  if ($data['hits'] > $max) {
    json(['error' => 'Too Many Requests'], 429);
  }
}
