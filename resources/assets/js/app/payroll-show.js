window.printPayroll = function() {
    window.print();
};

window.sendPayroll = function() {
    Swal.fire({
        title: pageData.labels.sendPayrollDetails,
        text: pageData.labels.confirmSendPayroll,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.yesSendIt,
        cancelButtonText: pageData.labels.cancel,
        customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-label-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: pageData.labels.sent,
                text: pageData.labels.payrollSentSuccess,
                icon: 'success',
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
        }
    });
};