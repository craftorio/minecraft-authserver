<?php

declare(strict_types=1);

namespace Craftorio\Authserver;

/**
 * Debug log for profile API (tail authserver/var/profile-api.log).
 */
class ProfileApiLog
{
    public static function write(string $endpoint, array $context = []): void
    {
        $entry = array_merge([
            'time' => date('c'),
            'endpoint' => $endpoint,
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query' => $_SERVER['QUERY_STRING'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '',
        ], $context);

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        $paths = [
            dirname(__DIR__) . '/var/profile-api.log',
            '/tmp/craftorio-auth-profile-api.log',
        ];

        foreach ($paths as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        }
    }

    public static function rawBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }
}
