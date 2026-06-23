<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramStatusCommand extends Command
{
    protected $signature = 'telegram:status';

    protected $description = 'Check Telegram bot configuration and how to receive updates';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (! filled($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return self::FAILURE;
        }

        $token = (string) $token;
        $me = Http::get("https://api.telegram.org/bot{$token}/getMe")->json('result', []);
        $webhook = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json('result', []);

        $this->info('Telegram bot status');
        $this->line('Bot: @'.($me['username'] ?? 'unknown'));
        $this->line('Webhook: '.($webhook['url'] ?: '(not set — polling mode)'));
        $this->line('Pending updates: '.($webhook['pending_update_count'] ?? 0));

        if (! empty($webhook['last_error_message'])) {
            $this->warn('Last webhook error: '.$webhook['last_error_message']);
        }

        $this->newLine();
        $this->comment('Local dev (Option B): keep this running in a terminal:');
        $this->line('  php artisan telegram:poll --force');
        $this->newLine();
        $this->comment('Or start everything together:');
        $this->line('  composer dev');

        return self::SUCCESS;
    }
}
