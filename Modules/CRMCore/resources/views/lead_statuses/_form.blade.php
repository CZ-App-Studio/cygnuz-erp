<div class="offcanvas offcanvas-end" style="width: 450px;" tabindex="-1" id="offcanvasLeadStatusForm" aria-labelledby="offcanvasLeadStatusFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasLeadStatusFormLabel">{{ __('Add Lead Status') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="leadStatusForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="status_id">
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div class="mb-3">
        <label for="status_name" class="form-label">{{ __('Status Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" id="status_name" class="form-control" required>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3">
        <label for="status_color" class="form-label">{{ __('Display Color') }}</label>
        <input type="color" name="color" id="status_color" class="form-control form-control-color">
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="status_is_default" name="is_default" value="1">
        <label class="form-check-label" for="status_is_default">{{ __('Set as Default Status') }}</label>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="status_is_final" name="is_final" value="1">
        <label class="form-check-label" for="status_is_final">{{ __('Is Final Stage (e.g., Converted, Lost)') }}</label>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="saveStatusBtn" class="btn btn-primary flex-fill">{{ __('Save Status') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
