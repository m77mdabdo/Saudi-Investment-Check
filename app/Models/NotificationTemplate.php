<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    public const VARIABLES = [
        'name', 'company', 'whatsapp', 'email', 'score', 'max_score',
        'result', 'classification', 'source', 'event', 'main_question',
        'cta_url', 'cta_label', 'lead_url', 'result_url', 'date',
    ];

    protected $fillable = ['key', 'audience', 'name', 'subject', 'body', 'recipients', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function render(string $field, array $vars): string
    {
        $text = (string) $this->{$field};

        foreach ($vars as $key => $value) {
            $text = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string) $value, $text);
        }

        return $text;
    }
}
