<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Editable subject lines (and optional intro copy) for the transactional
 * emails. The HTML chrome itself lives in Blade so the layout stays
 * email-client safe no matter what is typed here.
 */
class NotificationTemplate extends Model
{
    use HasFactory, HasTranslations;

    public const VARIABLES = [
        'name', 'company', 'whatsapp', 'email', 'score', 'max_score',
        'result', 'classification', 'source', 'event', 'main_question',
        'cta_url', 'cta_label', 'lead_url', 'result_url', 'date',
    ];

    protected $fillable = ['key', 'audience', 'name', 'subject', 'body', 'recipients', 'is_active', 'translations'];

    protected $casts = ['is_active' => 'boolean', 'translations' => 'array'];

    public function localizedSubject(?string $locale = null): ?string
    {
        return $this->t('subject', $locale);
    }

    public function localizedBody(?string $locale = null): ?string
    {
        return $this->t('body', $locale);
    }

    public function render(string $field, array $vars, ?string $locale = null): string
    {
        return $this->renderString((string) $this->t($field, $locale), $vars);
    }

    public function renderString(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string) $value, $text);
        }

        return $text;
    }
}
