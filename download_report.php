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

// Enhanced HTML content processor
function processHTMLContent($content) {
    // First decode HTML entities
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Fix common special characters
    $content = str_replace(
        ['â€œ', 'â€', 'â€™', 'â€"', 'â€"', '&quot;', '&amp;', '&lt;', '&gt;', '&nbsp;'],
        ['"', '"', "'", '-', '-', '"', '&', '<', '>', ' '],
        $content
    );
    
    // Convert HTML lists to formatted text
    $content = preg_replace('/<ol[^>]*>/', "\n", $content);
    $content = preg_replace('/<ul[^>]*>/', "\n", $content);
    $content = preg_replace('/<\/ol>/', "\n", $content);
    $content = preg_replace('/<\/ul>/', "\n", $content);
    
    // Convert list items to bullet points
    $lines = explode("\n", $content);
    $processed_lines = [];
    $list_number = 0;
    $in_list = false;
    
    foreach ($lines as $line) {
        if (preg_match('/<li[^>]*>(.*?)<\/li>/', $line, $matches)) {
            if (!$in_list) {
                $list_number = 1;
                $in_list = true;
            }
            // Convert list item to proper format
            $line_content = strip_tags($matches[1]);
            $processed_lines[] = "   " . $list_number . ". " . trim($line_content);
            $list_number++;
        } else {
            // Handle paragraphs and other elements
            $line = strip_tags($line, '<p><br><strong><em>');
            if (!empty(trim($line))) {
                $in_list = false;
                $processed_lines[] = trim($line);
            }
        }
    }
    
    $content = implode("\n", $processed_lines);
    
    // Convert <br> and </p> tags to new lines
    $content = preg_replace('/<br[^>]*>/', "\n", $content);
    $content = preg_replace('/<\/p>/', "\n\n", $content);
    
    // Remove remaining HTML tags but maintain line breaks
    $content = strip_tags($content);
    
    // Normalize spaces while preserving intentional line breaks
    $content = preg_replace('/[ \t]+/', ' ', $content);
    $content = preg_replace('/\n\s+/', "\n", $content);
    $content = preg_replace('/\n{3,}/', "\n\n", $content);
    
    return trim($content);
}

class PDF extends FPDF
{
    protected $firstPage = true;
    protected $compilerName;
    protected $leftMargin = 20;
    protected $lineHeight = 6;

    function __construct($compilerName)
    {
        parent::__construct();
        $this->compilerName = processHTMLContent($compilerName);
        $this->SetAutoPageBreak(true, 25);
        $this->SetMargins($this->leftMargin, 20, 20);
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
        $title = processHTMLContent($title);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        
        // Accent line under title
        $this->SetDrawColor(255, 153, 0);  // Orange
        $this->SetLineWidth(0.3);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 190, $this->GetY());
        
        $this->Ln(5);
    }

    function ChapterBody($content)
    {
        $this->SetTextColor(51, 51, 51);
        $this->SetFont('Helvetica', '', 11);
        
        // Process the content
        $content = processHTMLContent($content);
        $paragraphs = explode("\n", $content);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                $this->Ln($this->lineHeight);
                continue;
            }
            
            // Check if this is a list item
            if (preg_match('/^\s*(\d+)\.\s(.*)/', $paragraph, $matches)) {
                // This is a numbered list item
                $this->SetX($this->leftMargin + 5);
                $this->MultiCell(0, $this->lineHeight, $paragraph, 0, 'L');
            } else {
                // Regular paragraph
                $this->SetX($this->leftMargin);
                $this->MultiCell(0, $this->lineHeight, $paragraph, 0, 'J');
            }
            
            $this->Ln($this->lineHeight/2);
        }
        
        $this->Ln($this->lineHeight);
    }

    function CreateInfoBox($label, $value)
    {
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(0, 51, 102);  // Dark blue for label
        $label = processHTMLContent($label);
        $this->Cell(50, 8, $label, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);  // Dark gray for value
        $value = processHTMLContent($value);
        $this->Cell(0, 8, $value, 0, 1);
    }

    function CheckPageBreak()
    {
        if ($this->GetY() > 250) {  // Adjust this value as needed
            $this->AddPage();
        }
    }

    function WriteHTML($html)
    {
        $content = processHTMLContent($html);
        $this->Write($this->lineHeight, $content);
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

    // Main content sections with improved formatting
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
        if (!empty(trim($content))) {
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
    error_log("PDF Generation Error: " . $e->getMessage());
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