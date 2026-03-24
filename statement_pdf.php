<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("testr@testr"));

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check - Protection if external user
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}


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
