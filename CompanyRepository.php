<?php
// All queries use prepared statements and safe bindings.
class CompanyRepository
{
  private PDO $db;

  public function __construct(PDO $db) { $this->db = $db; }

  public function search(string $q, int $limit = 10): array {
    $limit = max(1, min($limit, 25));

    // If numeric -> search CUI first
    if (preg_match('/^\d+$/', $q)) {
      $sql = "SELECT id, firma, cui, judet, localitate, adresa
                FROM companii_agricultura
               WHERE cui = :qExact OR cui LIKE :qPrefix
               LIMIT {$limit}";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([
        ':qExact'  => $q,
        ':qPrefix' => $q.'%',
      ]);
      $rows = $stmt->fetchAll();
      if ($rows) return $rows; // short-circuit if we already matched by CUI
    }

    // Tokenize by space and build LIKE conditions for firma + (optional) localitate/judet
    $tokens = preg_split('/\s+/', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $where = [];
    $binds = [];

    // Basic relevance: prefix or word-boundary matches in `firma`
    $where[] = "firma LIKE :firmaprefix";
    $binds[':firmaprefix'] = $q.'%';
    $where[] = "firma LIKE :firmanoword";
    $binds[':firmanoword'] = '% '.$q.'%';

    // Token hits in localitate/judet help rank results
    foreach ($tokens as $i => $t) {
      $p = ":t$i";
      $where[] = "localitate LIKE $p OR judet LIKE $p OR firma LIKE $p";
      $binds[$p] = "%$t%";
    }

    $sql = "SELECT id, firma, cui, judet, localitate, adresa
              FROM companii_agricultura
             WHERE (".implode(' OR ', $where).")
             LIMIT {$limit}";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($binds);
    return $stmt->fetchAll();
  }

  public function getById(int $id): ?array {
    $stmt = $this->db->prepare(
      "SELECT id, firma, cui, judet, localitate, adresa, telefon, website
         FROM companii_agricultura
        WHERE id = :id"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public function getByCui(string $cui): ?array {
    $stmt = $this->db->prepare(
      "SELECT id, firma, cui, judet, localitate, adresa, telefon, website
         FROM companii_agricultura
        WHERE cui = :cui
        LIMIT 1"
    );
    $stmt->execute([':cui' => $cui]);
    $row = $stmt->fetch();
    return $row ?: null;
  }
}
