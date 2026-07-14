<?php
/*
 * ============================================================
 *  Made by Bapan | Date: 5/4/2026
 *  All credits belongs to Bapan
 *  For any kind of software development job, cheat, website
 *  or panel development — contact Bapan:
 *  Telegram: https://t.me/bapanff
 *  Official Channel: https://t.me/mocosn
 * ============================================================
 */
class Router {
    private $routes = [];

    public function get($path, $handler) {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public function post($path, $handler) {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    public function resolve($method, $uri) {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['handler' => $route['handler'], 'params' => $params];
            }
        }

        return null;
    }

    public function dispatch($method, $uri) {
        $result = $this->resolve($method, $uri);
        if (!$result) {
            Response::error('not_found', 404);
            return;
        }

        [$controller, $action] = $result['handler'];
        $instance = new $controller();
        $instance->$action($result['params']);
    }

    public function serveFile($path) {
        if (file_exists($path)) {
            $html = file_get_contents($path);
            $html = str_replace('{{APP_NAME}}', APP_NAME, $html);
            echo $html;
            exit;
        }
        http_response_code(404);
        echo '404 Not Found';
        exit;
    }
}
