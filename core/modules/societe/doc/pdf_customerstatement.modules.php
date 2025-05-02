<?php

require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

class pdf_customerstatement extends TCPDF
{

    protected array $company;
    protected array $customer;
    protected array $user;
    //public float $header_bottom_y = 90;
    public string $customer_country_code;
    public string $company_country_code;
    private float $fixedHeaderHeight = 90;
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format, true, 'UTF-8', false);

        // Set equal left and right margins
        $this->SetMargins(15, 10, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(15);
        $this->SetAutoPageBreak(true, 15);
    }

    public function setContext(array $company_info, array $customer_info, array $user_info): void
    {
        $this->company = $company_info;
        $this->customer = $customer_info;
        $this->user = $user_info;
    }

    public function Header(): void
    {

        global $langs;
       // or 100 if space is tight

        if($this->numpages == 1)
        {
            $leftMargin = $this->getMargins()['left'];
            $rightMargin = $this->getMargins()['right'];
            $pageWidth = $this->getPageWidth();
            $contentWidth = $pageWidth - $leftMargin - $rightMargin;

            // Right-hand content starts here
            $rightStart = $leftMargin + $contentWidth - 90;

            // Logo
            if (!empty($this->company['logo']) && is_readable($this->company['logo'])) {
                $this->Image($this->company['logo'], $leftMargin, 10, 0, 20);
            }

            // Company info
            $company_lines = [$this->company['name'], $this->company['address']];

            if (!empty($this->company['phone'])) {
                $company_lines[] = "Phone: " . $this->company['phone'];
            }
            if (!empty($this->company['email'])) {
                $company_lines[] = "Email: " . $this->company['email'];
            }
            if (!empty($this->company['vat'])) {
                $company_lines[] = $langs->transnoentities("VATIntraShort").":" . $this->company['vat'];
            }

            $this->SetXY($leftMargin, 50);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 5, 'From:', 0, 1, 'L');

            $this->SetFillColor(230, 230, 230);
            $this->RoundedRect($leftMargin, 55, 85, 35, 0, '1234', 'F');
            $this->SetFont('helvetica', '', 10);
            $this->SetXY($leftMargin + 5, 57);
            $this->MultiCell(85, 5, implode("\n", $company_lines), 0, 'L');  $y_end1 = $this->GetY();

            // Customer info
            $customer_lines = [$this->customer['name'], $this->customer['address']];

            if (!empty($this->customer['phone'])) {
                $customer_lines[] = "Phone: " . $this->customer['phone'];
            }
            if (!empty($this->customer['email'])) {
                $customer_lines[] = "Email: " . $this->customer['email'];
            }
            if (!empty($this->customer['vat'])) {
                $customer_lines[] = $langs->transnoentities("VATIntraShort").":" . $this->customer['vat'];
            }

            $this->RoundedRect($rightStart, 55, 90, 35, 0, '1234', 'D');
            $this->SetXY($rightStart, 50);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 5, 'To:', 0, 1, 'L');

            $this->SetFont('helvetica', '', 10);
            $this->SetXY($rightStart + 5, 57);
            $this->MultiCell(85, 5, implode("\n", $customer_lines), 0, 'L');
            $y_end2 = $this->GetY();

            // Top-right box
            $boxX = $leftMargin + $contentWidth - 60; // align with margin
            $this->SetXY($boxX, 10);
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(60, 6, "Customer Statement", 0, 2, 'R');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(60, 6, "Customer Ref: " . $this->customer['ref'], 0, 2, 'R');
            $this->Cell(60, 6, "Statement Period: " . $this->customer['date'], 0, 2, 'R');

           // $this->Ln(10);
           
            $this->header_bottom_y = $this->fixedHeaderHeight;

        }



    }

    public function Footer(): void
    {
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);

        $email = $this->company['email'] ?? '';
        $phone = $this->company['phone'] ?? '';

        $contactLine = "Should you have any enquiries concerning this statement, please contact";

        if ($email && $phone) {
            $contactLine .= " $email or $phone.";
        } elseif ($email) {
            $contactLine .= " $email.";
        } elseif ($phone) {
            $contactLine .= " $phone.";
        }

        if ($email || $phone) {
            $this->MultiCell(0, 5, $contactLine, 0, 'L');
        }

        $this->SetY(-15);
        $this->SetFont('helvetica', '', 8);

        // Left: Date Printed
        $printedDate = dol_print_date(dol_now(), 'dayhourtext'); // e.g., 21 April 2025 14:55
        $this->Cell(0, 5, 'Date Printed: ' . $printedDate, 0, 0, 'L');

        // Right: Page X of Y
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }


    public function writeStatement(array $transactions): void
    {
        $this->Ln(4);
        $leftMargin = $this->getMargins()['left'];
        $contentWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];

        $colWidths = [25, 30, 35, 30, 30, 30];
        $this->SetX($leftMargin);

        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($contentWidth, 5, 'Transactions', 0, 1, 'L');

        $this->SetX($leftMargin);
        $this->SetFillColor(240, 240, 240);
        $this->SetDrawColor(0);

        $headers = ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Balance'];
        foreach ($headers as $i => $title) {
            $this->Cell($colWidths[$i], 7, $title, 1, 0, $i < 3 ? 'L' : 'R', 1);
        }
        $this->Ln();

        $this->SetFont('helvetica', '', 10);
        $balance = (float) $transactions[0]['balance'];

        foreach ($transactions as $index => $line) {
            $debit = $line['debit'] !== '' ? (float) $line['debit'] : 0;
            $credit = $line['credit'] !== '' ? (float) $line['credit'] : 0;

            if ($index > 0) $balance += $debit - $credit;

            $this->SetX($leftMargin);
            $this->Cell($colWidths[0], 6, explode(' ', $line['date'])[0], 'LR');
            $this->Cell($colWidths[1], 6, $line['type'], 'LR');
            $this->Cell($colWidths[2], 6, $line['ref'], 'LR');
            $this->Cell($colWidths[3], 6, $debit > 0 ? number_format($debit, 2) : '', 'LR', 0, 'R');
            $this->Cell($colWidths[4], 6, $credit > 0 ? number_format($credit, 2) : '', 'LR', 0, 'R');
            $this->Cell($colWidths[5], 6, number_format($balance, 2), 'LR', 1, 'R');
        }

        // --- Fill if short ---
        $pageHeight = $this->getPageHeight();
        $bottomMargin = 15;
        $gapBeforeAging = 8;
        $minContentY = $pageHeight - $bottomMargin - $gapBeforeAging - 50;

        if ($this->GetY() < $minContentY) {
            while ($this->GetY() + 6 < $minContentY) {
                $this->SetX($leftMargin);
                foreach ($colWidths as $width) {
                    $this->Cell($width, 6, '', 'LR');
                }
                $this->Ln();
            }
        }

        // --- Ensure space for closing balance ---
        //$requiredSpace = 12;
        //if ($this->GetY() + $requiredSpace > $this->getPageHeight() - $this->getBreakMargin()) {
        //    $this->AddPage();
        //    $this->SetX($leftMargin);
        //}

        // --- Draw closing balance row ---
        $this->SetFont('helvetica', 'B', 10);
        $this->SetX($leftMargin);
        $this->Cell($colWidths[0] + $colWidths[1] + $colWidths[2], 6, 'Closing Balance', 'LTB');
        $this->Cell($colWidths[3], 6, '', 'TB');
        $this->Cell($colWidths[4], 6, '', 'TB');
        $this->Cell($colWidths[5], 6, number_format($balance, 2), 'RTB', 1, 'R');

        // --- Optional bottom border ---
        $this->SetX($leftMargin);
        $this->Cell(array_sum($colWidths), 0, '', 'T');
    }

    public function writeAgingSummary(array $aging): void
    {
        $leftMargin = $this->getMargins()['left'];
        $contentWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];

        $cols = ['90+ Days', '60-89 Days', 'Current', 'Not Due', 'Total'];
        $colWidth = $contentWidth / count($cols);
        $headingHeight = 8;
        $rowHeight = 7;

        $this->Ln(4);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetX($leftMargin);
        $this->Cell($contentWidth, 5, 'Age Analysis', 0, 1, 'L');

        $this->SetX($leftMargin);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetFillColor(240, 240, 240);

        foreach ($cols as $label) {
            $this->Cell($colWidth, $headingHeight, $label, 1, 0, 'R', 1);
        }
        $this->Ln();

        $this->SetX($leftMargin);
        $this->SetFont('helvetica', '', 10);
        $this->Cell($colWidth, $rowHeight, number_format($aging['90+'], 2), 1, 0, 'R');
        $this->Cell($colWidth, $rowHeight, number_format($aging['60-89'], 2), 1, 0, 'R');
        $this->Cell($colWidth, $rowHeight, number_format($aging['current'], 2), 1, 0, 'R');
        $this->Cell($colWidth, $rowHeight, number_format($aging['not_due'], 2), 1, 0, 'R');
        $this->Cell($colWidth, $rowHeight, number_format($aging['total'], 2), 1, 1, 'R');
    }
