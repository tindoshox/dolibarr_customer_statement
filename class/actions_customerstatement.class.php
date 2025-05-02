<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2025		Tiny ADMIN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    customerstatement/class/actions_customerstatement.class.php
 * \ingroup customerstatement
 * \brief   Example hook overload.
 *
 * TODO: Write detailed description here.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonhookactions.class.php';


/**
 * Class ActionsCustomerStatement
 */
class ActionsCustomerStatement extends CommonHookActions
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var string[] Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var ?string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * @var int        Priority of hook (50 is used if value is not defined)
     */
    public int $priority;


    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /* Add other hook methods here... */

    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $form = new Form($object->db);


        if ($parameters['currentcontext'] !== 'thirdpartycard') return 0;
        if (empty($object->client) || $object->client == 2) return 0;

        // Check if customer has invoices
        $sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "facture
            WHERE fk_soc = " . (int) $object->id . " AND fk_statut IN (1, 2)";
        $res = $object->db->query($sql);
        if (!$res || ($object->db->fetch_object($res)->nb ?? 0) < 1) return 0;

        $langs->load("customerstatement@customerstatement");

        $token = newToken();

        $now = dol_now();
        $start = dol_print_date(strtotime('first day of this month'), '%Y-%m-%d');
        $end = dol_print_date(strtotime('today 23:59:59'), '%Y-%m-%d');

        print '<div class="inline-block">';
        print '<span class="butAction" id="openStatementModalBtn">' . $langs->trans("GenerateCustomerStatement") . '</span>';
        print '</div>';

        // Modal markup (Dolibarr style)
        print '
   
            <div id="statementModal" class="popup" style="display:none;">
    <div class="popup-block">
        <div class="popup-header">' . $langs->trans("SelectDateRange") . '</div>
        <div class="popup-body">
            <form id="statementForm" method="POST" action="' . DOL_URL_ROOT . '/custom/customerstatement/statement_pdf.php" target="hiddenIframe">
                <input type="hidden" name="id" value="' . $object->id . '">
                <input type="hidden" name="token" value="' . newToken() . '">

                <table class="noborder" width="100%">
                    <tr><td>' . $langs->trans("StartDate") . '</td><td>' .
                        $form->selectDate(strtotime("first day of this month"), "from_date", 0, 0, 0, "", 1, 1) . '
                    </td></tr>
                    <tr><td>' . $langs->trans("EndDate") . '</td><td>' .
                        $form->selectDate(strtotime("today 23:59:59"), "to_date", 0, 0, 0, "", 1, 1) . '
                    </td></tr>
                </table>

                <br>
                <div class="center">
                    <button type="submit" class="button">' . $langs->trans("Generate") . '</button>
                    <button type="button" class="button" onclick="document.getElementById(\'statementModal\').style.display=\'none\';">' . $langs->trans("Cancel") . '</button>
                </div>
            </form>
        </div>
    </div>
</div>';

        print '<iframe name="hiddenIframe" style="display:none;"></iframe>';
        print '
<style>
#statementModal {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.4);
    display: none;
    z-index: 9999;
}

#statementModal .popup-block {
    background-color: #fff;
    border: 1px solid #aaa;
    border-radius: 4px;
    width: 500px;
    max-width: 90%;
    margin: 100px auto;
    padding: 20px;
    box-shadow: 0 0 10px #333;
}

#statementModal .popup-header {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 10px;
}
</style>
';

        print '<script>
        document.getElementById("openStatementModalBtn").addEventListener("click", function() {
            document.getElementById("statementModal").style.display = "block";
        });

        document.getElementById("statementForm").addEventListener("submit", function() {
            setTimeout(function() { location.reload(); }, 1500);
        });
    </script>';

        return 0;
    }

}
