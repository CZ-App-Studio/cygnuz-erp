@php
  // Ensure $activity->old_values and $activity->new_values are arrays, even if empty.
  // The Audit model from laravel-auditing usually casts these from JSON to arrays.
  use Illuminate\Support\Str;
  $oldValues = is_array($activity->old_values) ? $activity->old_values : [];
  $newValues = is_array($activity->new_values) ? $activity->new_values : [];
  $changedKeys = array_keys($newValues); // Focus on what changed to become the new value
@endphp

@if(!empty($oldValues) || !empty($newValues))
  <div class="mt-1">
    <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#activity-details-{{$activity->id}}" aria-expanded="false" aria-controls="activity-details-{{$activity->id}}">
      @lang('View Changes')
    </button>
    <div class="collapse mt-2" id="activity-details-{{$activity->id}}">
      <div class="row">
        <div class="col-md-{{ !empty($oldValues) && !empty($newValues) && count(array_intersect_key($oldValues, $newValues)) > 0 ? '6' : '12' }}">
          @if(!empty($newValues))
            <strong>@lang('Changes (New Values)'):</strong>
            <ul class="list-unstyled small">
              @foreach($newValues as $key => $newValue)
                @php
                  $oldValue = $oldValues[$key] ?? null;
                  $displayKey = Str::title(str_replace('_', ' ', $key));
                  $displayNewValue = is_array($newValue) ? json_encode($newValue) : Str::limit(strval($newValue), 70);
                @endphp
                <li>
                  {{ $displayKey }}: <code class="text-success">{{ $displayNewValue }}</code>
                  @if(array_key_exists($key, $oldValues) && $oldValue != $newValue)
                    (was <code class="text-danger">{{ is_array($oldValue) ? json_encode($oldValue) : Str::limit(strval($oldValue), 30) }}</code>)
                  @elseif(!array_key_exists($key, $oldValues) && $newValue !== null)
                    <span class="text-muted">(@lang('added'))</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
        </div>

        {{-- Optional: If you specifically want to list ONLY old values that are not in new (less common for "updated" event) --}}
        @if(!empty($oldValues) && count(array_diff_key($oldValues, $newValues)) > 0)
          <div class="col-md-6">
            <strong>@lang('Values Removed/Nulled'):</strong>
            <ul class="list-unstyled small">
              @foreach($oldValues as $key => $value)
                @if(!array_key_exists($key, $newValues) || $newValues[$key] === null)
                  <li>{{ Str::title(str_replace('_', ' ', $key)) }}: <code class="text-danger">{{ is_array($value) ? json_encode($value) : Str::limit(strval($value), 50) }}</code></li>
                @endif
              @endforeach
            </ul>
          </div>
        @endif
      </div>
    </div>
  </div>
@endif
