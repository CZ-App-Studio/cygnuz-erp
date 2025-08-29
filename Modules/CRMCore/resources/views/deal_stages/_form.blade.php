<div class="offcanvas offcanvas-end" style="width: 450px;" tabindex="-1" id="offcanvasDealStageForm" aria-labelledby="offcanvasDealStageFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasDealStageFormLabel">@lang('Add New Stage')</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="dealStageForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="stage_id">
      <input type="hidden" name="_method" id="formMethod" value="POST">
      {{-- pipeline_id is implicit from the route and handled by controller --}}

      <div class="mb-3">
        <label for="stage_name" class="form-label">@lang('Stage Name') <span class="text-danger">*</span></label>
        <input type="text" name="name" id="stage_name" class="form-control" required>
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3">
        <label for="stage_color" class="form-label">@lang('Display Color')</label>
        <input type="color" name="color" id="stage_color" class="form-control form-control-color" value="#6c757d">
        <div class="invalid-feedback"></div>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="stage_is_default_for_pipeline" name="is_default_for_pipeline" value="1">
        <label class="form-check-label" for="stage_is_default_for_pipeline">@lang('Default Stage for this Pipeline')</label>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="stage_is_won_stage" name="is_won_stage" value="1">
        <label class="form-check-label" for="stage_is_won_stage">@lang('Is "Won" Stage')</label>
      </div>

      <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="stage_is_lost_stage" name="is_lost_stage" value="1">
        <label class="form-check-label" for="stage_is_lost_stage">@lang('Is "Lost" Stage')</label>
      </div>

      <div class="text-end mt-4">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="offcanvas">@lang('Cancel')</button>
        <button type="submit" id="saveStageBtn" class="btn btn-primary">@lang('Save Stage')</button>
      </div>
    </form>
  </div>
</div>
