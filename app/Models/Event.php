<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'city', 'country', 'starts_at', 'ends_at',
        'status', 'is_default', 'description', 'logo_path', 'cta_settings',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_default' => 'boolean',
        'cta_settings' => 'array',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function qrSources(): HasMany
    {
        return $this->hasMany(QrSource::class);
    }

    public static function current(): ?self
    {
        return static::query()
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderByDesc('starts_at')
            ->first() ?? static::query()->orderByDesc('is_default')->first();
    }

    public function getDateRangeAttribute(): string
    {
        if (! $this->starts_at) {
            return '';
        }

        return $this->ends_at
            ? $this->starts_at->format('d M Y').' — '.$this->ends_at->format('d M Y')
            : $this->starts_at->format('d M Y');
    }
}
