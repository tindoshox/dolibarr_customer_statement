<?php

function getCustomerStatementData($db, $socid, $startdate, $enddate): array
{

    $running_balance = 0;

    $statement_end = new DateTime($enddate);

    // Opening balance before startdate
    $sql_opening = "SELECT SUM(f.total_ttc - COALESCE(p.amount, 0)) as opening_balance FROM " . MAIN_DB_PREFIX . "facture as f LEFT JOIN ( SELECT pf.fk_facture, SUM(pf.amount) as amount";
    $sql_opening .=" FROM " . MAIN_DB_PREFIX . "paiement_facture pf INNER JOIN " . MAIN_DB_PREFIX . "paiement p ON pf.fk_paiement = p.rowid WHERE p.datep < '" . $db->escape($startdate) . "'";
    $sql_opening .=" GROUP BY pf.fk_facture) as p ON f.rowid = p.fk_facture WHERE f.fk_soc = " . ((int)$socid) . " AND f.datef < '" . $db->escape($startdate) . "' AND f.fk_statut IN (1,2)";

    $res_opening = $db->query($sql_opening);
    $opening_balance = 0;
    if ($res_opening) {
        $obj = $db->fetch_object($res_opening);
        $opening_balance = round((float)($obj->opening_balance ?? 0), 2);
    }

    $data[] = [
        'date' => $startdate,
        'type' => 'Opening Balance',
        'ref' => '',
        'debit' => '',
        'credit' => '',
        'balance' => $running_balance
    ];


    $transactions = [];

// Fetch invoices
    $sql_inv = " SELECT datef as date, ref, total_ttc, type FROM " . MAIN_DB_PREFIX . "facture WHERE fk_soc = " . ((int)$socid) . " AND datef BETWEEN '" . $db->escape($startdate) . "' AND '" . $db->escape($enddate) . "'";
    $sql_inv .= " AND fk_statut IN (1,2)";

    $res_inv = $db->query($sql_inv);
    while ($obj = $db->fetch_object($res_inv)) {
        $is_credit_note = ((int)$obj->type === 2);
        $transactions[] = [
            'date' => $obj->date,
            'type' => $is_credit_note ? 'Credit Note' : 'Invoice',
            'ref' => $obj->ref,
            'debit' => $is_credit_note ? '' : (float)$obj->total_ttc,
            'credit' => $is_credit_note ? (float)$obj->total_ttc * -1 : '',

        ];
    }

// Fetch payments
    $sql_pay = "SELECT p.datep as date, pf.amount, f.ref, p.fk_paiement FROM " . MAIN_DB_PREFIX . "paiement_facture pf INNER JOIN " . MAIN_DB_PREFIX . "paiement p ON pf.fk_paiement = p.rowid";
    $sql_pay .= " INNER JOIN " . MAIN_DB_PREFIX . "facture f ON pf.fk_facture = f.rowid WHERE f.fk_soc = " . ((int)$socid) . " AND p.datep BETWEEN '" . $db->escape($startdate) . "' AND '" . $db->escape($enddate) . "'";

    $res_pay = $db->query($sql_pay);
    while ($obj = $db->fetch_object($res_pay)) {
        $is_refund = ((int)$obj->fk_paiement === 1); // or compare against refund payment mode ID
        $transactions[] = [
            'date' => $obj->date,
            'type' => $is_refund ? 'Refund' : 'Payment',
            'ref' => $obj->ref,
            'debit' => $is_refund ? (float)$obj->amount : '',
            'credit' => $is_refund ? '' : (float)$obj->amount,
        ];
    }

    usort($transactions, fn($a, $b) => strcmp($a['date'], $b['date']));

    // Running Balance

    $data = [[
        'date' => $startdate,
        'type' => 'Opening Balance',
        'ref' => '',
        'debit' => '',
        'credit' => '',
        'balance' => $opening_balance
    ]];

    $running_balance = $opening_balance;

    foreach ($transactions as $t) {
        $debit = $t['debit'] ?: 0;
        $credit = $t['credit'] ?: 0;

        $running_balance += $debit - $credit;

        $data[] = array_merge($t, [
            'balance' => $running_balance
        ]);
    }


    // Accurate Aging (based on due date)
    $aging = [
        '90+' => 0,
        '60-89' => 0,
        'current' => 0,
        'not_due'=>0,
        'total' => 0
    ];

    $sql_aging = "SELECT f.date_lim_reglement, f.total_ttc, COALESCE(p.amount, 0) as paid FROM " . MAIN_DB_PREFIX . "facture f LEFT JOIN (SELECT pf.fk_facture, SUM(pf.amount) as amount";
    $sql_aging .= " FROM " . MAIN_DB_PREFIX . "paiement_facture pf INNER JOIN " . MAIN_DB_PREFIX . "paiement p ON pf.fk_paiement = p.rowid WHERE p.datep < '".$db->escape($enddate)."'";
    $sql_aging .= " GROUP BY pf.fk_facture) p ON f.rowid = p.fk_facture WHERE f.fk_soc = " . ((int)$socid) . " AND f.datef <  '".$db->escape($enddate)."' AND f.fk_statut IN (1,2) AND f.paye = 0";

    $res_aging = $db->query($sql_aging);
    while ($obj = $db->fetch_object($res_aging)) {
        $amount_due = (float)$obj->total_ttc - (float)$obj->paid;
        if ($amount_due <= 0) continue;

        $due_date = new DateTime($obj->date_lim_reglement);
        $diff = $due_date->diff($statement_end);
        $age_days = $diff->invert ? 0 : $diff->days;

        if ($age_days < 30) {
            $aging['not_due'] += $amount_due;
        } elseif ($age_days < 60) {
            $aging['current'] += $amount_due;
        } elseif ($age_days < 90) {
            $aging['60-89'] += $amount_due;
        } else {
            $aging['90+'] += $amount_due;
        }
    }

    $due_now = $aging['current'] + $aging['60-89'] + $aging['90+'];
    $aging['total'] = $aging['not_due']+ $aging['current'] + $aging['60-89'] + $aging['90+'];



    return [
        'transactions' => $data,
        'aging' => $aging,
        'due_now' => $due_now

    ];
}

function getLastUsedBankAccount($db, $conf): array
{
    $sql = "SELECT ba.rowid, ba.label, ba.proprio owner, ba.number, ba.bic FROM " . MAIN_DB_PREFIX . "facture f INNER JOIN " . MAIN_DB_PREFIX . "bank_account ba ON f.fk_account = ba.rowid  WHERE f.fk_account IS NOT NULL";
    $sql .= " AND f.entity = " . ((int)$conf->entity) . " ORDER BY f.datef DESC LIMIT 1";

    $res = $db->query($sql);
    if ($res && $db->num_rows($res)) {
        $obj = $db->fetch_object($res);
        return [
            'bank' => $obj->label,
            'owner' => $obj->owner,
            'account' => $obj->number,
            'bic' => $obj->bic,
        ];
    }

    return [
        'bank' => 'Bank name not available',
        'owner' => '',
        'account' => ''
    ];
}

