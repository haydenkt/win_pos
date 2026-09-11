<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";
include "../includes/permissions.php";

requirePermission('payments_view');

include "../includes/header.php";
include "../includes/sidebar.php";




$search="";


if(isset($_GET['search'])){

    $search=mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );

}




$query=mysqli_query($conn,"

SELECT


payments.*,


invoices.invoice_no,


customers.name AS customer_name,


customers.phone AS customer_phone



FROM payments



LEFT JOIN invoices

ON payments.invoice_id = invoices.id



LEFT JOIN customers

ON payments.customer_id = customers.id




WHERE


invoices.invoice_no LIKE '%$search%'

OR customers.name LIKE '%$search%'

OR customers.phone LIKE '%$search%'



ORDER BY payments.id DESC



");



?>




<h2>

<i class="fa fa-money-bill"></i>

Payments

</h2>






<div class="card mt-3">


<div class="card-body">



<form method="GET">


<div class="input-group">


<input

type="text"

name="search"

class="form-control"

placeholder="Search invoice, customer, phone..."

value="<?=htmlspecialchars($search);?>">



<button class="btn btn-primary">

<i class="fa fa-search"></i>

Search

</button>



</div>


</form>



</div>

</div>









<div class="card mt-3">


<div class="card-body">


<table class="table table-bordered table-striped">


<thead>


<tr>


<th>
#
</th>


<th>
Invoice No
</th>


<th>
Customer
</th>


<th>
Amount
</th>


<th>
Payment Type
</th>


<th>
Method
</th>


<th>
Date
</th>


<th>
Action
</th>


</tr>


</thead>






<tbody>



<?php


$i=1;


while($row=mysqli_fetch_assoc($query)){


?>



<tr>



<td>

<?=$i++;?>

</td>





<td>


<?=$row['invoice_no'];?>



</td>







<td>


<?=$row['customer_name'];?>


<br>


<small>

<?=$row['customer_phone'];?>

</small>


</td>







<td>


<?=number_format($row['amount']);?>


</td>







<td>


<?=$row['payment_type'];?>

</td>







<td>


<?=$row['payment_method'];?>

</td>







<td>


<?=date(

'd-m-Y',

strtotime($row['payment_date'])

);?>


</td>







<td>



<a href="../invoices/view.php?id=<?=$row['invoice_id'];?>"

class="btn btn-info btn-sm">


<i class="fa fa-eye"></i>


</a>





<?php if($_SESSION['role']=="admin"){ ?>


<a href="delete.php?id=<?=$row['id'];?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete this payment?');">


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






<?php

include "../includes/footer.php";

?>