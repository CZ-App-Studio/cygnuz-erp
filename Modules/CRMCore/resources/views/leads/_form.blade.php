<div class="offcanvas offcanvas-end" style="width: 600px;" tabindex="-1" id="offcanvasLeadForm" aria-labelledby="offcanvasLeadFormLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasLeadFormLabel">{{ __('Add New Lead') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="leadForm" novalidate>
      @csrf
      <input type="hidden" name="id" id="lead_id" value="">
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div class="row g-3">
        <div class="col-md-12 mb-3">
          <label for="title" class="form-label">{{ __('Lead Title') }} <span class="text-danger">*</span></label>
          <input type="text" name="title" id="title" class="form-control" required>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="contact_name" class="form-label">{{ __('Contact Name') }}</label>
          <input type="text" name="contact_name" id="contact_name" class="form-control">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="company_name" class="form-label">{{ __('Company Name') }}</label>
          <input type="text" name="company_name" id="company_name" class="form-control">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="contact_email" class="form-label">{{ __('Contact Email') }}</label>
          <input type="email" name="contact_email" id="contact_email" class="form-control">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="contact_phone" class="form-label">{{ __('Contact Phone') }}</label>
          <input type="tel" name="contact_phone" id="contact_phone" class="form-control">
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="value" class="form-label">{{ __('Estimated Value') }}</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="value" id="value" class="form-control" min="0" step="any">
          </div>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="lead_source_id" class="form-label">{{ __('Lead Source') }}</label>
          <select name="lead_source_id" id="lead_source_id" class="form-select">
            <option value="">{{ __('Select Source') }}</option>
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="assigned_to_user_id" class="form-label">{{ __('Assigned To') }}</label>
          <select name="assigned_to_user_id" id="assigned_to_user_id" class="form-select select2-users"
                  data-placeholder="{{ __('Search & Select User...') }}">
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-12 mb-3">
          <label for="description" class="form-label">{{ __('Description') }}</label>
          <textarea name="description" id="description" class="form-control" rows="4"></textarea>
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" id="saveLeadBtn" class="btn btn-primary flex-fill">{{ __('Save Lead') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
