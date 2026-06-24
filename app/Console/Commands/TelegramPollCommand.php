<?php

namespace App\Console\Commands;

use App\Services\TelegramCallbackService;
use App\Services\TelegramUpdateDeduplicator;
use App\Services\TelegramUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll
                            {--timeout=30 : Long-poll timeout in seconds (max 50)}
                            {--force : Remove an active webhook without prompting}';

    protected $description = 'Receive Telegram updates via long polling (always-on local dev without a tunnel)';

    private int $offset = 0;

    public function __construct(
        private readonly TelegramUpdateService $telegramUpdates,
        private readonly TelegramCallbackService $telegramCallbacks,
        private readonly TelegramUpdateDeduplicator $telegramDedup,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        if (! filled($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return self::FAILURE;
        }

        $token = (string) $token;

        if (! $this->ensurePollingMode($token)) {
            return self::FAILURE;
        }

        $timeout = min(50, max(1, (int) $this->option('timeout')));

        $this->info('Telegram long polling started. Press Ctrl+C to stop.');
        $this->line('Keep this running while you develop — no Cloudflare tunnel needed.');

        while (true) {
            $response = Http::timeout($timeout + 10)->get(
                "https://api.telegram.org/bot{$token}/getUpdates",
                [
                    'offset' => $this->offset,
                    'timeout' => $timeout,
                    'allowed_updates' => json_encode(['message', 'callback_query']),
                ],
            );

            if (! $response->successful()) {
                $this->warn('getUpdates failed: '.$response->body());
                sleep(3);

                continue;
            }

            $updates = $response->json('result', []);
            if (! is_array($updates)) {
                sleep(1);

                continue;
            }

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }

                $updateId = $update['update_id'] ?? null;
                if (is_int($updateId)) {
                    $this->offset = $updateId + 1;

                    if ($this->telegramDedup->alreadyProcessed($updateId)) {
                        continue;
                    }
                }

                $message = $update['message'] ?? null;
                if (is_array($message)) {
                    $this->telegramUpdates->processMessage($message);
                }

                $callbackQuery = $update['callback_query'] ?? null;
                if (is_array($callbackQuery)) {
                    $this->telegramCallbacks->processCallbackQuery($callbackQuery);
                }
            }
        }
    }

    private function ensurePollingMode(string $token): bool
    {
        $info = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json('result', []);
        $webhookUrl = is_string($info['url'] ?? null) ? $info['url'] : '';

        if ($webhookUrl !== '') {
            $this->warn("Webhook is active: {$webhookUrl}");
            $shouldDelete = $this->option('force') || $this->confirm('Remove webhook and switch to long polling?', true);
            if (! $shouldDelete) {
                return false;
            }

            $delete = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook", [
                'drop_pending_updates' => false,
            ]);

            if (! ($delete->json('ok') ?? false)) {
                $this->error('Could not delete webhook: '.($delete->body()));

                return false;
            }

            $this->info('Webhook removed.');
        }

        return true;
    }
}
