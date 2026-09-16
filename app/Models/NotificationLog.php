<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = ['lead_id', 'template_key', 'channel', 'recipient', 'subject', 'status', 'error'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
