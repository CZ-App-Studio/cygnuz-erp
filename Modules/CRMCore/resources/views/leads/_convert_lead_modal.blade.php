<div class="offcanvas offcanvas-end" style="width: 700px;" tabindex="-1" id="convertLeadOffcanvas" aria-labelledby="convertLeadOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="convertLeadOffcanvasLabel">{{ __('Convert Lead') }}: <span id="convertLeadOffcanvasTitleName" class="fw-bold"></span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="convertLeadForm" novalidate>
      @csrf
      <input type="hidden" name="lead_id" id="convert_lead_id">

      <h6><i class="bx bx-user-pin me-2"></i>{{ __('Contact Information') }}</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label for="convert_contact_first_name" class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
          <input type="text" name="contact_first_name" id="convert_contact_first_name" class="form-control" required>
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
          <label for="convert_contact_last_name" class="form-label">{{ __('Last Name') }}</label>
          <input type="text" name="contact_last_name" id="convert_contact_last_name" class="form-control">
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
          <label for="convert_contact_email" class="form-label">{{ __('Email') }}</label>
          <input type="email" name="contact_email" id="convert_contact_email" class="form-control">
          <div id="existing_contact_info" class="form-text text-warning" style="display: none;"></div>
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
          <label for="convert_contact_phone" class="form-label">{{ __('Phone') }}</label>
          <input type="tel" name="contact_phone" id="convert_contact_phone" class="form-control">
          <div class="invalid-feedback"></div>
        </div>
      </div>
      <hr class="my-3">

      <h6><i class="bx bx-buildings me-2"></i>{{ __('Company Information') }}</h6>
      <div class="mb-3">
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="company_option" id="company_option_none" value="none" checked>
          <label class="form-check-label" for="company_option_none">{{ __('Do not link to a company') }}</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="company_option" id="company_option_existing" value="existing">
          <label class="form-check-label" for="company_option_existing">{{ __('Link to Existing Company') }}</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="company_option" id="company_option_new" value="new">
          <label class="form-check-label" for="company_option_new">{{ __('Create New Company') }}</label>
        </div>
      </div>
      <div id="existing_company_section" class="mb-3" style="display: none;">
        <label for="existing_company_id" class="form-label">{{ __('Select Existing Company') }}</label>
        <select name="existing_company_id" id="existing_company_id" class="form-select select2-companies-convert"
                data-placeholder="{{ __('Search & Select Company...') }}">
        </select>
        <div class="invalid-feedback"></div>
      </div>
      <div id="new_company_section" class="mb-3" style="display: none;">
        <label for="new_company_name" class="form-label">{{ __('New Company Name') }} <span id="new_company_name_required_star" class="text-danger" style="display:none;">*</span></label>
        <input type="text" name="new_company_name" id="new_company_name" class="form-control">
        <div class="invalid-feedback"></div>
      </div>
      <hr class="my-3">

      <h6><i class="bx bx-dollar-circle me-2"></i>{{ __('Opportunity/Deal Information') }}</h6>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="create_deal" name="create_deal" value="1" checked>
        <label class="form-check-label" for="create_deal">{{ __('Create a new Deal upon conversion') }}</label>
      </div>

      <div id="deal_fields_section" class="row g-3">
        <div class="col-md-12 mb-3">
          <label for="convert_deal_title" class="form-label">{{ __('Deal Title') }} <span class="text-danger">*</span></label>
          <input type="text" name="deal_title" id="convert_deal_title" class="form-control">
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="convert_deal_value" class="form-label">{{ __('Deal Value') }}</label>
          <input type="number" name="deal_value" id="convert_deal_value" class="form-control" min="0" step="any">
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="convert_deal_expected_close_date" class="form-label">{{ __('Expected Close Date') }}</label>
          <input type="text" name="deal_expected_close_date" id="convert_deal_expected_close_date" class="form-control flatpickr-date">
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="convert_deal_pipeline_id" class="form-label">{{ __('Pipeline') }} <span class="text-danger">*</span></label>
          <select name="deal_pipeline_id" id="convert_deal_pipeline_id" class="form-select select2-basic-convert">
          </select>
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="convert_deal_stage_id" class="form-label">{{ __('Stage') }} <span class="text-danger">*</span></label>
          <select name="deal_stage_id" id="convert_deal_stage_id" class="form-select select2-basic-convert" data-placeholder="{{ __('Select Stage...') }}">
          </select>
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="submitConvertLeadBtn" class="btn btn-success flex-fill">{{ __('Convert Lead') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>