@props([
    'name' => 'entity_selector',
    'id' => 'entitySelector',
    'required' => false,
    'value' => null,
    'companyId' => null,
    'contactId' => null,
    'customerId' => null,
    'showWalkIn' => true,
    'label' => __('Select Customer'),
])

<div class="entity-selector-container">
    <label class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <!-- Entity Type Selector -->
    <div class="btn-group w-100 mb-2" role="group">
        <input type="radio" class="btn-check" name="{{ $name }}_type" id="{{ $id }}_company" value="company" autocomplete="off">
        <label class="btn btn-outline-primary" for="{{ $id }}_company">
            <i class="bx bx-building"></i> {{ __('Company') }}
        </label>

        <input type="radio" class="btn-check" name="{{ $name }}_type" id="{{ $id }}_contact" value="contact" autocomplete="off">
        <label class="btn btn-outline-primary" for="{{ $id }}_contact">
            <i class="bx bx-user"></i> {{ __('Contact') }}
        </label>

        <input type="radio" class="btn-check" name="{{ $name }}_type" id="{{ $id }}_customer" value="customer" autocomplete="off">
        <label class="btn btn-outline-primary" for="{{ $id }}_customer">
            <i class="bx bx-id-card"></i> {{ __('Customer') }}
        </label>

        @if($showWalkIn)
            <input type="radio" class="btn-check" name="{{ $name }}_type" id="{{ $id }}_walkin" value="walkin" autocomplete="off" checked>
            <label class="btn btn-outline-primary" for="{{ $id }}_walkin">
                <i class="bx bx-walk"></i> {{ __('Walk-in') }}
            </label>
        @endif
    </div>

    <!-- Entity Search/Select -->
    <div id="{{ $id }}_company_container" class="entity-search-container d-none">
        <select id="{{ $id }}_company_select" name="company_id" class="form-select entity-select" data-placeholder="{{ __('Search and select company...') }}">
            <option value=""></option>
            @if($companyId)
                <option value="{{ $companyId }}" selected>{{ __('Loading...') }}</option>
            @endif
        </select>
    </div>

    <div id="{{ $id }}_contact_container" class="entity-search-container d-none">
        <select id="{{ $id }}_contact_select" name="contact_id" class="form-select entity-select" data-placeholder="{{ __('Search and select contact...') }}">
            <option value=""></option>
            @if($contactId)
                <option value="{{ $contactId }}" selected>{{ __('Loading...') }}</option>
            @endif
        </select>
    </div>

    <div id="{{ $id }}_customer_container" class="entity-search-container d-none">
        <select id="{{ $id }}_customer_select" name="customer_id" class="form-select entity-select" data-placeholder="{{ __('Search and select customer...') }}">
            <option value=""></option>
            @if($customerId)
                <option value="{{ $customerId }}" selected>{{ __('Loading...') }}</option>
            @endif
        </select>
    </div>

    <!-- Walk-in Customer Form -->
    <div id="{{ $id }}_walkin_container" class="walkin-form-container">
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" class="form-control" name="customer_name" 
                       placeholder="{{ __('Customer Name') }}" {{ $required ? 'required' : '' }}>
            </div>
            <div class="col-md-6">
                <input type="tel" class="form-control" name="customer_phone" 
                       placeholder="{{ __('Phone Number') }}">
            </div>
            <div class="col-md-12">
                <input type="email" class="form-control" name="customer_email" 
                       placeholder="{{ __('Email Address (Optional)') }}">
            </div>
        </div>
    </div>

    <!-- Selected Entity Display -->
    <div id="{{ $id }}_selected_display" class="selected-entity-display mt-2 d-none">
        <div class="alert alert-info d-flex align-items-center">
            <i class="bx bx-info-circle me-2"></i>
            <div>
                <strong>{{ __('Selected:') }}</strong>
                <span id="{{ $id }}_selected_text"></span>
            </div>
        </div>
    </div>
</div>

