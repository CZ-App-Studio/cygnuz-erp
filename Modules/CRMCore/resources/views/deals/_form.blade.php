{{-- This is a partial, included in deals/index.blade.php --}}
<div class="offcanvas offcanvas-end" style="width: 700px;" tabindex="-1" id="offcanvasDealForm" aria-labelledby="offcanvasDealFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasDealFormLabel">@lang('Add New Deal')</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="dealForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="deal_id">
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div class="row g-3">
        <div class="col-md-12 mb-3">
          <label for="deal_title" class="form-label">@lang('Deal Title') <span class="text-danger">*</span></label>
          <input type="text" name="title" id="deal_title" class="form-control" required>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="deal_pipeline_id" class="form-label">@lang('Pipeline') <span class="text-danger">*</span></label>
          <select name="pipeline_id" id="deal_pipeline_id" class="form-select select2-basic" required>
            <option value="">@lang('Select Pipeline')</option>
            {{-- Options populated by JS from pageData.allPipelinesForForm or directly if simple --}}
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="deal_stage_id" class="form-label">@lang('Stage') <span class="text-danger">*</span></label>
          <select name="deal_stage_id" id="deal_stage_id" class="form-select select2-basic" required data-placeholder="@lang('Select Stage...')">
            {{-- Options populated dynamically by JS based on selected pipeline --}}
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="deal_value" class="form-label">@lang('Value')</label>
          <div class="input-group">
            <span class="input-group-text">$</span> {{-- Adjust currency symbol as needed --}}
            <input type="number" name="value" id="deal_value" class="form-control" min="0" step="any">
          </div>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="deal_expected_close_date" class="form-label">@lang('Expected Close Date')</label>
          <input type="text" name="expected_close_date" id="deal_expected_close_date" class="form-control flatpickr-date">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="deal_company_id" class="form-label">@lang('Company')</label>
          <select name="company_id" id="deal_company_id" class="form-select select2-companies"
                  data-placeholder="@lang('Search & Select Company...')">
            {{-- Populated by Select2 AJAX --}}
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="deal_contact_id" class="form-label">@lang('Primary Contact')</label>
          <select name="contact_id" id="deal_contact_id" class="form-select select2-contacts"
                  data-placeholder="@lang('Search & Select Contact...')">
            {{-- Populated by Select2 AJAX, potentially filtered by selected company --}}
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="deal_assigned_to_user_id" class="form-label">@lang('Assigned To')</label>
          <select name="assigned_to_user_id" id="deal_assigned_to_user_id" class="form-select select2-users"
                  data-placeholder="@lang('Search & Select User...')">
            {{-- Populated by Select2 AJAX --}}
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="deal_description" class="form-label">@lang('Description')</label>
          <textarea name="description" id="deal_description" class="form-control" rows="3"></textarea>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="deal_probability" class="form-label">@lang('Probability (%)')</label>
          <input type="number" name="probability" id="deal_probability" class="form-control" min="0" max="100" step="1">
          <div class="invalid-feedback"></div>
        </div>
        {{-- Lost Reason field - shown by JS if stage is 'lost' --}}
        <div class="col-md-12 mb-3 d-none" id="lost_reason_container">
          <label for="deal_lost_reason" class="form-label">@lang('Lost Reason')</label>
          <textarea name="lost_reason" id="deal_lost_reason" class="form-control" rows="2"></textarea>
          <div class="invalid-feedback"></div>
        </div>

      </div>

      <div class="text-end mt-4">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="offcanvas">@lang('Cancel')</button>
        <button type="submit" id="saveDealBtn" class="btn btn-primary">@lang('Save Deal')</button>
      </div>
    </form>
  </div>
</div>
