<div class="offcanvas offcanvas-end" style="width: 500px;" tabindex="-1" id="offcanvasDealPipelineForm" aria-labelledby="offcanvasDealPipelineFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasDealPipelineFormLabel">@lang('Add Deal Pipeline')</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="dealPipelineForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="pipeline_id">
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div class="mb-3">
        <label for="pipeline_name" class="form-label">@lang('Pipeline Name') <span class="text-danger">*</span></label>
        <input type="text" name="name" id="pipeline_name" class="form-control" required>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3">
        <label for="pipeline_description" class="form-label">@lang('Description')</label>
        <textarea name="description" id="pipeline_description" class="form-control" rows="3"></textarea>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="pipeline_is_active" name="is_active" value="1" checked>
        <label class="form-check-label" for="pipeline_is_active">{{ __('Active') }}</label>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="pipeline_is_default" name="is_default" value="1">
        <label class="form-check-label" for="pipeline_is_default">@lang('Set as Default Pipeline')</label>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="savePipelineBtn" class="btn btn-primary flex-fill">{{ __('Save Pipeline') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
