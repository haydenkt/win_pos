<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include_once "../config/database.php";
include "../includes/header.php";
include "../includes/sidebar.php";


$result = mysqli_query(
    $conn,
    "SELECT *
     FROM glass_types
     ORDER BY id DESC"
);

?>


<div class="container-fluid">


<div class="d-flex justify-content-between align-items-center mb-3">

    <h2>
        <i class="fa fa-window-maximize"></i>
        Glass Types
    </h2>


    <a href="add.php" class="btn btn-primary">

        <i class="fa fa-plus"></i>
        Add Glass

    </a>


</div>



<div class="card">


<div class="card-header">

<strong>
<i class="fa fa-list"></i>
Glass List
</strong>

</div>



<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-striped">


<thead class="table-light">

<tr>

<th width="60">
#
</th>


<th>
Glass Name
</th>


<th>
Price / Sqft
</th>


<th>
Status
</th>


<th>
Created Date
</th>


<th width="150">
Action
</th>


</tr>

</thead>



<tbody>


<?php

$count = 1;


while($row = mysqli_fetch_assoc($result)):

?>


<tr>


<td>
<?= $count++; ?>
</td>



<td>

<?= htmlspecialchars($row['name']); ?>

</td>



<td>

<?= number_format(
    $row['price_per_sqft'],
    2
); ?>

</td>



<td>


<?php if($row['status']=="Active"): ?>

<span class="badge bg-success">
Active
</span>


<?php else: ?>

<span class="badge bg-secondary">
Inactive
</span>


<?php endif; ?>


</td>



<td>

<?= date(
    "d-m-Y",
    strtotime($row['created_at'])
); ?>

</td>



<td>


<a
href="edit.php?id=<?= $row['id']; ?>"
class="btn btn-warning btn-sm"
title="Edit"
>

<i class="fa fa-edit"></i>

</a>



<a
href="delete.php?id=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this glass type?');"
title="Delete"
>

<i class="fa fa-trash"></i>

</a>



</td>



</tr>


<?php endwhile; ?>



</tbody>


</table>


</div>


</div>


</div>


</div>



<?php

include "../includes/footer.php";

?>