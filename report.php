<?php
// Add this to the existing PHP section at the top of the file
include('report_functions.php'); // We'll create this file to handle report generation logic
?>

<!-- Add this to the HTML, after the existing tabs -->
<button class="tab" data-tab="generate-report">Generate Report</button>

<!-- Add this new tab content after the existing tab contents -->
<div id="generate-report" class="tab-content">
    <h2>Generate Account Report</h2>
    <form id="generateReportForm">
        <div class="form-group">
            <label for="reportType">Report Type</label>
            <select class="form-control" id="reportType" name="reportType" required>
                <option value="account_summary">Account Summary</option>
                <option value="transaction_history">Transaction History</option>
                <option value="balance_trends">Balance Trends</option>
            </select>
        </div>
        <div class="form-group">
            <label for="dateRange">Date Range</label>
            <select class="form-control" id="dateRange" name="dateRange" required>
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="365">Last 365 days</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>
        <div id="customDateRange" style="display: none;">
            <div class="form-group">
                <label for="startDate">Start Date</label>
                <input type="date" class="form-control" id="startDate" name="startDate">
            </div>
            <div class="form-group">
                <label for="endDate">End Date</label>
                <input type="date" class="form-control" id="endDate" name="endDate">
            </div>
        </div>
        <div class="form-group">
            <label for="exportFormat">Export Format</label>
            <select class="form-control" id="exportFormat" name="exportFormat" required>
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
                <option value="csv">CSV</option>
            </select>
        </div>
        <button type="submit" class="btn">Generate Report</button>
    </form>
</div>

<!-- Add this to your existing JavaScript section -->
<script>
$(document).ready(function() {
    // ... (existing code)

    $('#generateReportForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });

    $('#dateRange').change(function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
        }
    });
});

function generateReport() {
    const reportType = $('#reportType').val();
    const dateRange = $('#dateRange').val();
    const exportFormat = $('#exportFormat').val();
    let startDate = '';
    let endDate = '';

    if (dateRange === 'custom') {
        startDate = $('#startDate').val();
        endDate = $('#endDate').val();
    }

    $.ajax({
        url: 'generate_report.php',
        method: 'POST',
        data: {
            reportType: reportType,
            dateRange: dateRange,
            startDate: startDate,
            endDate: endDate,
            exportFormat: exportFormat
        },
        xhrFields: {
            responseType: 'blob' // to handle file download
        },
        success: function(blob, status, xhr) {
            // Check for a filename
            const filename = "";
            const disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('attachment') !== -1) {
                const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = filenameRegex.exec(disposition);
                if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
            }

            // Create a link element, hide it, direct it towards the blob, and then click it programatically
            const a = document.createElement('a');
            a.style = "display: none";
            document.body.appendChild(a);
            const url = window.URL.createObjectURL(blob);
            a.href = url;
            a.download = filename || `accounts_report.${exportFormat}`;
            a.click();
            window.URL.revokeObjectURL(url);

            Swal.fire('Success', 'Report generated successfully!', 'success');
        },
        error: function() {
            Swal.fire('Error', 'Failed to generate report', 'error');
        }
    });
}
</script>