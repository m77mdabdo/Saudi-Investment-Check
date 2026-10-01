<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use Illuminate\Console\Command;

/**
 * Brings the STORED CMS copy in line with the registration flow.
 *
 * Stored content wins over the lang files (see LandingPage::localizedContent),
 * so the new benefit cards and privacy note shipped in lang/{ar,en}/ do not
 * appear on a site whose landing_pages row still holds the quiz-era text.
 *
 * Touches exactly two keys per locale — `benefits` and `footer_note` — and
 * leaves every other key in the row alone. Idempotent: running it twice makes
 * no further change, and it reports "already up to date".
 */
class RefreshRegistrationCopy extends Command
{
    protected $signature = 'app:refresh-registration-copy {--dry-run : Show what would change without writing}';

    protected $description = 'Update the stored CMS benefit cards and footer note to the registration copy (ar + en)';

    /** @var array<string,array<string,mixed>> */
    protected array $copy = [
        'ar' => [
            'benefits' => [
                ['icon' => '⚡', 'title' => 'ثواني وتخلص', 'text' => 'الاسم والتليفون وبس.'],
                ['icon' => '📞', 'title' => 'هنتواصل معاك', 'text' => 'الفريق هيكلّمك بعد الفعالية.'],
                ['icon' => '🤝', 'title' => 'نساعدك تبدأ صح', 'text' => 'تأسيس، تراخيص، تكاليف — قولنا محتاج إيه.'],
            ],
            'footer_note' => 'بنستخدم بياناتك إننا نتواصل معاك بخصوص استفسارك — مش أكتر.',
        ],
        'en' => [
            'benefits' => [
                ['icon' => '⚡', 'title' => 'Done in seconds', 'text' => 'Just your name and phone.'],
                ['icon' => '📞', 'title' => "We'll be in touch", 'text' => 'The team calls you after the event.'],
                ['icon' => '🤝', 'title' => "We'll help you start right", 'text' => 'Setup, licensing, costs — tell us what you need.'],
            ],
            'footer_note' => 'We use your details only to contact you about your enquiry.',
        ],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $page = LandingPage::query()->where('slug', 'default')->first();

        if (! $page) {
            $this->error('No landing page with slug "default" — nothing to update.');

            return self::FAILURE;
        }

        $changes = [];

        // Arabic is the base locale: it lives in the `content` column.
        $arabic = (array) ($page->content ?? []);
        $arabicChanges = $this->diff($arabic, $this->copy['ar']);

        if ($arabicChanges) {
            $changes['ar'] = $arabicChanges;
            $arabic = array_replace($arabic, $this->copy['ar']);
        }

        // English lives under translations.en.content.
        $translations = (array) ($page->translations ?? []);
        $english = (array) data_get($translations, 'en.content', []);
        $englishChanges = $this->diff($english, $this->copy['en']);

        if ($englishChanges) {
            $changes['en'] = $englishChanges;
            $translations['en']['content'] = array_replace($english, $this->copy['en']);
        }

        if (! $changes) {
            $this->info('Already up to date — no keys changed.');

            return self::SUCCESS;
        }

        foreach ($changes as $locale => $keys) {
            $this->line('');
            $this->line("  <options=bold>[{$locale}]</> ".implode(', ', array_keys($keys)));

            foreach ($keys as $key => [$from, $to]) {
                $this->line("    <fg=red>- {$key}:</> ".$this->preview($from));
                $this->line("    <fg=green>+ {$key}:</> ".$this->preview($to));
            }
        }

        $untouched = array_values(array_diff(array_keys((array) ($page->content ?? [])), ['benefits', 'footer_note']));
        $this->line('');
        $this->line('  untouched keys: '.(implode(', ', $untouched) ?: '(none)'));

        if ($dry) {
            $this->line('');
            $this->warn('Dry run — nothing was written.');

            return self::SUCCESS;
        }

        $page->content = $arabic;
        $page->translations = $translations;
        $page->save();

        $this->line('');
        $this->info('Updated '.implode(' and ', array_keys($changes)).'.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string,mixed>  $current
     * @param  array<string,mixed>  $target
     * @return array<string,array{0:mixed,1:mixed}>
     */
    protected function diff(array $current, array $target): array
    {
        $changes = [];

        foreach ($target as $key => $value) {
            if (($current[$key] ?? null) !== $value) {
                $changes[$key] = [$current[$key] ?? null, $value];
            }
        }

        return $changes;
    }

    protected function preview(mixed $value): string
    {
        if ($value === null) {
            return '(not set)';
        }

        if (is_array($value)) {
            return implode(' / ', array_map(fn ($row) => (string) ($row['title'] ?? '?'), $value));
        }

        return mb_strimwidth((string) $value, 0, 72, '…');
    }
}
