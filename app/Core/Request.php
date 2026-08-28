<?php

declare(strict_types=1);

namespace Adl\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        return $uri === '' ? '/' : $uri;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function string(string $key, string $default = ''): string
    {
        return trim((string) $this->input($key, $default));
    }

    public function bool(string $key): bool
    {
        $value = $this->input($key);
        return $value === true || $value === 1 || $value === '1' || $value === 'on';
    }

    public function int(string $key, ?int $default = null): ?int
    {
        $value = $this->input($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return (int) $value;
    }

    /** @return array<int|string, mixed> */
    public function list(string $key): array
    {
        $value = $this->input($key, []);
        return is_array($value) ? $value : [];
    }

    /** @return list<string> */
    public function strings(string $key): array
    {
        $raw = $this->input($key);
        if (!is_array($raw)) {
            $raw = $raw === null || $raw === '' ? [] : [$raw];
        }
        $out = [];
        foreach ($raw as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $out[] = $value;
            }
        }
        return array_values(array_unique($out));
    }

    /** @return array<string, mixed> */
    public function file(string $key): array
    {
        $file = $_FILES[$key] ?? [];
        return is_array($file) ? $file : [];
    }
}
