<?php

namespace Doyosi\EasyEvent\Http\Controllers\Panel;

use Doyosi\EasyEvent\Models\EasyEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class EasyEventController extends Controller
{
    public function index()
    {
        $events = EasyEvent::query()->orderByDesc('created_at')
            ->paginate(config('easy-event.pagination', 15));

        return view('easy-event::panel.index', compact('events'));
    }

    public function create()
    {
        return view('easy-event::panel.index'); // keep simple; you can split into its own view later
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        EasyEvent::create($data);
        return redirect()->route('panel.easy-events.index')
            ->with('status', 'Event created.');
    }

    public function edit(Event $event)
    {
        return view('easy-event::panel.index', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validated($request);
        $event->update($data);
        return redirect()->route('panel.easy-events.index')
            ->with('status', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('panel.easy-events.index')
            ->with('status', 'Event deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'event_id'   => ['nullable', 'string', 'max:191'],
            'type'       => ['required', 'string', Rule::in(config('easy-event.types'))],
            'title'      => ['required', 'string', 'max:255'],
            'description'=> ['nullable', 'string'],
            'starts_at'  => ['required', 'date'],
            'ends_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day'    => ['boolean'],
            'location'   => ['nullable', 'string', 'max:255'],
            'status'     => ['required', 'string', Rule::in(config('easy-event.status'))],
            'meta'       => ['nullable', 'array'],
        ]);
    }
}
