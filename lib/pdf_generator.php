<?php

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once __DIR__ . '/../core/modules/societe/doc/pdf_customerstatement.modules.php';
require_once __DIR__ . '/functions.php';

function generateCustomerStatementPDF($thirdparty_id, $startdate, $enddate): bool|string
{
    global $langs, $db, $conf, $user, $mysoc;
    $end_clean = substr($enddate, 0, 10);
     
    $object = new Societe($db);
    if (!$object->fetch($thirdparty_id)) return false;

    if ($object->client != 1 && $object->client != 3) return false;

    $results = getCustomerStatementData($db, $thirdparty_id, $startdate, $enddate);
    if (empty($results['transactions'])) return false;

    $statement = $results['transactions'];
    $aging     = $results['aging'];
    $due_now   = $results['due_now'];
    $bank_info = getLastUsedBankAccount($db, $conf);

    $company_info = [
        'name' => $mysoc->name,
        'address' => $mysoc->getFullAddress(),
        'logo' => DOL_DATA_ROOT . '/mycompany/logos/' . $mysoc->logo,
        'email' => $mysoc->email,
        'phone' => $mysoc->phone,
       'vat' => $mysoc->tva_intra
    ];
   
    $customer_info = [
        'name' => $object->name,
        'address' => $object->getFullAddress(),
        'ref' => $object->code_client,
        'date' => dol_print_date(strtotime($startdate), 'day') . ' - ' . dol_print_date(strtotime($end_clean), 'day'),
        'email'=> $object->email,
        'phone' => $object->phone,
        'vat' => $object->tva_intra,
    ];

    $user_info = [
        'fullname' => $user->getFullName($langs),
        'email' => $user->email,
        'phone' => $user->office_phone
    ];

    $summary = [
        'opening' => $statement[0]['balance'],
        'debits'  => array_sum(array_column($statement, 'debit')),
        'credits' => array_sum(array_column($statement, 'credit')),
        'closing' => end($statement)['balance']
    ];

    $pdf = new pdf_customerstatement();
    $pdf->setContext($company_info, $customer_info, $user_info);
    $pdf->customer_country_code = $object->country_code;
    $pdf->company_country_code  = $mysoc->country_code;
    $pdf->conf = $conf;
    $pdf->langs = $langs;

    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15 + 40);

    $pdf->SetY($pdf->header_bottom_y);
    $pdf->writeSummaryWithPaymentNotice($summary, $due_now, $bank_info);
    $pdf->writeStatement($statement);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetY($pdf->getPageHeight() - 55);
    $pdf->writeAgingSummary($aging);

    $filename = 'statement_' . dol_sanitizeFileName(dol_print_date($startdate, 'day') . '_' . dol_print_date($end_clean, 'day')) . '.pdf';
    $relativepath = $thirdparty_id . '/' . $filename;
    $filepath = $conf->societe->multidir_output[$conf->entity] . '/' . $relativepath;

    dol_mkdir(dirname($filepath));
    $pdf->Output($filepath, 'F');


    return $relativepath;

}
