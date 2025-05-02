<?php


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once __DIR__ . '/lib/pdf_generator.php';

$langs->load("customerstatement@customerstatement");

// CSRF check
if (!GETPOST('token') || !$_SESSION['newtoken'] || GETPOST('token') !== $_SESSION['newtoken']) {
accessforbidden('Invalid CSRF token');
}


$id = GETPOST('id', 'int');
if (!$id) {
http_response_code(400);
exit('Missing customer ID');
}


$from = dol_mktime(
    GETPOST('from_datehour', 'int'),
    GETPOST('from_datemin', 'int'),
    GETPOST('from_datesec', 'int'),
    GETPOST('from_datemonth', 'int'),
    GETPOST('from_dateday', 'int'),
    GETPOST('from_dateyear', 'int')
);

$to = dol_mktime(
    GETPOST('to_datehour', 'int'),
    GETPOST('to_datemin', 'int'),
    GETPOST('to_datesec', 'int'),
    GETPOST('to_datemonth', 'int'),
    GETPOST('to_dateday', 'int'),
    GETPOST('to_dateyear', 'int')
);


// auto-generate the date range
if (empty($from)) {
    $from = strtotime('first day of this month');
}
if (empty($to)) {
    $to = strtotime('today 23:59:59');
}
;

$from_sql = date('Y-m-d', $from);
$to_sql   = date('Y-m-d 23:59:59', $to);

// Generate PDF
$relativepath = generateCustomerStatementPDF($id, $from_sql, $to_sql);

if (!$relativepath) {
    setEventMessages("Statement generation failed", 'errors');
    http_response_code(500);
    exit('PDF generation failed');
}

http_response_code(200);
setEventMessages($langs->trans("Statement generated"), null);
exit('PDF saved');