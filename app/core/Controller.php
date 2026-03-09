<?php
/**
 * Base Controller
 */
class Controller {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
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
}
