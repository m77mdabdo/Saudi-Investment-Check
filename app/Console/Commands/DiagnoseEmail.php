<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\MailHealth;
use Illuminate\Console\Command;

class DiagnoseEmail extends Command
{
    protected $signature = 'email:diagnose';

    protected $description = 'Check the mail configuration, SMTP connectivity and notification settings';

    public function handle(MailHealth $health): int
    {
        $report = $health->report();

        $this->line('');
        $this->components->info('Mail configuration');
        $this->table(['Setting', 'Value'], collect($report['config'])->map(fn ($v, $k) => [$k, $v])->values()->all());

        $this->components->info('Notification settings');
        $this->table(['Check', 'Value'], collect($report['settings'])->map(fn ($v, $k) => [$k, $v])->values()->all());

        $this->components->info('SMTP connection');
        foreach ($report['connection'] as $line) {
            $this->line('  '.$line);
        }

        $problems = $report['problems'];

        $this->line('');

        if ($problems === []) {
            $this->components->info('No problems detected. Run `php artisan email:test you@example.com` to confirm delivery.');

            return self::SUCCESS;
        }

        $this->components->error(count($problems).' problem(s) found:');

        foreach ($problems as $problem) {
            $this->line('  • '.$problem);
        }

        $this->line('');
        $this->line('  Templates: '.NotificationTemplate::query()->where('is_active', true)->count().' active');
        $this->line('  Recent failures: '.NotificationLog::query()->where('status', 'failed')->count());

        return self::FAILURE;
    }
}
