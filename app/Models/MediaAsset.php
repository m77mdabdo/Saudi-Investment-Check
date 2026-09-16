<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot', 'provider', 'external_id', 'query', 'url', 'thumb_url',
        'photographer', 'photographer_url', 'avg_color', 'width', 'height', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
