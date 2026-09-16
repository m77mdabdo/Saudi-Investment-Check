<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_question_id', 'key', 'label', 'description', 'icon',
        'score', 'requires_detail', 'detail_label', 'is_active', 'position',
    ];

    protected $casts = [
        'requires_detail' => 'boolean',
        'is_active' => 'boolean',
        'score' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
