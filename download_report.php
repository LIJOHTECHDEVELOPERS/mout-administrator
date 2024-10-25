<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';
require('fpdf/fpdf.php');

if (!isset($_GET['report_id'])) {
    echo "No report ID provided.";
    exit();
}

$report_id = (int) $_GET['report_id'];

$query = "SELECT r.*, u.name as user_name, d.name as docket_name, s.year as spiritual_year
          FROM reports r
          JOIN admin_users u ON r.user_id = u.id
          JOIN dockets d ON r.docket_id = d.id
          JOIN spiritual_years s ON r.spiritual_year_id = s.id
          WHERE r.id = $report_id";

$report_result = mysqli_query($conn, $query);

if (!$report_result || mysqli_num_rows($report_result) == 0) {
    echo "Report not found.";
    exit();
}

$report = mysqli_fetch_assoc($report_result);

class PDF extends FPDF
{
    protected $firstPage = true;
    protected $compilerName;

    function __construct($compilerName)
    {
        parent::__construct();
        $this->compilerName = $compilerName;
    }

    function Header()
    {
        if ($this->firstPage) {
            // Subtle gray background for header
            $this->SetFillColor(245, 245, 245);
            $this->Rect(0, 0, 210, 50, 'F');
            
            // Dark blue text for header
            $this->SetTextColor(0, 51, 102);
            $this->SetFont('Helvetica', 'B', 20);
            $this->Cell(0, 20, 'MOUT JKUAT MINISTRY', 0, 1, 'C');
            $this->SetFont('Helvetica', 'B', 16);
            $this->Cell(0, 10, 'AGM REPORT', 0, 1, 'C');
            
            // Accent line
            $this->SetDrawColor(255, 153, 0);  // Orange
            $this->SetLineWidth(0.5);
            $this->Line(10, 42, 200, 42);
            
            $this->Ln(20);  // Add some space after the header
            $this->firstPage = false;
        } else {
            $this->Ln(10);  // Add some space at the top of subsequent pages
        }
    }

    function Footer()
    {
        $this->SetY(-20);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);  // Gray text for footer
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 1, 'C');
        $this->Cell(0, 5, 'Compiled by: ' . $this->compilerName, 0, 0, 'C');
    }

    function ChapterTitle($title)
    {
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(0, 51, 102);  // Dark blue text
        $this->Cell(0, 10, $title, 0, 1, 'L');
        
        // Accent line under title
        $this->SetDrawColor(255, 153, 0);  // Orange
        $this->SetLineWidth(0.3);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 190, $this->GetY());
        
        $this->Ln(5);
    }

    function ChapterBody($content)
    {
        $this->SetTextColor(51, 51, 51);  // Dark gray for body text
        $this->SetFont('Helvetica', '', 11);
        $this->MultiCell(0, 6, $content, 0, 'J');
        $this->Ln(10);
    }

    function CreateInfoBox($label, $value)
    {
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(0, 51, 102);  // Dark blue for label
        $this->Cell(50, 8, $label, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);  // Dark gray for value
        $this->Cell(0, 8, $value, 0, 1);
    }

    function CheckPageBreak()
    {
        if ($this->GetY() > 250) {  // Adjust this value as needed
            $this->AddPage();
        }
    }
}

$pdf = new PDF($report['user_name']);
$pdf->AliasNbPages();
$pdf->AddPage();

// Report Details
$pdf->CreateInfoBox('Docket Served:', $report['docket_name']);
$pdf->CreateInfoBox('Compiled By:', $report['user_name']);
$pdf->CreateInfoBox('Spiritual Year:', $report['spiritual_year']);
$pdf->CreateInfoBox('Status:', ucfirst($report['status']));
$pdf->CreateInfoBox('Date Created:', date('F j, Y', strtotime($report['created_at'])));

$pdf->Ln(10);

// Main content
$sections = [
    'Greetings' => $report['greetings'],
    'Responsibilities' => $report['responsibilities'],
    'Accomplishments' => $report['accomplishments'],
    'Challenges' => $report['challenges'],
    'Recognitions' => $report['recognitions'],
    'Recommendations' => $report['recommendations'],
    'Conclusion' => $report['conclusion']
];

foreach ($sections as $title => $content) {
    $pdf->CheckPageBreak();
    $pdf->ChapterTitle($title);
    $pdf->CheckPageBreak();
    $pdf->ChapterBody($content);
}

// Add a decorative element at the end
$pdf->SetDrawColor(0, 51, 102);  // Dark blue
$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());

$pdf->Output('D', 'MOUT_JKUAT_AGM_Report_' . $report['id'] . '.pdf');
?>