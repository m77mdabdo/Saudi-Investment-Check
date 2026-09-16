<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;

    public const NAMES = [
        'landing_page_view',
        'quiz_started',
        'quiz_question_completed',
        'quiz_completed',
        'lead_form_viewed',
        'lead_submitted',
        'result_ready',
        'result_needs_prep',
        'result_early_stage',
        'cta_clicked',
        'meeting_clicked',
    ];

    protected $fillable = [
        'name', 'event_id', 'lead_id', 'qr_source_id', 'session_hash', 'source',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
        'device', 'browser', 'payload',
    ];

    protected $casts = ['payload' => 'array'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
