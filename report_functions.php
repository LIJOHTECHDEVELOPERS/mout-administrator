<?php
// report_functions.php

require('fpdf/fpdf.php');


// Database connection
$conn = new mysqli('localhost', 'root','', 'leadersportal');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get account summary with updated logic
function getAccountSummary($startDate, $endDate) {
    global $conn;
    $query = "SELECT id, name, balance, created_at FROM accounts WHERE created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate the total payments for the account
        $accountId = $row['id'];
        $paymentQuery = "SELECT SUM(amount) as total_payments FROM payments WHERE LOWER(account_reference) LIKE LOWER(?)";
        $stmtPayments = $conn->prepare($paymentQuery);
        $stmtPayments->bind_param("s", $row['name']);
        $stmtPayments->execute();
        $paymentResult = $stmtPayments->get_result();
        $totalPayments = $paymentResult->fetch_assoc()['total_payments'] ?? 0;

        // Calculate the total expenses for the account
        $expenseQuery = "SELECT SUM(amount) as total_expenses FROM expenses WHERE LOWER(account_name) LIKE LOWER(?)";
        $stmtExpenses = $conn->prepare($expenseQuery);
        $stmtExpenses->bind_param("s", $row['name']);
        $stmtExpenses->execute();
        $expenseResult = $stmtExpenses->get_result();
        $totalExpenses = $expenseResult->fetch_assoc()['total_expenses'] ?? 0;

        // Calculate the final balance
        $finalBalance = $row['balance'] + $totalPayments - $totalExpenses;

        // Add account data to array
        $accounts[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'balance' => $row['balance'],
            'total_payments' => $totalPayments,
            'total_expenses' => $totalExpenses,
            'balance' => $finalBalance,
            'created_at' => $row['created_at']
        ];
    }

    return $accounts;
}

// Function to get transaction history with updated logic
function getTransactionHistory($startDate, $endDate) {
    global $conn;
    $query = "SELECT * FROM payments WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get balance trends with updated logic
function getBalanceTrends($startDate, $endDate) {
    global $conn;
    $query = "SELECT account, name, created_at, 
                     SUM(amount) OVER (PARTITION BY account_id ORDER BY created_at) as running_balance 
              FROM balance 
              WHERE created_at BETWEEN ? AND ? 
              ORDER BY account_id, created_at";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to generate a PDF report
function generatePDF($data, $reportType) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, ucfirst(str_replace('_', ' ', $reportType)), 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 12);

    switch ($reportType) {
        case 'account_summary':
            $pdf->Cell(60, 10, 'Account Name', 1);
            $pdf->Cell(40, 10, 'Balance', 1);
            $pdf->Cell(60, 10, 'Last Updated', 1);
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 12);
            foreach ($data as $row) {
                $pdf->Cell(60, 10, $row['name'], 1);
                $pdf->Cell(40, 10, 'KES' . number_format($row['balance'], 2), 1);
                $pdf->Cell(60, 10, $row['created_at'], 1);
                $pdf->Ln();
            }
            break;

        case 'transaction_history':
            $pdf->Cell(40, 10, 'Date', 1);
            $pdf->Cell(50, 10, 'Account', 1);
            $pdf->Cell(50, 10, 'Transaction ID', 1);
            $pdf->Cell(40, 10, 'Amount', 1);
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 12);
            foreach ($data as $row) {
                $pdf->Cell(40, 10, $row['created_at'], 1);
                $pdf->Cell(50, 10, $row['account_reference'], 1);
                $pdf->Cell(50, 10, $row['trans_id'], 1);
                $pdf->Cell(40, 10, 'KES' . number_format($row['amount'], 2), 1);
                $pdf->Ln();
            }
            break;

        case 'balance_trends':
            $pdf->Cell(40, 10, 'Date', 1);
            $pdf->Cell(60, 10, 'Account', 1);
            $pdf->Cell(60, 10, 'Running Balance', 1);
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 12);
            foreach ($data as $row) {
                $pdf->Cell(40, 10, $row['created_at'], 1);
                $pdf->Cell(60, 10, $row['name'], 1);
                $pdf->Cell(60, 10, '$' . number_format($row['running_balance'], 2), 1);
                $pdf->Ln();
            }
            break;
    }

    return $pdf->Output('S');
}

// Function to generate an Excel report
function generateExcel($data, $reportType) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $rowNum = 1;

    switch ($reportType) {
        case 'account_summary':
            $sheet->setCellValue('A'.$rowNum, 'Account Name');
            $sheet->setCellValue('B'.$rowNum, 'Initial Balance');
            $sheet->setCellValue('C'.$rowNum, 'Total Payments');
            $sheet->setCellValue('D'.$rowNum, 'Total Expenses');
            $sheet->setCellValue('E'.$rowNum, 'Final Balance');
            $rowNum++;

            foreach ($data as $row) {
                $sheet->setCellValue('A'.$rowNum, $row['name']);
                $sheet->setCellValue('B'.$rowNum, $row['initial_balance']);
                $sheet->setCellValue('C'.$rowNum, $row['total_payments']);
                $sheet->setCellValue('D'.$rowNum, $row['total_expenses']);
                $sheet->setCellValue('E'.$rowNum, $row['balance']);
                $rowNum++;
            }
            break;

        case 'transaction_history':
            $sheet->setCellValue('A'.$rowNum, 'Date');
            $sheet->setCellValue('B'.$rowNum, 'Account');
            $sheet->setCellValue('C'.$rowNum, 'Type');
            $sheet->setCellValue('D'.$rowNum, 'Amount');
            $rowNum++;

            foreach ($data as $row) {
                $sheet->setCellValue('A'.$rowNum, $row['created_at']);
                $sheet->setCellValue('B'.$rowNum, $row['account_name']);
                $sheet->setCellValue('C'.$rowNum, $row['transaction_type']);
                $sheet->setCellValue('D'.$rowNum, $row['amount']);
                $rowNum++;
            }
            break;

        case 'balance_trends':
            $sheet->setCellValue('A'.$rowNum, 'Date');
            $sheet->setCellValue('B'.$rowNum, 'Account');
            $sheet->setCellValue('C'.$rowNum, 'Running Balance');
            $rowNum++;

            foreach ($data as $row) {
                $sheet->setCellValue('A'.$rowNum, $row['created_at']);
                $sheet->setCellValue('B'.$rowNum, $row['name']);
                $sheet->setCellValue('C'.$rowNum, $row['running_balance']);
                $rowNum++;
            }
            break;
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $excelOutput = ob_get_clean();

    return $excelOutput;
}
