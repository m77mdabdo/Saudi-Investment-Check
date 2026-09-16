<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPES = ['single', 'multiple', 'text', 'textarea'];

    protected $fillable = [
        'key', 'type', 'title', 'subtitle', 'icon', 'placeholder',
        'is_required', 'is_scored', 'is_active', 'position', 'meta',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_scored' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class)->orderBy('position');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    public function isChoice(): bool
    {
        return in_array($this->type, ['single', 'multiple'], true);
    }
}
