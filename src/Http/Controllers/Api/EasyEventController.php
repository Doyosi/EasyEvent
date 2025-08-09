<?php

namespace Doyosi\EasyEvent\Http\Controllers\Api;

use Doyosi\EasyEvent\Http\Resources\EventResource;
use Doyosi\EasyEvent\Models\EasyEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EasyEventController extends Controller
{
    /**
     * GET /api/easy-events
     *
     * Query params:
     * - limit (int)        : number of items (no pagination when present)
     * - paginate (0|1)     : toggle pagination (default from config)
     * - per_page (int)     : items per page (when paginate=1)
     * - scope (string)     : today|month|upcoming|past
     * - type (string)      : filter by type
     * - status (string)    : filter by status
     * - from, to (date/dt) : between range (overrides scope when both given)
     */
    public function index(Request $request)
    {
        EventResource::withoutWrapping(); // return plain array for the widget

        $validated = $request->validate([
            'limit'     => ['nullable', 'integer', 'min:1', 'max:' . (int)config('easy-event.routes.api.max_limit', 100)],
            'paginate'  => ['nullable', 'boolean'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:200'],
            'scope'     => ['nullable', Rule::in(['today', 'month', 'upcoming', 'past'])],
            'type'      => ['nullable', 'string'],
            'status'    => ['nullable', 'string', Rule::in(config('easy-event.status', ['draft','published','archived']))],
            'from'      => ['nullable', 'date'],
            'to'        => ['nullable', 'date'],
        ]);

        $q = EasyEvent::query()->published()->orderBy('starts_at');

        if (!empty($validated['status'])) {
            $q->where('status', $validated['status']);
        }
        if (!empty($validated['type'])) {
            $q->type($validated['type']);
        }

        // explicit range beats scope
        if (!empty($validated['from']) && !empty($validated['to'])) {
            $q->between(Carbon::parse($validated['from']), Carbon::parse($validated['to']));
        } else {
            switch ($validated['scope'] ?? null) {
                case 'today':    $q->today();    break;
                case 'month':    $q->thisMonth();break;
                case 'upcoming': $q->upcoming(); break;
                case 'past':     $q->past();     break;
            }
        }

        // If limit present -> return plain collection
        if (!empty($validated['limit'])) {
            $items = $q->limit((int)$validated['limit'])->get();
            return EventResource::collection($items);
        }

        // Pagination? (default from config)
        $paginate = (bool)($validated['paginate'] ?? (int)config('easy-event.routes.api.paginate_default', 0));
        if ($paginate) {
            $perPage = (int)($validated['per_page'] ?? config('easy-event.routes.api.per_page', 15));
            $paginator = $q->paginate($perPage);
            return EventResource::collection($paginator);
        }

        // Default: capped list for widget
        $default = (int)config('easy-event.routes.api.per_page', 15);
        $items = $q->limit($default)->get();
        return EventResource::collection($items);
    }

    /**
     * GET /api/easy-events/{event}
     */
    public function show(Event $event)
    {
        abort_unless($event->status === 'published', 404);
        EventResource::withoutWrapping();
        return new EventResource($event);
    }
}
