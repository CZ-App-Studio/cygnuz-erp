@php
  $oldValues = is_array($activity->old_values) ? $activity->old_values : [];
  $newValues = is_array($activity->new_values) ? $activity->new_values : [];
  use Illuminate\Support\Str;
@endphp

@if(!empty($oldValues) || !empty($newValues))
  <div class="mt-1">
    <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#activity-details-{{$activity->id}}" aria-expanded="false" aria-controls="activity-details-{{$activity->id}}">
      @lang('View Changes')
    </button>
    <div class="collapse mt-2" id="activity-details-{{$activity->id}}">
      <div class="row">
        <div class="col-12">
          <strong class="text-muted">@lang('Changes'):</strong>
          <ul class="list-unstyled small">
            @foreach($newValues as $key => $newValue)
              @php
                $oldValue = $oldValues[$key] ?? null;
                $displayKey = Str::title(str_replace('_', ' ', $key));
                $displayNewValue = is_array($newValue) ? json_encode($newValue) : Str::limit(strval($newValue), 70);
              @endphp
              @if($oldValue != $newValue)
                <li>
                  {{ $displayKey }}: <code class="text-success">{{ $displayNewValue ?: '""' }}</code>
                  @if(array_key_exists($key, $oldValues))
                    <span class="text-muted">(was <code class="text-danger">{{ is_array($oldValue) ? json_encode($oldValue) : Str::limit(strval($oldValue), 30) }}</code>)</span>
                  @endif
                </li>
              @endif
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
@endif
