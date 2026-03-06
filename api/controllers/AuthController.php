<?php
// controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';

class AuthController {
    public function handle(string $method, array $parts, array $body): void {
        $action = $parts[1] ?? '';

        match (true) {
            $method === 'POST' && $action === 'login'    => $this->login($body),
            $method === 'POST' && $action === 'register' => $this->register($body),
            $method === 'GET'  && $action === 'me'       => $this->me(),
            $method === 'POST' && $action === 'logout'   => $this->logout(),
            default => Response::error('Endpoint auth non trovato', 404),
        };
    }

    private function login(array $body): void {
        $email    = $body['email']    ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            Response::error('Email e password richiesti', 422);
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM utenti WHERE email = :email AND attivo = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            Response::error('Credenziali non valide', 401);
        }

        $token     = bin2hex(random_bytes(32));
        $hashedTok = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $stmt = $db->prepare(
            "INSERT INTO api_tokens (utente_id, token, scadenza) VALUES (:uid, :token, :exp)"
        );
        $stmt->execute([':uid' => $user['id'], ':token' => $hashedTok, ':exp' => $expiresAt]);

        Response::success([
            'token'      => $token,
            'expires_at' => $expiresAt,
            'user'       => [
                'id'    => $user['id'],
                'nome'  => $user['nome'],
                'email' => $user['email'],
                'ruolo' => $user['ruolo'],
            ],
        ], 'Login effettuato');
    }

    private function register(array $body): void {
        $userModel = new User();
        $errors = $userModel->validate($body, true);
        if (!empty($errors)) {
            Response::error('Dati non validi', 422, $errors);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM utenti WHERE email = :email");
        $stmt->execute([':email' => $body['email']]);
        if ($stmt->fetch()) {
            Response::error('Email già registrata', 409);
        }

        $id = $userModel->create($body);
        Response::success(['id' => $id], 'Registrazione completata', 201);
    }

    private function me(): void {
        $user = AuthMiddleware::requireAuth();
        Response::success($user);
    }

    private function logout(): void {
        $user = AuthMiddleware::requireAuth();

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/^Bearer\s+(.+)$/i', $header, $matches);
        $token = hash('sha256', $matches[1]);

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM api_tokens WHERE token = :token");
        $stmt->execute([':token' => $token]);

        Response::success(null, 'Logout effettuato');
    }
}
