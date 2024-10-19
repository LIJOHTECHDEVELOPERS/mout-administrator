<?php
// generate_report.php

include('db.php');
include('report_functions.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['reportType'];
    $dateRange = $_POST['dateRange'];
    $exportFormat = $_POST['exportFormat'];

    // Calculate start and end dates
    $endDate = date('Y-m-d');
    if ($dateRange === 'custom') {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
    } else {
        $startDate = date('Y-m-d', strtotime("-$dateRange days"));
    }

    // Fetch data based on report type
    switch ($reportType) {
        case 'account_summary':
            $data = getAccountSummary($startDate, $endDate);
            break;
        case 'transaction_history':
            $data = getTransactionHistory($startDate, $endDate);
            break;
        case 'balance_trends':
            $data = getBalanceTrends($startDate, $endDate);
            break;
        default:
            die('Invalid report type');
    }

    // Generate report based on export format
    switch ($exportFormat) {
        case 'pdf':
            $content = generatePDF($data, $reportType);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="account_report.pdf"');
            break;
        case 'excel':
            $content = generateExcel($data, $reportType);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="account_report.xlsx"');
            break;
        case 'csv':
            $content = generateCSV($data, $reportType);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="account_report.csv"');
            break;
        default:
            die('Invalid export format');
    }

    echo $content;
    exit;
}
?>