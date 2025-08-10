@extends(config('easy-event.web_extends_view'))

@section('content')
<div class="container mx-auto p-4">
  <div class="card bg-base-100 shadow">
    <div class="card-body">
      <h1 class="card-title text-3xl">{{ $event->title }}</h1>
      <div class="flex items-center gap-2 text-sm opacity-70">
        <span>{{ $event->starts_at->format(config('easy-event.date_format')) }}</span>
        @if($event->ends_at)
          <span>— {{ $event->ends_at->format(config('easy-event.date_format')) }}</span>
        @endif
        @if($event->all_day)
          <span class="badge">All day</span>
        @endif
      </div>
      @if($event->location)
        <div class="mt-2 text-sm">📍 {{ $event->location }}</div>
      @endif
      <div class="prose mt-4">{{ $event->description }}</div>
    </div>
  </div>
</div>
@endsection
