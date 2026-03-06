<?php
// models/User.php
 
class User {
    private PDO $db;
 
    public function __construct() {
        $this->db = Database::getInstance();
    }
 
    // ── Listing con paginazione e filtri ──────────────────────────────────────
    public function findAll(array $params = []): array {
        $page    = max(1, (int)($params['page']    ?? 1));
        $perPage = min(100, max(1, (int)($params['per_page'] ?? 10)));
        $offset  = ($page - 1) * $perPage;
        $search  = $params['cerca'] ?? '';
        $ruolo   = $params['ruolo'] ?? '';
        $orderBy = in_array($params['ordina'] ?? '', ['id','nome','email','creato_il'])
                    ? $params['ordina'] : 'id';
        $dir     = strtoupper($params['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
 
        $where = ['attivo = 1'];
        $bind  = [];
 
        if ($search !== '') {
            $where[] = '(nome LIKE :cerca OR email LIKE :cerca2)';
            $bind[':cerca']  = "%$search%";
            $bind[':cerca2'] = "%$search%";
        }
        if ($ruolo !== '') {
            $where[] = 'ruolo = :ruolo';
            $bind[':ruolo'] = $ruolo;
        }
 
        $whereSql = 'WHERE ' . implode(' AND ', $where);
 
        // Conta totale per paginazione
        $stmtCount = $this->db->prepare(
            "SELECT COUNT(*) FROM utenti $whereSql"
        );
        $stmtCount->execute($bind);
        $total = (int)$stmtCount->fetchColumn();
 
        // Query principale
        $stmt = $this->db->prepare(
            "SELECT id, nome, email, ruolo, attivo, creato_il
             FROM utenti $whereSql
             ORDER BY $orderBy $dir
             LIMIT :limit OFFSET :offset"
        );
        foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
 
        return ['data' => $stmt->fetchAll(), 'total' => $total,
                'page' => $page, 'per_page' => $perPage];
    }
 
    // ── Trova per ID ──────────────────────────────────────────────────────────
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, nome, email, ruolo, attivo, creato_il FROM utenti WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
 
    // ── Crea utente ───────────────────────────────────────────────────────────
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO utenti (nome, email, password, ruolo)
             VALUES (:nome, :email, :password, :ruolo)"
        );
        $stmt->execute([
            ':nome'     => $data['nome'],
            ':email'    => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            ':ruolo'    => $data['ruolo'] ?? 'utente',
        ]);
        return (int)$this->db->lastInsertId();
    }
 
    // ── Aggiorna utente (PUT - completo) ──────────────────────────────────────
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE utenti
             SET nome = :nome, email = :email, ruolo = :ruolo, attivo = :attivo
             WHERE id = :id"
        );
        return $stmt->execute([
            ':nome'   => $data['nome'],
            ':email'  => $data['email'],
            ':ruolo'  => $data['ruolo'],
            ':attivo' => $data['attivo'] ?? 1,
            ':id'     => $id,
        ]);
    }
 
    // ── Aggiornamento parziale (PATCH) ────────────────────────────────────────
    public function patch(int $id, array $data): bool {
        $allowed = ['nome', 'email', 'ruolo', 'attivo'];
        $fields  = [];
        $bind    = [':id' => $id];
 
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $bind[":$field"] = $data[$field];
            }
        }
 
        if (empty($fields)) return false;
 
        $stmt = $this->db->prepare(
            "UPDATE utenti SET " . implode(', ', $fields) . " WHERE id = :id"
        );
        return $stmt->execute($bind);
    }
 
    // ── Elimina utente (soft delete) ──────────────────────────────────────────
    public function delete(int $id): bool {
        $stmt = $this->db->prepare(
            "UPDATE utenti SET attivo = 0 WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }
 
    // ── Validazione ───────────────────────────────────────────────────────────
    public function validate(array $data, bool $isCreate = true): array {
        $errors = [];
        if ($isCreate || isset($data['nome'])) {
            if (empty($data['nome']) || strlen($data['nome']) < 2)
                $errors['nome'] = 'Il nome deve avere almeno 2 caratteri';
        }
        if ($isCreate || isset($data['email'])) {
            if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL))
                $errors['email'] = 'Email non valida';
        }
        if ($isCreate) {
            if (empty($data['password']) || strlen($data['password']) < 8)
                $errors['password'] = 'Password: minimo 8 caratteri';
        }
        return $errors;
    }
}
