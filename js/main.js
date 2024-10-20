$(document).ready(function() {
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Report saved successfully!');
                    $('#view-tab').tab('show');
                    loadReports();
                } else {
                    alert('Error saving report. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });

    function loadReports() {
        $.ajax({
            url: 'get_reports.php',
            method: 'GET',
            dataType: 'html',
            success: function(response) {
                $('#view').html(response);
            },
            error: function() {
                alert('Error loading reports. Please try again.');
            }
        });
    }

    $(document).on('click', '.preview-report', function() {
        var reportId = $(this).data('id');
        $.ajax({
            url: 'preview_report.php',
            method: 'GET',
            data: { id: reportId },
            dataType: 'html',
            success: function(response) {
                $('#previewModal .modal-body').html(response);
                $('#previewModal').modal('show');
            },
            error: function() {
                alert('Error loading report preview. Please try again.');
            }
        });
    });

    $(document).on('click', '.complete-report', function() {
        var reportId = $(this).data('id');
        if (confirm('Are you sure you want to mark this report as completed? This action cannot be undone.')) {
            $.ajax({
                url: 'complete_report.php',
                method: 'POST',
                data: { id: reportId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Report marked as completed successfully!');
                        loadReports();
                    } else {
                        alert('Error completing report. Please try again.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    $('#view-tab').on('shown.bs.tab', function (e) {
        loadReports();
    });
});