<?php

namespace App\Support;

class TelegramUrl
{
    /** Public HTTPS base URL used for webhook registration. */
    public static function webhookBaseUrl(): ?string
    {
        $configured = config('services.telegram.webhook_url') ?: config('app.url');

        if (! is_string($configured) || $configured === '') {
            return null;
        }

        $url = rtrim($configured, '/');

        if (! str_starts_with($url, 'https://')) {
            return null;
        }

        if (self::isLocalHost($url)) {
            return null;
        }

        return $url;
    }

    public static function webhookEndpoint(): ?string
    {
        $base = self::webhookBaseUrl();

        return $base ? "{$base}/api/v1/telegram/webhook" : null;
    }

    private static function isLocalHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return true;
        }

        $localHosts = ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'];

        return in_array(strtolower($host), $localHosts, true)
            || str_ends_with(strtolower($host), '.local');
    }
}
