<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SalesStatus extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label', 'color', 'position', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('sales_statuses'));
        static::deleted(fn () => Cache::forget('sales_statuses'));
    }

    /** @return \Illuminate\Support\Collection<int, SalesStatus> */
    public static function cached()
    {
        return Cache::remember('sales_statuses', 3600, fn () => static::query()
            ->where('is_active', true)->orderBy('position')->get());
    }

    public static function defaultKey(): string
    {
        return static::cached()->firstWhere('is_default', true)?->key
            ?? static::cached()->first()?->key
            ?? 'new';
    }
}
