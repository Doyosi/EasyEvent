<?php

namespace Doyosi\EasyEvent\Http\Controllers\Web;

use Doyosi\EasyEvent\Models\EasyEvent;
use Illuminate\Routing\Controller;

class EasyEventController extends Controller
{
    public function index()
    {
        $events = EasyEvent::query()
            ->published()
            ->upcoming()
            ->paginate(config('easy-event.pagination', 15));

        return view('easy-event::web.index', compact('events'));
    }

    public function show(EasyEvent $event)
    {
        abort_unless($event->status === 'published', 404);
        return view('easy-event::web.show', compact('event'));
    }
}
