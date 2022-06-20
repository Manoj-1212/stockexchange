// Initiate datatables in roles, tables, users page
(function() {
    'use strict';
    
    $('#dataTables-example').DataTable({
        responsive: true,
        pageLength: 20,
        lengthChange: false,
        searching: true,
        ordering: false
    });

    $('#dataTables-market').DataTable({
        responsive: true,
        scrollY: '400px',
        scrollCollapse: true,
        paging: false,
        ordering: false
    });
})();