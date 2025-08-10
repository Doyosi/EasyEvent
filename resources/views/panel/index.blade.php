@extends(config('easy-event.panel_extends_view'))

@section('content')
<div class="container mx-auto p-4">
  @if(session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
  @endif

  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Manage Events</h1>
    <a href="{{ route('panel.easy-events.create') }}" class="btn btn-primary">+ New Event</a>
  </div>

  <div class="overflow-x-auto">
    <table class="table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Type</th>
          <th>Starts</th>
          <th>Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse(($events ?? []) as $event)
        <tr>
          <td>{{ $event->title }}</td>
          <td>{{ $event->type }}</td>
          <td>{{ $event->starts_at?->format(config('easy-event.date_format')) }}</td>
          <td>{{ $event->status }}</td>
          <td class="text-right">
            <a href="{{ route('panel.easy-events.edit', $event) }}" class="btn btn-xs">Edit</a>
            <form action="{{ route('panel.easy-events.destroy', $event) }}" method="POST" class="inline">
              @csrf @method('DELETE')
              <button class="btn btn-xs btn-error" onclick="return confirm('Delete?')">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5">No events yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(isset($events))
    <div class="mt-4">{{ $events->links() }}</div>
  @endif
</div>
@endsection
