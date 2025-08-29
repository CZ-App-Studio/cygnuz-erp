<div class="offcanvas offcanvas-end" style="width: 400px;" tabindex="-1" id="offcanvasLeadSourceForm" aria-labelledby="offcanvasLeadSourceFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasLeadSourceFormLabel">{{ __('Add Lead Source') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="leadSourceForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="source_id">
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div class="mb-3">
        <label for="source_name" class="form-label">{{ __('Source Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" id="source_name" class="form-control" required>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="source_is_active" name="is_active" value="1" checked>
        <label class="form-check-label" for="source_is_active">{{ __('Is Active') }}</label>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="saveSourceBtn" class="btn btn-primary flex-fill">{{ __('Save') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