public function writeSummaryWithPaymentNotice(array $summary, float $due_now, array $bank_info): void
{
    $leftMargin = $this->getMargins()['left'];
    $contentWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];

    $leftWidth = 95;
    $rightWidth = $contentWidth - $leftWidth - 5;

    // Ensure consistent vertical start: 10mm below header
    $startX = $leftMargin;
    $startY = $this->header_bottom_y + 10;
    $this->SetY($startY);

    $lineHeight = 5;

    // --- LEFT COLUMN: Payment Notice ---
    $payText = '';
    if ($due_now > 0) {
        $this->SetFont('helvetica', 'B', 10);
        $payText .= "Amount due for immediate payment: " . number_format($due_now, 2) . "\n\n";
    }

    $this->SetFont('helvetica', '', 10);
    $payText .= "Please make payment to:\n";
    $payText .= "Account Holder: " . $bank_info['owner'] . "\n";
    $payText .= "Bank Name: " . $bank_info['bank'] . "\n";
    $payText .= "Account No: " . $bank_info['account'] . "\n";

    if (
        strtoupper($this->customer_country_code) !== strtoupper($this->company_country_code) &&
        !empty($bank_info['bic'])
    ) {
        $payText .= "SWIFT/BIC: " . $bank_info['bic'] . "\n";
    }

    $this->SetXY($startX + 2, $startY + 2);
    $this->MultiCell($leftWidth, $lineHeight, $payText, 0, 'L');
    $endY_left = $this->GetY();

    // --- RIGHT COLUMN: Statement Summary ---
    $this->SetXY($startX + $leftWidth, $startY + 2);
    $this->SetFont('helvetica', 'B', 10);
    $this->Cell($rightWidth, 5, 'Statement Summary', 0, 1, 'R');

    $this->SetFont('helvetica', '', 10);
    foreach ([
        'Opening Balance' => $summary['opening'],
        'Total Debits'    => $summary['debits'],
        'Total Credits'   => $summary['credits'],
        'Closing Balance' => $summary['closing']
    ] as $label => $value) {
        $this->SetX($startX + $leftWidth);
        $this->Cell($rightWidth, $lineHeight, $label . ': ' . number_format($value, 2), 0, 1, 'R');
    }

    // --- Outline box around both columns ---
    $boxHeight = max($this->GetY(), $endY_left) - $startY;
    $this->Rect($startX, $startY, $contentWidth, $boxHeight + 2);

    // Move cursor down
    $this->SetY($startY + $boxHeight + 6);
}


}
