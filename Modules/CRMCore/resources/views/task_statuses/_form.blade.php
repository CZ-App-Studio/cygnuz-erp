<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTaskStatusForm" aria-labelledby="offcanvasTaskStatusFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasTaskStatusFormLabel">{{ __('Add Task Status') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="taskStatusForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="task_status_id_input"> {{-- Renamed ID to avoid conflict --}}
      <input type="hidden" name="_method" id="taskStatusFormMethod" value="POST">

      <div class="mb-3">
        <label for="task_status_name" class="form-label">{{ __('Status Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" id="task_status_name" class="form-control" required>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3">
        <label for="task_status_color" class="form-label">{{ __('Display Color') }}</label>
        <input type="color" name="color" id="task_status_color" class="form-control form-control-color" value="#6c757d">
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="task_status_is_default" name="is_default" value="1">
          <label class="form-check-label" for="task_status_is_default">{{ __('Set as Default Status') }}</label>
        </div>
      </div>

      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="task_status_is_completed_status" name="is_completed_status" value="1">
          <label class="form-check-label" for="task_status_is_completed_status">{{ __('Is a "Completed" Status') }}</label>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" id="saveTaskStatusBtn" class="btn btn-primary flex-fill">{{ __('Save Status') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
