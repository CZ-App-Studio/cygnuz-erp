<div class="offcanvas offcanvas-end" style="width: 600px;" tabindex="-1" id="offcanvasTaskForm" aria-labelledby="offcanvasTaskFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasTaskFormLabel">{{ __('Add New Task') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="taskForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="task_id">
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div class="row g-3">
        <div class="col-md-12 mb-3">
          <label for="task_title" class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input type="text" name="title" id="task_title" class="form-control" required>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="task_description" class="form-label">{{ __('Description') }}</label>
          <textarea name="description" id="task_description" class="form-control" rows="3"></textarea>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="task_due_date" class="form-label">{{ __('Due Date') }}</label>
          <input type="text" name="due_date" id="task_due_date" class="form-control flatpickr-datetime" placeholder="YYYY-MM-DD HH:MM">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="task_reminder_at" class="form-label">{{ __('Set Reminder At (Optional)') }}</label>
          <input type="text" name="reminder_at" id="task_reminder_at" class="form-control flatpickr-datetime" placeholder="YYYY-MM-DD HH:MM">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="task_status_id" class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
          <select name="task_status_id" id="task_status_id" class="form-select select2-basic" required>
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="task_priority_id" class="form-label">{{ __('Priority') }}</label>
          <select name="task_priority_id" id="task_priority_id" class="form-select select2-basic">
            <option value="">{{ __('Select Priority') }}</option>
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="task_assigned_to_user_id" class="form-label">{{ __('Assigned To') }}</label>
          <select name="assigned_to_user_id" id="task_assigned_to_user_id" class="form-select select2-users"
                  data-placeholder="{{ __('Search & Select User...') }}">
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-5 mb-3">
          <label for="taskable_type_selector" class="form-label">{{ __('Related To (Type)') }}</label>
          <select name="taskable_type_selector" id="taskable_type_selector" class="form-select select2-basic">
            <option value="">{{ __('None') }}</option>
            <option value="Contact">{{ __('Contact') }}</option>
            <option value="Company">{{ __('Company') }}</option>
            <option value="Lead">{{ __('Lead') }}</option>
            <option value="Deal">{{ __('Deal') }}</option>
          </select>
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-7 mb-3">
          <label for="taskable_id_selector" class="form-label">{{ __('Related Record') }}</label>
          <select name="taskable_id_selector" id="taskable_id_selector" class="form-select select2-taskable"
                  data-placeholder="{{ __('Select Type First...') }}">
          </select>
          <div class="invalid-feedback"></div>
        </div>

      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="saveTaskBtn" class="btn btn-primary flex-fill">{{ __('Save Task') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
