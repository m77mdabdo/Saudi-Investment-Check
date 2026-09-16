<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'name', 'slug', 'campaign', 'medium',
        'description', 'is_active', 'scans',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function getTrackingUrlAttribute(): string
    {
        return url('/').'?source='.$this->slug
            .($this->campaign ? '&utm_campaign='.urlencode($this->campaign) : '')
            .($this->medium ? '&utm_medium='.urlencode($this->medium) : '');
    }
}
