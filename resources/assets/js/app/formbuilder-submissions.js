$(function () {
  'use strict';

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  const dt_submissions = $('.datatables-submissions');
  let submissionTable;
  let currentSubmissionId = null;

  if (dt_submissions.length) {
    submissionTable = dt_submissions.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.submissionsData,
        type: 'GET'
      },
      columns: [
        { data: 'id', name: 'id' },
        {
          data: 'created_at',
          name: 'created_at',
          render: function (data, type, row) {
            if (type === 'display' || type === 'type') {
              if (typeof moment !== 'undefined' && moment.isDate(new Date(data))) {
                return moment(data).format('MMM DD, YYYY HH:mm');
              } else {
                // Fallback if moment is not available
                const date = new Date(data);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
              }
            }
            return data;
          }
        },
        { data: 'user', name: 'user', orderable: false },
        { data: 'ip_address', name: 'ip_address' },
        {
          data: 'data_preview',
          name: 'data_preview',
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            return '<span class="text-truncate d-inline-block" style="max-width: 200px;">' + data + '</span>';
          }
        },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      responsive: true,
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        },
        emptyTable: pageData.labels.noData,
        loadingRecords: pageData.labels.loading
      }
    });
  }

  // Global functions for DataTable actions
  window.viewSubmission = function(submissionId) {
    currentSubmissionId = submissionId;

    const viewUrl = pageData.urls.submissionView.replace('__SUBMISSION_ID__', submissionId);

    $('#submissionContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    $('#submissionModal').modal('show');

    $.get(viewUrl)
      .done(function(response) {
        if (response.status == 'success') {
          $('#submissionContent').html(formatSubmissionData(response.data));
        } else {
          $('#submissionContent').html('<div class="alert alert-danger">' + (response.data || response.message || pageData.labels.error) + '</div>');
        }
      })
      .fail(function() {
        $('#submissionContent').html('<div class="alert alert-danger">' + pageData.labels.error + '</div>');
      });
  };

  window.deleteSubmission = function(submissionId) {
    Swal.fire({
      title: pageData.labels.confirmDelete,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.delete,
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        performDeleteSubmission(submissionId);
      }
    });
  };

  // Delete submission from modal
  $('#deleteSubmissionBtn').on('click', function() {
    if (currentSubmissionId) {
      Swal.fire({
        title: pageData.labels.confirmDelete,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.delete,
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          performDeleteSubmission(currentSubmissionId);
          $('#submissionModal').modal('hide');
        }
      });
    }
  });

  // Export submissions
  $('#exportBtn').on('click', function() {
    window.location.href = pageData.urls.submissionsExport;
  });

  // Delete all submissions
  $('#deleteAllBtn').on('click', function() {
    Swal.fire({
      title: pageData.labels.confirmDeleteAll,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.delete,
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(pageData.urls.submissionsDeleteAll)
          .done(function(response) {
            if (response.status == 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.success,
                text: response.data.message || pageData.labels.allSubmissionsDeleted,
                timer: 2000,
                showConfirmButton: false
              });
              submissionTable.ajax.reload();
              location.reload(); // Reload to update stats
            } else {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: response.data || response.message || pageData.labels.error
              });
            }
          })
          .fail(function() {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: pageData.labels.error
            });
          });
      }
    });
  });

  function performDeleteSubmission(submissionId) {
    const deleteUrl = pageData.urls.submissionDelete.replace('__SUBMISSION_ID__', submissionId);

    $.ajax({
      url: deleteUrl,
      type: 'DELETE',
      success: function(response) {
        if (response.status == 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data.message || pageData.labels.submissionDeleted,
            timer: 2000,
            showConfirmButton: false
          });
          submissionTable.ajax.reload();
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.data || response.message || pageData.labels.error
          });
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.error
        });
      }
    });
  }

  function formatSubmissionData(submission) {
    let html = '<div class="submission-details">';

    // Submission info
    html += '<div class="row mb-3">';
    html += '<div class="col-md-6"><strong>ID:</strong> ' + submission.id + '</div>';
    html += '<div class="col-md-6"><strong>Date:</strong> ' + moment(submission.created_at).format('MMM DD, YYYY HH:mm') + '</div>';
    html += '</div>';

    html += '<div class="row mb-3">';
    html += '<div class="col-md-6"><strong>User:</strong> ' + (submission.user || pageData.labels.anonymous) + '</div>';
    html += '<div class="col-md-6"><strong>IP Address:</strong> ' + submission.ip_address + '</div>';
    html += '</div>';

    if (submission.user_agent) {
      html += '<div class="row mb-3">';
      html += '<div class="col-12"><strong>User Agent:</strong><br><small class="text-muted">' + submission.user_agent + '</small></div>';
      html += '</div>';
    }

    html += '<hr>';

    // Form data
    html += '<h6>Form Data:</h6>';

    if (submission.data && Object.keys(submission.data).length > 0) {
      html += '<div class="table-responsive">';
      html += '<table class="table table-sm table-striped">';

      for (const [key, value] of Object.entries(submission.data)) {
        html += '<tr>';
        html += '<td><strong>' + key.replace('_', ' ').toUpperCase() + '</strong></td>';
        html += '<td>' + formatFieldValue(value) + '</td>';
        html += '</tr>';
      }

      html += '</table>';
      html += '</div>';
    } else {
      html += '<p class="text-muted">No data available</p>';
    }

    html += '</div>';
    return html;
  }

  function formatFieldValue(value) {
    if (value === null || value === undefined) {
      return '<em class="text-muted">No value</em>';
    }

    if (typeof value === 'object') {
      if (Array.isArray(value)) {
        return value.join(', ');
      } else if (value.original_name) {
        // File upload
        return '<a href="/storage/' + value.path + '" target="_blank">' + value.original_name + '</a>';
      } else {
        return JSON.stringify(value);
      }
    }

    return String(value);
  }
});
