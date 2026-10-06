<?php

$fourteenDaysAgo = date('Y-m-d', strtotime('-14 days'));
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

//////////////// Current Debtors //////////////////////
$sql = "SELECT SUM(outstanding) AS outstanding
        FROM inv_headers
        WHERE outstanding <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $outstanding = $row['outstanding'];
} else {
    $outstanding = 0;
}

// Current Debtors Qty
$sql = "SELECT COUNT(*) AS outstandingQty, custref
        FROM inv_headers
        GROUP BY custref
        HAVING SUM(outstanding) <> 0
";
if ($qresult = $dbcnx->query($sql) AND $qresult->num_rows > 0) {
    $outstandingQty = $qresult->num_rows;
} else {
    $outstandingQty = 0;
}

//////////////// Over 30 Days //////////////////////
$sql = "SELECT SUM(outstanding) AS outstanding
        FROM inv_headers
        WHERE CONCAT(year, '-', month, '-', day) < '$thirtyDaysAgo'
        HAVING SUM(outstanding) <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $over30Days = $row['outstanding'];
    $over30DaysQty = $result->num_rows;
} else {
    $over30Days = 0;
}

// Over 30 Days Qty
$sql = "SELECT COUNT(*) AS over30DaysQty, custref
        FROM inv_headers
        WHERE CONCAT(year, '-', month, '-', day) < '$thirtyDaysAgo'
        GROUP BY custref
        HAVING SUM(outstanding) <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $over30DaysQty = $result->num_rows;
} else {
    $over30DaysQty = 0;
}

//////////////// Over 14 Days //////////////////////
$sql = "SELECT SUM(outstanding) AS outstanding
        FROM inv_headers
        WHERE CONCAT(year, '-', month, '-', day) < '$fourteenDaysAgo'
        AND CONCAT(year, '-', month, '-', day) >= '$thirtyDaysAgo'
        HAVING SUM(outstanding) <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $over14Days = $row['outstanding'];
} else {
    $over14Days = 0;
}

// Over 14 Days Qty
$sql = "SELECT COUNT(*) AS over14DaysQty, custref
        FROM inv_headers
        WHERE CONCAT(year, '-', month, '-', day) < '$fourteenDaysAgo'
        AND CONCAT(year, '-', month, '-', day) >= '$thirtyDaysAgo'
        GROUP BY custref
        HAVING SUM(outstanding) <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $over14DaysQty = $result->num_rows;
} else {
    $over14DaysQty = 0;
}

////////////////////// Managed Debtors //////////////////////
$sql = "SELECT SUM(h.outstanding) AS outstanding
        FROM inv_headers AS h
        JOIN users AS c ON h.custref = c.custref
        WHERE h.outstanding <> 0 AND c.ad_managed = 'Yes'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $managed = $row['outstanding'];
    $managedQty = $result->num_rows;
} else {
    $managed = 0;
}

// Managed Debtors Qty
$sql = "SELECT COUNT(*) AS managedQty
        FROM inv_headers AS h
        JOIN users AS c ON h.custref = c.custref
        WHERE c.ad_managed = 'Yes'
        GROUP BY h.custref
        HAVING SUM(h.outstanding) <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $managedQty = $result->num_rows;
} else {
    $managedQty = 0;
}

//////////////// Restricted Debtors //////////////////////
$sql = "SELECT SUM(h.outstanding) AS outstanding
        FROM inv_headers AS h
        JOIN users AS c ON h.custref = c.custref
        WHERE h.outstanding <> 0 AND c.tim = 'Yes'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $restricted = $row['outstanding'];
    $restrictedQty = $result->num_rows;
} else {
    $restricted = 0;
}

// Restricted Debtors Qty
$sql = "SELECT COUNT(*) AS restrictedQty
        FROM inv_headers AS h
        JOIN users AS c ON h.custref = c.custref
        WHERE c.tim = 'Yes'
        GROUP BY h.custref
        HAVING SUM(h.outstanding) <> 0
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $restrictedQty = $result->num_rows;
} else {
    $restrictedQty = 0;
}




////////////////////// Other //////////////////////

// TermWarning Debtors
$sql = "SELECT COUNT(*) AS termWarning
        FROM users
        WHERE terminationWarning > '0'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $termWarning = $row['termWarning'];
} else {
    $termWarning = 0;
}

// TermNotice Debtors
$sql = "SELECT COUNT(*) AS termNotice
        FROM users
        WHERE terminationNotice > '0'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $termNotice = $row['termNotice'];
} else {
    $termNotice = 0;
}

// Paymnet Plans
$sql = "SELECT COUNT(*) AS paymentPlans
        FROM pplans_headers
        WHERE `status` = '1'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $paymentPlans = $row['paymentPlans'];
} else {
    $paymentPlans = 0;
}

// escalation
$sql = "SELECT COUNT(*) AS escalation
        FROM users
        WHERE escalation = 'Yes'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $escalation = $row['escalation'];
} else {
    $escalation = 0;
}

?>
