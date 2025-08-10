@extends(config('easy-event.web_extends_view'))

@section('content')
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">Events</h1>
  <div class="grid gap-4">
    @forelse($events as $event)
      <a href="{{ route('easy-events.show', $event) }}" class="card bg-base-100 shadow hover:shadow-lg transition">
        <div class="card-body">
          <h2 class="card-title">{{ $event->title }}</h2>
          <p class="text-sm opacity-70">{{ $event->starts_at->format(config('easy-event.date_format')) }}</p>
          <div class="badge">{{ ucfirst($event->type) }}</div>
        </div>
      </a>
    @empty
      <div class="alert">No events.</div>
    @endforelse
  </div>

  <div class="mt-6">
    {{ $events->links() }}
  </div>
</div>
@endsection
