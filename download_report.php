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

// Sanitization function for PDF content
function sanitizePDFContent($content) {
    // Decode HTML entities
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Remove HTML tags
    $content = strip_tags($content);
    
    // Fix common special characters
    $content = str_replace(
        ['â€œ', 'â€', 'â€™', 'â€"', 'â€"', '&quot;', '&amp;', '&lt;', '&gt;', '&nbsp;'], 
        ['"', '"', "'", '-', '-', '"', '&', '<', '>', ' '],
        $content
    );
    
    // Remove any remaining non-printable characters
    $content = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $content);
    
    // Normalize spaces
    $content = preg_replace('/\s+/', ' ', $content);
    
    return trim($content);
}

class PDF extends FPDF
{
    protected $firstPage = true;
    protected $compilerName;

    function __construct($compilerName)
    {
        parent::__construct();
        $this->compilerName = $this->sanitizePDFContent($compilerName);
        $this->SetAutoPageBreak(true, 25); // Set auto page break with 25mm margin
    }

    // Internal sanitization method for the PDF class
    protected function sanitizePDFContent($text) {
        if (is_string($text)) {
            return sanitizePDFContent($text);
        }
        return $text;
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
            $this->SetTextColor(128, 128, 128);
            $this->SetFont('Helvetica', 'I', 10);
            $this->Cell(0, 10, 'MOUT JKUAT MINISTRY - AGM Report', 0, 0, 'R');
            $this->Ln(15);
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
        $this->CheckPageBreak();
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(0, 51, 102);  // Dark blue text
        $this->Cell(0, 10, $this->sanitizePDFContent($title), 0, 1, 'L');
        
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
        $this->MultiCell(0, 6, $this->sanitizePDFContent($content), 0, 'J');
        $this->Ln(10);
    }

    function CreateInfoBox($label, $value)
    {
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(0, 51, 102);  // Dark blue for label
        $this->Cell(50, 8, $this->sanitizePDFContent($label), 0);
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);  // Dark gray for value
        $this->Cell(0, 8, $this->sanitizePDFContent($value), 0, 1);
    }

    function CheckPageBreak()
    {
        if ($this->GetY() > 250) {  // Adjust this value as needed
            $this->AddPage();
        }
    }
}

try {
    // Prepare and execute query with prepared statement
    $report_id = (int) $_GET['report_id'];
    $query = "SELECT r.*, u.name as user_name, d.name as docket_name, s.year as spiritual_year
              FROM reports r
              JOIN admin_users u ON r.user_id = u.id
              JOIN dockets d ON r.docket_id = d.id
              JOIN spiritual_years s ON r.spiritual_year_id = s.id
              WHERE r.id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $report_id);
    mysqli_stmt_execute($stmt);
    $report_result = mysqli_stmt_get_result($stmt);

    if (!$report_result || mysqli_num_rows($report_result) == 0) {
        throw new Exception("Report not found.");
    }

    $report = mysqli_fetch_assoc($report_result);

    // Create PDF instance
    $pdf = new PDF($report['user_name']);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Set default font encoding
    $pdf->SetFont('Helvetica', '', 10);

    // Report Details
    $pdf->CreateInfoBox('Docket Served:', $report['docket_name']);
    $pdf->CreateInfoBox('Compiled By:', $report['user_name']);
    $pdf->CreateInfoBox('Spiritual Year:', $report['spiritual_year']);
    $pdf->CreateInfoBox('Status:', ucfirst($report['status']));
    $pdf->CreateInfoBox('Date Created:', date('F j, Y', strtotime($report['created_at'])));

    $pdf->Ln(10);

    // Main content sections
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
        if (!empty(trim($content))) {  // Only show sections with content
            $pdf->ChapterTitle($title);
            $pdf->ChapterBody($content);
        }
    }

    // Add a decorative element at the end
    $pdf->SetDrawColor(0, 51, 102);  // Dark blue
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());

    // Generate unique filename
    $filename = 'MOUT_JKUAT_AGM_Report_' . $report_id . '_' . date('Y-m-d') . '.pdf';
    
    // Set appropriate headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF
    $pdf->Output('D', $filename);

} catch (Exception $e) {
    // Log error (you should implement proper error logging)
    error_log("PDF Generation Error: " . $e->getMessage());
    
    // Show user-friendly error message
    echo "An error occurred while generating the PDF. Please try again later.";
    exit();
} finally {
    // Clean up
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>