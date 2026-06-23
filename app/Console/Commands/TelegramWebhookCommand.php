<?php

namespace App\Console\Commands;

use App\Support\TelegramUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook
                            {action=info : info, set, sync, or delete}
                            {url? : Public base URL for set (optional override)}';

    protected $description = 'Inspect or register the Telegram bot webhook';

    public function handle(): int
    {
        $token = $this->botToken();
        if ($token === null) {
            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'info' => $this->showInfo($token),
            'set' => $this->setWebhook($token, $this->resolveSetUrl()),
            'sync' => $this->syncWebhook($token),
            'delete' => $this->deleteWebhook($token),
            default => $this->invalidAction(),
        };
    }

    private function botToken(): ?string
    {
        $token = config('services.telegram.bot_token');
        if (! filled($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return null;
        }

        return (string) $token;
    }

    private function showInfo(string $token): int
    {
        $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
        $data = $response->json('result', []);

        $this->info('Telegram webhook status');
        $this->line('URL: '.($data['url'] ?: '(not set)'));
        $this->line('Pending updates: '.($data['pending_update_count'] ?? 0));

        $expected = TelegramUrl::webhookEndpoint();
        if ($expected) {
            $this->line('Expected (from APP_URL / TELEGRAM_WEBHOOK_URL): '.$expected);
        } else {
            $this->comment('No public HTTPS URL configured — use telegram:poll for local dev, or set TELEGRAM_WEBHOOK_URL.');
        }

        if (! empty($data['last_error_message'])) {
            $this->warn('Last error: '.$data['last_error_message']);
        }

        if (($data['pending_update_count'] ?? 0) > 0) {
            $this->comment('Tip: after fixing the URL, run telegram:webhook sync or set <url> again.');
        }

        return self::SUCCESS;
    }

    private function resolveSetUrl(): ?string
    {
        $url = $this->argument('url');
        if (is_string($url) && $url !== '') {
            return rtrim($url, '/');
        }

        return TelegramUrl::webhookBaseUrl();
    }

    private function syncWebhook(string $token): int
    {
        $endpoint = TelegramUrl::webhookEndpoint();
        if (! $endpoint) {
            $this->error('Cannot sync: set TELEGRAM_WEBHOOK_URL or APP_URL to a public https URL (not localhost).');
            $this->line('For local dev without a tunnel, run: php artisan telegram:poll');

            return self::FAILURE;
        }

        return $this->setWebhook($token, TelegramUrl::webhookBaseUrl(), $endpoint);
    }

    private function setWebhook(string $token, ?string $baseUrl, ?string $webhookUrl = null): int
    {
        if (! filled($baseUrl)) {
            $this->error('Usage: php artisan telegram:webhook set https://your-public-url');

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) $baseUrl, '/');
        $webhookUrl ??= str_ends_with($baseUrl, '/api/v1/telegram/webhook')
            ? $baseUrl
            : "{$baseUrl}/api/v1/telegram/webhook";

        $payload = ['url' => $webhookUrl];
        $secret = config('services.telegram.webhook_secret');
        if (filled($secret)) {
            $payload['secret_token'] = $secret;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", $payload);
        $body = $response->json();

        if (! ($body['ok'] ?? false)) {
            $this->error('setWebhook failed: '.($body['description'] ?? $response->body()));

            return self::FAILURE;
        }

        $this->info("Webhook registered: {$webhookUrl}");
        if (filled($secret)) {
            $this->line('Secret token: configured from TELEGRAM_WEBHOOK_SECRET');
        }

        return self::SUCCESS;
    }

    private function deleteWebhook(string $token): int
    {
        $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook", [
            'drop_pending_updates' => false,
        ]);
        $body = $response->json();

        if (! ($body['ok'] ?? false)) {
            $this->error('deleteWebhook failed: '.($body['description'] ?? $response->body()));

            return self::FAILURE;
        }

        $this->info('Webhook removed. You can run telegram:poll for local long-polling.');

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Unknown action. Use: php artisan telegram:webhook info|set|sync|delete');

        return self::FAILURE;
    }
}
