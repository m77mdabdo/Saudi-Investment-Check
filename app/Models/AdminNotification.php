<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'body', 'url', 'icon', 'level', 'lead_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
