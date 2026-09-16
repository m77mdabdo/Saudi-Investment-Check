<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'template_key', 'type', 'channel', 'locale', 'mailer',
        'recipient', 'subject', 'status', 'error', 'sent_at', 'failed_at', 'attempts', 'meta',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term = trim((string) $term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%'.$term.'%';
            $q->where('recipient', 'like', $like)
                ->orWhere('subject', 'like', $like)
                ->orWhere('template_key', 'like', $like);
        });
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'sent' => 'emerald',
            'failed' => 'coral',
            default => 'slate',
        };
    }

    public function isRetryable(): bool
    {
        return in_array($this->status, ['failed', 'skipped'], true)
            && in_array($this->template_key, ['customer_result', 'admin_new_lead', 'test'], true);
    }
}
