<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\PhotoStorage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'event_id', 'qr_source_id',
        'name', 'phone', 'phone_country', 'email', 'photo_path',
        'source', 'device', 'browser', 'platform', 'locale', 'ip_hash', 'session_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (EventRegistration $registration) {
            $registration->uuid ??= (string) Str::uuid();
        });

        // Photo cleanup lives here, not in the controller, so it holds for every
        // deletion path — a bulk action, a tinker session, a future cascade —
        // and not only the one admin button that happens to call it today.
        static::deleting(function (EventRegistration $registration) {
            app(PhotoStorage::class)->delete($registration->photo_path);
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function qrSource(): BelongsTo
    {
        return $this->belongsTo(QrSource::class);
    }

    public function hasPhoto(): bool
    {
        return (bool) $this->photo_path;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
