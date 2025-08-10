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
            'title'       => $this->getTranslation('title', app()->getLocale(), false) ?? $this->title,
            'description' => $this->getTranslation('description', app()->getLocale(), false) ?? ($this->description ?? ''),
            'location'    => $this->getTranslation('location', app()->getLocale(), false) ?? ($this->location ?? ''),
            'starts_at'   => optional($this->starts_at)->toIso8601String(),
            'ends_at'     => optional($this->ends_at)->toIso8601String(),
            'starts_at_formatted' => optional($this->starts_at)?->format(config('easy-event.date_format')),
            'ends_at_formatted'   => optional($this->ends_at)?->format(config('easy-event.date_format')),
            'all_day'     => (bool)$this->all_day,
            'status'      => $this->status,
            'meta'        => $this->meta ?? (object)[],
        ];
    }
}
