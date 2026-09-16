<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use App\Support\Locale;
use Illuminate\Console\Command;

class SendTestEmail extends Command
{
    protected $signature = 'email:test {email : Where to send the test message}
                                       {--locale= : ar or en (defaults to the site language)}';

    protected $description = 'Send a real test email through the configured mailer to verify SMTP delivery';

    public function handle(NotificationService $notifications): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Not a valid email address: {$email}");

            return self::FAILURE;
        }

        $locale = (string) ($this->option('locale') ?: Locale::default());

        $this->line('');
        $this->line('  Mailer : <options=bold>'.config('mail.default').'</>');
        $this->line('  Host   : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
        $this->line('  From   : '.config('mail.from.address'));
        $this->line('  To     : '.$email);
        $this->line('  Locale : '.$locale);
        $this->line('');

        if (config('mail.default') === 'log') {
            $this->warn('  MAIL_MAILER is "log" — nothing will actually be delivered.');
        }

        try {
            $notifications->sendTest($email, $locale);
        } catch (\Throwable $e) {
            $this->error('  ✗ Delivery failed: '.$e->getMessage());
            $this->line('  The failure is recorded in Admin → Email logs and storage/logs/laravel.log.');

            return self::FAILURE;
        }

        $this->info('  ✓ Test email accepted by the mail server.');
        $this->line('  Check the inbox (and the spam folder) for: '.$email);

        return self::SUCCESS;
    }
}
