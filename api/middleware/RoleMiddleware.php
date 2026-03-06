<?php
// middleware/RoleMiddleware.php

class RoleMiddleware {
    public static function requireRole(array $user, string ...$roles): void {
        if (!in_array($user['ruolo'], $roles, true)) {
            Response::error('Accesso negato: ruolo non autorizzato', 403);
        }
    }
}
