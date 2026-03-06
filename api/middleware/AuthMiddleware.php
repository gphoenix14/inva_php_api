<?php
// middleware/AuthMiddleware.php

class AuthMiddleware {
    public static function check(): ?array {
        $header = $_SERVER['HTTP_AUTHORIZATION']
                  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                  ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }
        $token = $matches[1];

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.id, u.nome, u.email, u.ruolo
             FROM api_tokens t
             JOIN utenti u ON u.id = t.utente_id
             WHERE t.token = :token AND t.scadenza > NOW()"
        );
        $stmt->execute([':token' => hash('sha256', $token)]);
        return $stmt->fetch() ?: null;
    }

    public static function requireAuth(): array {
        $user = self::check();
        if (!$user) {
            Response::error('Non autenticato', 401);
        }
        return $user;
    }
}
