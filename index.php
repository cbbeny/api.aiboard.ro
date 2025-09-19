<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/CompanyRepository.php';

cors();
requireApiKey();

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Very small router for:
// GET /api/companies/search?q=...&limit=10
// GET /api/companies/{id}
// GET /api/companies/by-cui/{cui}
try {
  $repo = new CompanyRepository(pdo());

  if ($method === 'GET' && preg_match('#^/api/companies/search$#', $uri)) {
    rateLimit('companies_search'); // basic rate limit
    $q = trim($_GET['q'] ?? '');
    if ($q === '') json(['error' => 'Missing q'], 422);
    $limit = (int)($_GET['limit'] ?? 10);
    $rows = $repo->search($q, $limit);
    json(['data' => $rows]);
  }

  if ($method === 'GET' && preg_match('#^/api/companies/by-cui/(\d+)$#', $uri, $m)) {
    $row = $repo->getByCui($m[1]);
    if (!$row) json(['data' => null], 404);
    json(['data' => $row]);
  }

  if ($method === 'GET' && preg_match('#^/api/companies/(\d+)$#', $uri, $m)) {
    $row = $repo->getById((int)$m[1]);
    if (!$row) json(['data' => null], 404);
    json(['data' => $row]);
  }

  json(['error' => 'Not Found'], 404);
} catch (Throwable $e) {
  json(['error' => 'Server Error', 'details' => $e->getMessage()], 500);
}
