<?php

if(session_status()==PHP_SESSION_NONE){

    session_start();

}


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



$from=mysqli_real_escape_string(
    $conn,
    $_GET['from'] ?? ''
);


$to=mysqli_real_escape_string(
    $conn,
    $_GET['to'] ?? ''
);



$where="";



if($from!="" && $to!=""){


    $where="

    WHERE DATE(invoices.created_at)

    BETWEEN '$from' AND '$to'

    ";

}






header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=Sales_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");






echo "

<table border='1'>

<tr>

<th>Material Type</th>

<th>Total Items</th>

<th>Quantity</th>

<th>SQFT</th>

<th>Total Sales</th>

</tr>

";







$sql="

SELECT


COALESCE(material_types.name,'Unknown') AS material_name,


COUNT(invoice_items.id) AS total_items,


COALESCE(SUM(invoice_items.quantity),0) AS qty,


COALESCE(SUM(invoice_items.sqft),0) AS sqft,


COALESCE(SUM(invoice_items.total),0) AS sales



FROM invoice_items



LEFT JOIN invoices

ON invoice_items.invoice_id=invoices.id




LEFT JOIN factory_products

ON invoice_items.product_id=factory_products.id




LEFT JOIN material_types

ON factory_products.material_type_id=material_types.id




$where



GROUP BY material_types.id



ORDER BY sales DESC



";




$result=mysqli_query($conn,$sql);



if(!$result){

    echo mysqli_error($conn);

}





while($row=mysqli_fetch_assoc($result)){


echo "

<tr>

<td>

".$row['material_name']."

</td>


<td>

".$row['total_items']."

</td>


<td>

".$row['qty']."

</td>


<td>

".number_format($row['sqft'],2)."

</td>


<td>

".number_format($row['sales'])."

</td>


</tr>

";


}





echo "

</table>

";


?>