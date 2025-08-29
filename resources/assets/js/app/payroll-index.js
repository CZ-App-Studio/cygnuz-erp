$(function () {
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Select an option',
        allowClear: true
    });

    // Initialize DataTable
    var dtTable = $('.datatables-payroll');

    if (dtTable.length) {
        var dtPayroll = dtTable.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/payroll/getListAjax',
                data: function (d) {
                    d.dateFilter = $('#dateFilter').val();
                    d.employeeFilter = $('#employeeFilter').val();
                    d.statusFilter = $('#statusFilter').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'user', name: 'user' },
                { data: 'period', name: 'period' },
                { data: 'basic_salary', name: 'basic_salary' },
                { data: 'gross_salary', name: 'gross_salary' },
                { data: 'net_salary', name: 'net_salary' },
                { data: 'status', name: 'status' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[2, 'desc']], // Order by period
            dom: '<"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                search: '',
                searchPlaceholder: 'Search payroll records...'
            },
            drawCallback: function() {
                // Initialize tooltips if any
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Apply filters
        $('.filter-input').on('change', function () {
            dtPayroll.draw();
        });
    }
});

// Global functions for actions (if any are needed)
window.viewPayrollDetails = function(id) {
    window.location.href = '/payroll/show/' + id;
}

window.generatePayslip = function(id) {
    window.location.href = '/payroll/payslip/' + id;
}

window.downloadPayslipPDF = function(id) {
    window.location.href = '/payroll/' + id + '/pdf';
}