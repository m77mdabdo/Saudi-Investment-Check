<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'event_id', 'qr_source_id', 'assigned_to',
        'name', 'company', 'whatsapp', 'whatsapp_country', 'email', 'consent', 'consent_at',
        'score', 'max_score', 'result_key', 'classification', 'result_rule_id',
        'sales_status', 'status_changed_at',
        'source', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
        'device', 'browser', 'platform', 'locale', 'ip_hash', 'session_hash',
        'answers_summary',
    ];

    protected $casts = [
        'consent' => 'boolean',
        'consent_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'answers_summary' => 'array',
        'score' => 'integer',
        'max_score' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            $lead->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function qrSource(): BelongsTo
    {
        return $this->belongsTo(QrSource::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LeadAnswer::class)->orderBy('id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ResultRule::class, 'result_rule_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class)->latest();
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class)->latest();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term = trim((string) $term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%'.$term.'%';
            $q->where('name', 'like', $like)
                ->orWhere('company', 'like', $like)
                ->orWhere('whatsapp', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    public function statusMeta(): ?SalesStatus
    {
        return SalesStatus::cached()->firstWhere('key', $this->sales_status);
    }

    public function tone(): string
    {
        return match ($this->result_key) {
            'ready' => 'emerald',
            'needs_prep' => 'amber',
            default => 'coral',
        };
    }

    public function whatsappLink(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public function scorePercent(): int
    {
        $max = max(1, (int) $this->max_score);

        return (int) round(($this->score / $max) * 100);
    }

    public function mainQuestion(): ?string
    {
        return $this->answers_summary['main_question'] ?? null;
    }
}
