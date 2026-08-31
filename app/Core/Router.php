<?php

declare(strict_types=1);

namespace Adl\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [$method, $this->compile($path), $handler, $path];
    }

    private function compile(string $path): string
    {
        $parts = preg_split('#(\{[a-zA-Z_][a-zA-Z0-9_-]*\})#', $path, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$path];
        $regex = '';
        foreach ($parts as $part) {
            if (preg_match('#^\{([a-zA-Z_][a-zA-Z0-9_-]*)\}$#', $part, $m)) {
                $regex .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        foreach ($this->routes as [$verb, $regex, $handler]) {
            if ($verb !== $method) {
                continue;
            }
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->{$action}($request, ...array_values($params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', [
            'title' => 'Page introuvable',
            'meta' => [
                'title' => 'Page introuvable — acteursdulivre.fr',
                'description' => 'Cette page n\'existe pas ou a été retirée.',
                'robots' => \Adl\Data\Seo::ROBOTS_NONE,
            ],
        ]);
    }
}