@push('page-script')
<script>
(function() {
    const selectorId = '{{ $id }}';
    const containers = {
        company: document.getElementById(`${selectorId}_company_container`),
        contact: document.getElementById(`${selectorId}_contact_container`),
        customer: document.getElementById(`${selectorId}_customer_container`),
        walkin: document.getElementById(`${selectorId}_walkin_container`)
    };
    
    const typeRadios = document.querySelectorAll(`input[name="{{ $name }}_type"]`);
    const selectedDisplay = document.getElementById(`${selectorId}_selected_display`);
    const selectedText = document.getElementById(`${selectorId}_selected_text`);
    
    // Initialize Select2 for entity selects
    function initializeEntitySelects() {
        // Company Select
        $(`#${selectorId}_company_select`).select2({
            dropdownParent: $(`#${selectorId}_company_container`),
            ajax: {
                url: '/api/fieldproductorder/v1/orders/search-crm',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        type: 'company',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.companies ? data.data.companies.map(function(company) {
                            return {
                                id: company.id,
                                text: `${company.name} (${company.code || 'No Code'})`,
                                company: company
                            };
                        }) : [],
                        pagination: {
                            more: data.data.has_more || false
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
        
        // Contact Select
        $(`#${selectorId}_contact_select`).select2({
            dropdownParent: $(`#${selectorId}_contact_container`),
            ajax: {
                url: '/api/fieldproductorder/v1/orders/search-crm',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        type: 'contact',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.contacts ? data.data.contacts.map(function(contact) {
                            const name = `${contact.first_name || ''} ${contact.last_name || ''}`.trim();
                            const company = contact.company ? ` - ${contact.company.name}` : '';
                            return {
                                id: contact.id,
                                text: `${name}${company} (${contact.email_primary || contact.phone_primary || 'No Contact'})`,
                                contact: contact
                            };
                        }) : [],
                        pagination: {
                            more: data.data.has_more || false
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
        
        // Customer Select
        $(`#${selectorId}_customer_select`).select2({
            dropdownParent: $(`#${selectorId}_customer_container`),
            ajax: {
                url: '/api/fieldproductorder/v1/orders/search-crm',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        type: 'customer',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.customers ? data.data.customers.map(function(customer) {
                            const contactName = customer.contact ? 
                                `${customer.contact.first_name || ''} ${customer.contact.last_name || ''}`.trim() : 
                                'No Contact';
                            return {
                                id: customer.id,
                                text: `${customer.code} - ${contactName}`,
                                customer: customer
                            };
                        }) : [],
                        pagination: {
                            more: data.data.has_more || false
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
    }
    
    // Handle entity type change
    function handleTypeChange(event) {
        const selectedType = event.target.value;
        
        // Hide all containers
        Object.keys(containers).forEach(key => {
            if (containers[key]) {
                containers[key].classList.add('d-none');
            }
        });
        
        // Clear non-walkin fields when switching types
        if (selectedType !== 'company') $(`#${selectorId}_company_select`).val(null).trigger('change');
        if (selectedType !== 'contact') $(`#${selectorId}_contact_select`).val(null).trigger('change');
        if (selectedType !== 'customer') $(`#${selectorId}_customer_select`).val(null).trigger('change');
        
        // Show selected container
        if (containers[selectedType]) {
            containers[selectedType].classList.remove('d-none');
        }
        
        // Update required fields
        updateRequiredFields(selectedType);
        
        // Clear or show selected display
        if (selectedType === 'walkin') {
            selectedDisplay.classList.add('d-none');
        }
    }
    
    // Update required field attributes based on entity type
    function updateRequiredFields(entityType) {
        const nameField = document.querySelector(`input[name="customer_name"]`);
        
        if (entityType === 'walkin') {
            if (nameField && {{ $required ? 'true' : 'false' }}) {
                nameField.setAttribute('required', 'required');
            }
        } else {
            if (nameField) {
                nameField.removeAttribute('required');
            }
        }
    }
    
    // Handle entity selection
    function handleEntitySelection(entityType, data) {
        let displayText = '';
        
        switch(entityType) {
            case 'company':
                if (data && data.company) {
                    displayText = `Company: ${data.company.name}`;
                }
                break;
            case 'contact':
                if (data && data.contact) {
                    const name = `${data.contact.first_name || ''} ${data.contact.last_name || ''}`.trim();
                    displayText = `Contact: ${name}`;
                }
                break;
            case 'customer':
                if (data && data.customer) {
                    displayText = `Customer: ${data.customer.code}`;
                }
                break;
        }
        
        if (displayText) {
            selectedText.textContent = displayText;
            selectedDisplay.classList.remove('d-none');
        } else {
            selectedDisplay.classList.add('d-none');
        }
    }
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeEntitySelects();
        
        // Add event listeners for type changes
        typeRadios.forEach(radio => {
            radio.addEventListener('change', handleTypeChange);
        });
        
        // Add event listeners for entity selection
        $(`#${selectorId}_company_select`).on('select2:select', function(e) {
            handleEntitySelection('company', e.params.data);
        });
        
        $(`#${selectorId}_contact_select`).on('select2:select', function(e) {
            handleEntitySelection('contact', e.params.data);
        });
        
        $(`#${selectorId}_customer_select`).on('select2:select', function(e) {
            handleEntitySelection('customer', e.params.data);
        });
        
        // Set initial state
        @if($companyId)
            document.getElementById(`${selectorId}_company`).checked = true;
            handleTypeChange({ target: { value: 'company' } });
        @elseif($contactId)
            document.getElementById(`${selectorId}_contact`).checked = true;
            handleTypeChange({ target: { value: 'contact' } });
        @elseif($customerId)
            document.getElementById(`${selectorId}_customer`).checked = true;
            handleTypeChange({ target: { value: 'customer' } });
        @elseif($showWalkIn)
            document.getElementById(`${selectorId}_walkin`).checked = true;
            handleTypeChange({ target: { value: 'walkin' } });
        @endif
    });
})();
</script>
@endpush

@push('page-style')
<style>
.entity-selector-container .btn-group {
    display: flex;
}
.entity-selector-container .btn-group .btn {
    flex: 1;
}
.selected-entity-display .alert {
    margin-bottom: 0;
}
.walkin-form-container {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
}
</style>
@endpush