<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";
include "../includes/permissions.php";

requirePermission('invoices_view');


// =====================================================
// FILTERS
// =====================================================

$search   = trim($_GET['search'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');


// =====================================================
// PAGINATION
// =====================================================

$per_page = 50;

$page = intval($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $per_page;


// =====================================================
// BUILD WHERE
// =====================================================

$where = [];

$params = [];

$types = '';


// Search

if ($search !== '') {

    $where[] = "

        (
            invoices.invoice_no LIKE ?
            OR invoices.id LIKE ?
            OR customers.name LIKE ?
            OR customers.phone LIKE ?
        )

    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssss";
}


// From date

if ($date_from !== '') {

    $where[] = "DATE(invoices.created_at) >= ?";

    $params[] = $date_from;

    $types .= "s";
}


// To date

if ($date_to !== '') {

    $where[] = "DATE(invoices.created_at) <= ?";

    $params[] = $date_to;

    $types .= "s";
}


$where_sql = '';

if (!empty($where)) {

    $where_sql =
        "WHERE " .
        implode(" AND ", $where);

}


// =====================================================
// TOTAL INVOICE COUNT
// =====================================================

$count_sql = "

SELECT COUNT(*) AS total

FROM invoices

LEFT JOIN customers
ON invoices.customer_id = customers.id

$where_sql

";


$stmt = $conn->prepare($count_sql);

if (!$stmt) {
    die(
        "Count query failed: "
        . $conn->error
    );
}


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$count_result =
    $stmt->get_result();

$count_row =
    $count_result->fetch_assoc();

$total_invoices =
    intval($count_row['total'] ?? 0);


// =====================================================
// TOTAL PAGES
// =====================================================

$total_pages =
    max(
        1,
        (int)ceil(
            $total_invoices / $per_page
        )
    );


if ($page > $total_pages) {

    $page = $total_pages;

    $offset =
        ($page - 1) * $per_page;

}


// =====================================================
// DAILY SUMMARY
//
// IMPORTANT:
// This query calculates totals from ALL matching
// invoices, not only the 50 invoices on the page.
// =====================================================

$summary_sql = "

SELECT

    DATE(invoices.created_at) AS sale_date,

    COUNT(*) AS invoice_count,

    COALESCE(
        SUM(invoices.grand_total),
        0
    ) AS total_sales,

    COALESCE(
        SUM(invoices.deposit),
        0
    ) AS total_deposit,

    COALESCE(
        SUM(invoices.balance),
        0
    ) AS total_balance

FROM invoices

LEFT JOIN customers
ON invoices.customer_id = customers.id

$where_sql

GROUP BY DATE(invoices.created_at)

ORDER BY sale_date DESC

";


$stmt = $conn->prepare($summary_sql);

if (!$stmt) {

    die(
        "Summary query failed: "
        . $conn->error
    );

}


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$summary_result =
    $stmt->get_result();


// =====================================================
// STORE DAILY SUMMARY
// =====================================================

$daily_summary = [];

while (
    $summary =
    $summary_result->fetch_assoc()
) {

    $daily_summary[
        $summary['sale_date']
    ] = $summary;

}


// =====================================================
// LOAD ONLY CURRENT PAGE OF INVOICES
// =====================================================

$sql = "

SELECT

    invoices.*,

    customers.name AS customer_name,

    customers.phone AS customer_phone

FROM invoices

LEFT JOIN customers
ON invoices.customer_id = customers.id

$where_sql

ORDER BY

    invoices.created_at DESC,

    invoices.id DESC

LIMIT ?

OFFSET ?

";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Invoice query failed: "
        . $conn->error
    );

}


$page_params = $params;

$page_params[] = $per_page;

$page_params[] = $offset;


$page_types =
    $types . "ii";


$stmt->bind_param(

    $page_types,

    ...$page_params

);


$stmt->execute();


$query =
    $stmt->get_result();


// =====================================================
// GROUP CURRENT PAGE INVOICES BY DATE
// =====================================================

$daily_invoices = [];


while (
    $row =
    $query->fetch_assoc()
) {

    $date = date(
        'Y-m-d',
        strtotime(
            $row['created_at']
        )
    );


    if (
        !isset(
            $daily_invoices[$date]
        )
    ) {

        $daily_invoices[$date] = [];

    }


    $daily_invoices[$date][] =
        $row;

}


?>


<?php include "../includes/header.php"; ?>

<?php include "../includes/sidebar.php"; ?>


<div class="content-wrapper">

<div class="container-fluid">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="row mt-3">

<div class="col-md-6">

<h2>

<i class="fa fa-file-invoice"></i>

Invoices

</h2>

</div>


<div class="col-md-6 text-end">

<a
href="add.php"
class="btn btn-primary"
>

<i class="fa fa-plus"></i>

New Invoice

</a>

</div>

</div>


<!-- =====================================================
     SEARCH / FILTER
===================================================== -->

<div class="card mt-3">

<div class="card-body">

<form method="GET">

<div class="row g-2">


<!-- SEARCH -->

<div class="col-md-4">

<label class="form-label">

Search

</label>

<input

type="text"

name="search"

class="form-control"

placeholder="Invoice, customer, phone..."

value="<?=htmlspecialchars($search);?>"

>

</div>


<!-- FROM -->

<div class="col-md-3">

<label class="form-label">

From Date

</label>

<input

type="date"

name="date_from"

class="form-control"

value="<?=htmlspecialchars($date_from);?>"

>

</div>


<!-- TO -->

<div class="col-md-3">

<label class="form-label">

To Date

</label>

<input

type="date"

name="date_to"

class="form-control"

value="<?=htmlspecialchars($date_to);?>"

>

</div>


<!-- BUTTON -->

<div class="col-md-2 d-flex align-items-end">

<button

type="submit"

class="btn btn-primary w-100"

>

<i class="fa fa-search"></i>

Search

</button>

</div>


</div>

</form>

</div>

</div>


<!-- =====================================================
     RESULT INFO
===================================================== -->

<div class="alert alert-light border mt-3">

<i class="fa fa-database"></i>

<strong>

<?=$total_invoices?>

</strong>

invoice(s) found.

&nbsp;

Page

<strong><?=$page?></strong>

of

<strong><?=$total_pages?></strong>.

</div>


<!-- =====================================================
     DAILY INVOICES
===================================================== -->

<?php if (!empty($daily_invoices)) { ?>


<?php foreach (
    $daily_invoices
    as $date => $invoices
) { ?>


<?php

$summary =
    $daily_summary[$date]
    ?? [

        'invoice_count' => count($invoices),

        'total_sales' => 0,

        'total_deposit' => 0,

        'total_balance' => 0

    ];

?>


<div class="card mt-4 shadow-sm">


<!-- =====================================================
     DATE HEADER
===================================================== -->

<div class="card-header">

<div class="row align-items-center">


<div class="col-md-5">

<h5 class="mb-0">

<i class="fa fa-calendar"></i>

<?=date(
    'd F Y',
    strtotime($date)
);?>

</h5>

</div>


<div class="col-md-7 text-md-end mt-2 mt-md-0">


<span class="badge bg-secondary">

<?=$summary['invoice_count']?> invoices

</span>


<span class="ms-2">

<strong>

Sales:

<?=number_format(
    $summary['total_sales'],
    2
);?>

</strong>

</span>


<span class="ms-3 text-primary">

<strong>

Deposit:

<?=number_format(
    $summary['total_deposit'],
    2
);?>

</strong>

</span>


<span class="ms-3 text-danger">

<strong>

Balance:

<?=number_format(
    $summary['total_balance'],
    2
);?>

</strong>

</span>


</div>


</div>

</div>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="card-body p-0">

<div class="table-responsive">


<table class="table table-bordered table-striped mb-0">


<thead class="table-dark">

<tr>

<th>

Invoice No

</th>

<th>

Customer

</th>

<th>

Total

</th>

<th>

Deposit

</th>

<th>

Balance

</th>

<th>

Payment Status

</th>

<th>

Action

</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $invoices
    as $row
) { ?>


<tr>


<!-- INVOICE -->

<td>

<strong>

<?=htmlspecialchars(
    $row['invoice_no']
);?>

</strong>

</td>


<!-- CUSTOMER -->

<td>

<?=htmlspecialchars(
    $row['customer_name']
    ?? ''
);?>

<br>

<small class="text-muted">

<?=htmlspecialchars(
    $row['customer_phone']
    ?? ''
);?>

</small>

</td>


<!-- TOTAL -->

<td>

<?=number_format(
    $row['grand_total'],
    2
);?>

</td>


<!-- DEPOSIT -->

<td>

<?=number_format(
    $row['deposit'],
    2
);?>

</td>


<!-- BALANCE -->

<td>

<?=number_format(
    $row['balance'],
    2
);?>

</td>


<!-- STATUS -->

<td>


<?php

if (
    $row['payment_status']
    === 'Paid'
) {

?>

<span class="badge bg-success">

Paid

</span>

<?php

}
elseif (
    $row['payment_status']
    === 'Partial'
) {

?>

<span class="badge bg-warning">

Partial

</span>

<?php

}
else {

?>

<span class="badge bg-danger">

Unpaid

</span>

<?php

}

?>


</td>


<!-- ACTION -->

<td class="text-nowrap">


<a

href="view.php?id=<?=$row['id'];?>"

class="btn btn-info btn-sm"

title="View"

>

<i class="fa fa-eye"></i>

</a>


<a

href="print_invoice.php?id=<?=$row['id'];?>"

target="_blank"

class="btn btn-primary btn-sm"

title="Print"

>

<i class="fa fa-print"></i>

</a>

<a
    href="return.php?id=<?=$row['id'];?>"
    class="btn btn-danger btn-sm"
    title="Return Items"
>
    <i class="fa fa-rotate-left"></i>
</a>

<a

href="../payments/add.php?invoice_id=<?=$row['id'];?>"

class="btn btn-success btn-sm"

title="Payment"

>

<i class="fa fa-money-bill"></i>

</a>


<?php

if (
    $_SESSION['role']
    === 'admin'
) {

?>

<a

href="delete.php?id=<?=$row['id'];?>"

class="btn btn-danger btn-sm"

title="Delete"

onclick="return confirm('Delete this invoice?');"

>

<i class="fa fa-trash"></i>

</a>

<?php } ?>


</td>


</tr>


<?php } ?>


</tbody>

</table>

</div>

</div>

</div>


<?php } ?>


<?php } else { ?>


<!-- =====================================================
     NO RESULTS
===================================================== -->

<div class="card mt-4">

<div class="card-body text-center py-5">


<i

class="fa fa-file-invoice fa-3x text-muted mb-3"

></i>


<h5>

No invoices found

</h5>


<p class="text-muted">

Try changing your search or date range.

</p>


</div>

</div>


<?php } ?>


