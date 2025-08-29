/* Attendance Index */

'use strict';

$(function () {
  console.log('Attendance Index');

  var dataTable = $('#attendanceTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: 'attendance/indexAjax',
      data: function data(d) {
        d.userId = $('#userId').val();
        d.date = $('#date').val();
      }
    },
    columns: [
      {data: 'id', name: 'id'},
      {data: 'user', name: 'user'},
      {data: 'shift', name: 'shift'},
      {data: 'check_in_time', name: 'check_in_time'},
      {data: 'check_out_time', name: 'check_out_time'}
    ]
  });

  $('#userId').select2();

  $('#userId').on('change', function () {
    dataTable.draw();
  });

  $('#date').on('change', function () {
    dataTable.draw();
  });
});
