<?php

namespace Doyosi\EasyEvent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;
// hasFactory is not used in this file, so it can be removed

class EasyEvent extends Model
{
    use HasFactory;
    use HasTranslations;
    protected $table;
    protected $guarded = [];
    public array $translatable = ['title', 'description', 'location'];


    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'all_day'   => 'boolean',
        'meta'      => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('easy-event.table', 'easy_events');
    }

    /* ---- Scopes ---- */

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function scopeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeBetween(Builder $q, Carbon|string $from, Carbon|string $to): Builder
    {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to   = $to   instanceof Carbon ? $to   : Carbon::parse($to);

        return $q->where(function ($q) use ($from, $to) {
            $q->whereBetween('starts_at', [$from, $to])
              ->orWhereBetween('ends_at', [$from, $to])
              ->orWhere(function ($q) use ($from, $to) {
                  $q->where('starts_at', '<=', $from)
                    ->where('ends_at', '>=', $to);
              });
        });
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('starts_at', '>=', now())->orderBy('starts_at', 'asc');
    }

    public function scopePast(Builder $q): Builder
    {
        return $q->where('starts_at', '<', now())->orderBy('starts_at', 'desc');
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->between(now()->startOfDay(), now()->endOfDay());
    }

    public function scopeThisMonth(Builder $q): Builder
    {
        return $q->between(now()->startOfMonth(), now()->endOfMonth());
    }

    protected static function newFactory()
    {
        return \Doyosi\EasyEvent\Database\Factories\EasyEventFactory::new();
    }
}
