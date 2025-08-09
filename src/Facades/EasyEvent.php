<?php

namespace Doyosi\EasyEvent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection today(int $limit = null)
 * @method static \Illuminate\Support\Collection thisMonth(int $limit = null)
 * @method static \Illuminate\Support\Collection recent(int $limit = 5)
 * @method static \Illuminate\Support\Collection upcoming(int $limit = 10)
 */
class EasyEvent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'doyosi.easy-event';
    }
}
