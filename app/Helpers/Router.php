<?php
/**
 * Lightweight front-controller router.
 * Supports named URL parameters ({param}) and GET/POST method matching.
 */

declare(strict_types=1);

namespace App\Helpers;

class Router
{
    private static array $routes = [];

    public static function get(string $pattern, array|callable $handler): void
    {
        self::add('GET', $pattern, $handler);
    }

    public static function post(string $pattern, array|callable $handler): void
    {
        self::add('POST', $pattern, $handler);
    }

    private static function add(string $method, string $pattern, array|callable $handler): void
    {
        self::$routes[] = compact('method', 'pattern', 'handler');
    }

    /**
     * Dispatch the current HTTP request to the matching handler.
     * Named parameters like {id} are extracted and passed as arguments.
     */
    public static function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Strip the sub-directory prefix so that routes are relative.
        // Supports running under /Trattoria-Gusto-menu/public/, /Trattoria-Gusto-menu/, or root VirtualHost.
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
        $path = $rawUri;

        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        } else {
            $parentDir = rtrim(dirname($scriptDir), '/\\');
            if ($parentDir !== '' && $parentDir !== '/' && str_starts_with($path, $parentDir)) {
                $path = substr($path, strlen($parentDir));
            }
        }
        $path = '/' . ltrim($path, '/');

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = self::matchPattern($route['pattern'], $path);
            if ($params === false) {
                continue;
            }
            // Found — invoke handler
            $handler = $route['handler'];
            if (is_array($handler) && count($handler) === 2) {
                [$class, $methodName] = $handler;
                $controller = new $class();
                $controller->$methodName(...array_values($params));
            } elseif (is_callable($handler)) {
                $handler(...array_values($params));
            }
            return;
        }

        // No route matched
        http_response_code(404);
        if (file_exists(BASE_PATH . '/resources/views/errors/404.php')) {
            include BASE_PATH . '/resources/views/errors/404.php';
        } else {
            echo '<h1>404 — Page Not Found</h1>';
        }
    }

    /**
     * Match a route pattern against a URL path.
     * Returns an associative array of named parameters, or false on no match.
     *
     * @return array<string,string>|false
     */
    private static function matchPattern(string $pattern, string $path): array|false
    {
        // Convert {param} placeholders to named regex groups
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#u';

        if (!preg_match($regex, $path, $matches)) {
            return false;
        }

        // Return only named captures
        $params = [];
        foreach ($matches as $key => $val) {
            if (is_string($key)) {
                $params[$key] = $val;
            }
        }
        return $params;
    }
}

