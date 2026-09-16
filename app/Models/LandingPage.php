<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'slug', 'name', 'content', 'seo_title', 'seo_description',
        'seo_image', 'hero_image_query', 'hero_image_url', 'is_active',
    ];

    protected $casts = ['content' => 'array', 'is_active' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->content, $key, $default);
    }
}
