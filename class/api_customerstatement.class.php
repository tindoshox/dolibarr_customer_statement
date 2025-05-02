<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2016   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2023   Romain Neil             <contact@romain-neil.fr>
 *
 * This program is free software you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\Format\UploadFormat;
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';


/**
 * API class for receive files
 *
 * @access protected
 * @class Reports {@requires user,external}
 */
class CustomerStatement extends DolibarrApi
{

    /**
     * @var array $DOCUMENT_FIELDS Mandatory fields, checked when create and update object
     */
    public static $DOCUMENT_FIELDS = array(
        'documentid'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }


    /**
     * Build a document.
     *
     * Test sample 1: { "socid":"socid", ":"startdate":"startdate", "enddate": "enddate", }.
     *
     * Supported modules: customerstatement
     *
     * @param string $socid {@from body}
     * @param string $startdate {@from body}
     * @param string $enddate {@from body}
     * @return  array                   List of documents
     *
     * @throws RestException 500 System error
     * @throws RestException 501
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     *
     * @url PUT /builddoc
     */


    public function builddoc(string $socid, string $startdate='', string $enddate=''): array
    {

        global $conf;
        $end_clean = substr($enddate, 0, 10);
        $modulepart = 'thirdparty';

        $original_file = "$socid/statement_" . dol_sanitizeFileName(dol_print_date(strtotime($startdate), 'day') . '_' . dol_print_date(strtotime($end_clean), 'day')) . ".pdf";
        //--- Finds and returns the document
        $entity = $conf->entity;

        $relativefile = $original_file;

        $check_access = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', '');
        $accessallowed = $check_access['accessallowed'];
        $sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
        $original_file = $check_access['original_file'];

        if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
            throw new RestException(401);
        }
        if (!$accessallowed) {
            throw new RestException(401);
        }


        // --- Generates the document

        require_once __DIR__ . '/../lib/pdf_generator.php';

        $result = generateCustomerStatementPDF($socid, $startdate,$enddate);
        if ($result <= 0) {
            throw new RestException(500, $result);
        }

        $filename = basename($original_file);
        $original_file_osencoded = dol_osencode($original_file); // New file name encoded in OS encoding charset

        if (!file_exists($original_file_osencoded)) {
            throw new RestException(404, 'File not found');
        }

        $file_content = file_get_contents($original_file_osencoded);
        return array('filename' => $filename, 'content-type' => dol_mimetype($filename), 'filesize' => filesize($original_file), 'content' => base64_encode($file_content), 'encoding' => 'base64');
    }

}
