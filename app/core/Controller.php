<?php
/**
 * Base Controller
 */
class Controller {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
        if (empty($_SESSION['user'])) {
            $_SESSION['user'] = [
                'name' => env('DEFAULT_USER_NAME', 'System User'),
                'role' => env('DEFAULT_USER_ROLE', 'owner'),
            ];
        }
    }

    protected function view(string $viewPath, array $data = []): void {
        extract($data);
        $viewFile = BASE_PATH . '/app/views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View not found: $viewPath";
        }
    }

    protected function redirect(string $url): void {
        header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
        exit;
    }

    protected function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function currentUserRole(): string {
        return $_SESSION['user']['role'] ?? 'owner';
    }

    protected function requireRoles(array $roles): void {
        $role = $this->currentUserRole();
        if (!in_array($role, $roles, true)) {
            http_response_code(403);
            echo '<h2>403 Forbidden</h2><p>You do not have permission to access this page.</p>';
            exit;
        }
    }
}
