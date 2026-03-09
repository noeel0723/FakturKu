<?php
/**
 * Simple Router
 */
class Router {
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'controller' => $controller, 'action' => $action];
    }

    public function post(string $path, string $controller, string $action): void {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'controller' => $controller, 'action' => $action];
    }

    public function dispatch(): void {
        $url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->buildPattern($route['path']);
            if (preg_match($pattern, $url, $matches)) {
                $controller = new $route['controller']();
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func_array([$controller, $route['action']], array_values($params));
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
    }

    private function buildPattern(string $path): string {
        $path = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }
}
