<div class="offcanvas offcanvas-end" style="width: 450px;" tabindex="-1" id="offcanvasTaskPriorityForm" aria-labelledby="offcanvasTaskPriorityFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasTaskPriorityFormLabel">@lang('Add Task Priority')</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="taskPriorityForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="task_priority_id_input"> {{-- Unique ID for this form's hidden ID --}}
      <input type="hidden" name="_method" id="taskPriorityFormMethod" value="POST">

      <div class="mb-3">
        <label for="task_priority_name" class="form-label">@lang('Priority Name') <span class="text-danger">*</span></label>
        <input type="text" name="name" id="task_priority_name" class="form-control" required>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3">
        <label for="task_priority_color" class="form-label">@lang('Display Color')</label>
        <input type="color" name="color" id="task_priority_color" class="form-control form-control-color" value="#6c757d">
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="task_priority_is_default" name="is_default" value="1">
        <label class="form-check-label" for="task_priority_is_default">@lang('Set as Default Priority')</label>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="saveTaskPriorityBtn" class="btn btn-primary flex-fill">{{ __('Save Priority') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
