<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'quiz_question_id', 'quiz_option_id', 'question_key', 'question_title',
        'option_key', 'answer_label', 'answer_text', 'score',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuizOption::class, 'quiz_option_id');
    }

    /** Stored answer, translated when the option still exists. */
    public function localizedAnswer(): ?string
    {
        if ($this->relationLoaded('option') || $this->quiz_option_id) {
            $label = $this->option?->t('label');

            if (filled($label)) {
                return $label;
            }
        }

        return $this->answer_label ?: $this->answer_text;
    }
}
