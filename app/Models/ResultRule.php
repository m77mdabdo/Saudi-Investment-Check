<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'classification', 'indicator', 'min_score', 'max_score',
        'headline', 'main_text', 'body', 'highlight', 'bullets',
        'primary_cta_label', 'primary_cta_url', 'secondary_cta_label', 'secondary_cta_url',
        'disclaimer', 'image_query', 'is_active', 'position',
    ];

    protected $casts = [
        'bullets' => 'array',
        'is_active' => 'boolean',
        'min_score' => 'integer',
        'max_score' => 'integer',
    ];

    public static function forScore(int $score): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('min_score', 'desc')
            ->first();
    }

    public function indicatorTone(): string
    {
        return match ($this->indicator) {
            'green' => 'emerald',
            'amber' => 'amber',
            default => 'coral',
        };
    }
}
