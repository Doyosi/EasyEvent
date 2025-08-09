<?php

namespace Doyosi\EasyEvent\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array (no data wrapper).
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'event_id'    => $this->event_id,
            'type'        => $this->type,
            'title'       => $this->title,
            'description' => $this->when($this->description, (string)$this->description),
            'starts_at'   => optional($this->starts_at)->toIso8601String(),
            'ends_at'     => optional($this->ends_at)->toIso8601String(),
            'starts_at_formatted' => optional($this->starts_at)?->format(config('easy-event.date_format')),
            'ends_at_formatted'   => optional($this->ends_at)?->format(config('easy-event.date_format')),
            'all_day'     => (bool)$this->all_day,
            'location'    => $this->when($this->location, (string)$this->location),
            'status'      => $this->status,
            'meta'        => $this->meta ?? (object)[],
        ];
    }
}
