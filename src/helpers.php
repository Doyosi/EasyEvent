<?php

use Doyosi\EasyEvent\Facades\EasyEvent;

if (! function_exists('easy_events_today')) {
    function easy_events_today(?int $limit = null) {
        return EasyEvent::today($limit);
    }
}
if (! function_exists('easy_events_month')) {
    function easy_events_month(?int $limit = null) {
        return EasyEvent::thisMonth($limit);
    }
}
if (! function_exists('easy_events_recent')) {
    function easy_events_recent(int $limit = 5) {
        return EasyEvent::recent($limit);
    }
}
if (! function_exists('easy_events_upcoming')) {
    function easy_events_upcoming(int $limit = 10) {
        return EasyEvent::upcoming($limit);
    }
}