<!-- =====================================================
     PAGINATION
===================================================== -->

<?php if (
    $total_pages > 1
) { ?>


<?php

function invoicePageUrl(
    $page_number
) {

    $params = [

        'search' =>
            $_GET['search'] ?? '',

        'date_from' =>
            $_GET['date_from'] ?? '',

        'date_to' =>
            $_GET['date_to'] ?? '',

        'page' =>
            $page_number

    ];


    return '?' .
        http_build_query(
            $params
        );

}

?>


<nav class="mt-4">

<ul class="pagination justify-content-center">


<!-- PREVIOUS -->

<?php if ($page > 1) { ?>

<li class="page-item">

<a

class="page-link"

href="<?=invoicePageUrl(
    $page - 1
);?>"

>

&laquo; Previous

</a>

</li>

<?php } ?>


<?php

$start_page =
    max(
        1,
        $page - 2
    );

$end_page =
    min(
        $total_pages,
        $page + 2
    );


for (
    $p = $start_page;
    $p <= $end_page;
    $p++
) {

?>


<li

class="page-item

<?=$p == $page
    ? 'active'
    : '';
?>"

>

<a

class="page-link"

href="<?=invoicePageUrl(
    $p
);?>"

>

<?=$p?>

</a>

</li>


<?php } ?>


<!-- NEXT -->

<?php if (
    $page < $total_pages
) { ?>

<li class="page-item">

<a

class="page-link"

href="<?=invoicePageUrl(
    $page + 1
);?>"

>

Next &raquo;

</a>

</li>

<?php } ?>


</ul>

</nav>


<?php } ?>


</div>

</div>


<?php include "../includes/footer.php"; ?>