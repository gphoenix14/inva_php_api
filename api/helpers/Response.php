<?php
// helpers/Response.php
 
class Response {
 
    public static function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
 
    public static function success(mixed $data, string $message = 'OK', int $code = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
 
    public static function error(string $message, int $code = 400, array $errors = []): void {
        $body = ['success' => false, 'message' => $message];
        if (!empty($errors)) $body['errors'] = $errors;
        self::json($body, $code);
    }
 
    public static function paginate(array $data, int $total, int $page, int $perPage): void {
        self::json([
            'success'    => true,
            'data'       => $data,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
