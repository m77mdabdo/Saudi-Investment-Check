<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;

/**
 * Answers "why is mail not arriving?" without exposing any credential.
 * Used by `php artisan email:diagnose` and the admin Email logs screen.
 */
class MailHealth
{
    public function __construct(
        protected SettingsService $settings,
        protected NotificationService $notifications,
    ) {}

    /** @return array{config:array,settings:array,connection:array,problems:array,ok:bool} */
    public function report(): array
    {
        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');
        $problems = [];

        $config = [
            'MAIL_MAILER' => $mailer,
            'host' => $host,
            'port' => (string) $port,
            'encryption' => (string) (config('mail.mailers.smtp.scheme') ?: config('mail.mailers.smtp.encryption') ?: 'auto'),
            'username configured' => filled(config('mail.mailers.smtp.username')) ? 'yes' : 'no',
            'password configured' => filled(config('mail.mailers.smtp.password')) ? 'yes' : 'no',
            'from address' => (string) config('mail.from.address'),
            'from name' => (string) config('mail.from.name'),
            'config cached' => file_exists(base_path('bootstrap/cache/config.php')) ? 'yes (run config:cache after .env edits)' : 'no',
            'queue connection' => (string) config('queue.default'),
        ];

        if ($mailer === 'log') {
            $problems[] = 'MAIL_MAILER is "log": messages are written to storage/logs/laravel.log and never delivered.';
        }

        if ($mailer === 'array') {
            $problems[] = 'MAIL_MAILER is "array": messages are discarded (test mode).';
        }

        if ($mailer === 'smtp') {
            if (blank(config('mail.mailers.smtp.username')) || blank(config('mail.mailers.smtp.password'))) {
                $problems[] = 'SMTP username or password is missing from the environment.';
            }

            if (blank($host) || $host === '127.0.0.1') {
                $problems[] = 'MAIL_HOST is not a real SMTP server ('.($host ?: 'empty').').';
            }
        }

        if (! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
            $problems[] = 'MAIL_FROM_ADDRESS is not a valid email address.';
        }

        $recipients = $this->notifications->adminRecipients();

        $settings = [
            'notify sales team' => $this->notifications->enabled('notify_admin') ? 'on' : 'OFF',
            'notify customer' => $this->notifications->enabled('notify_customer') ? 'on' : 'OFF',
            'notify on status change' => $this->notifications->enabled('notify_status_change', true) ? 'on' : 'OFF',
            'sales recipients' => $recipients ? implode(', ', $recipients) : 'none',
            'active templates' => (string) NotificationTemplate::query()->where('is_active', true)->count(),
            'sent (last 7 days)' => (string) NotificationLog::query()->where('status', 'sent')->where('created_at', '>=', now()->subDays(7))->count(),
            'failed (last 7 days)' => (string) NotificationLog::query()->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        if (! $this->notifications->enabled('notify_admin')) {
            $problems[] = 'Sales notifications are switched off in Admin → Settings.';
        }

        if (! $this->notifications->enabled('notify_customer')) {
            $problems[] = 'Customer result emails are switched off in Admin → Settings.';
        }

        if ($recipients === []) {
            $problems[] = 'No sales recipients configured and MAIL_FROM_ADDRESS is unusable.';
        }

        $connection = $this->probe($host, $port, $problems);

        return [
            'config' => $config,
            'settings' => $settings,
            'connection' => $connection,
            'problems' => $problems,
            'ok' => $problems === [],
        ];
    }

    /**
     * Opens a socket to the SMTP host — this is what catches a hosting provider
     * blocking outbound mail ports. No credentials are sent here.
     *
     * @param  array<int,string>  $problems
     * @return array<int,string>
     */
    protected function probe(string $host, int $port, array &$problems): array
    {
        if (config('mail.default') !== 'smtp' || blank($host)) {
            return ['Skipped (mailer is not SMTP).'];
        }

        $lines = [];
        $errno = 0;
        $errstr = '';
        $started = microtime(true);
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 8);

        if (! $socket) {
            $problems[] = "Cannot open a connection to {$host}:{$port} ({$errstr}). The host may block outbound SMTP — try the hosting provider's own SMTP server or port 465.";

            return ["✗ TCP {$host}:{$port} — {$errstr}"];
        }

        stream_set_timeout($socket, 8);
        $banner = trim((string) fgets($socket, 512));
        $lines[] = sprintf('✓ TCP %s:%d reachable in %dms', $host, $port, (int) ((microtime(true) - $started) * 1000));
        $lines[] = '  '.($banner !== '' ? $banner : 'no banner received');

        if (! str_starts_with($banner, '220')) {
            $problems[] = "The SMTP server answered with an unexpected banner: {$banner}";
        }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return $lines;
    }
}
