<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();


// ========================================
// LOGIN CHECK
// ========================================

if (!isset($_SESSION['user'])) {

    header("Location: ../index.php");
    exit();

}


include "../config/database.php";


// ========================================
// GET CUSTOMERS
// ========================================

$customers = mysqli_query($conn, "

    SELECT *

    FROM customers

    ORDER BY name ASC

");


if (!$customers) {

    die("Customer query failed: " . mysqli_error($conn));

}


include "../includes/header.php";

include "../includes/sidebar.php";

?>



<div class="main-content">

<div class="container-fluid">


<!-- ========================================
     PAGE HEADER
======================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">


    <h3>

        <i class="fa fa-map-marker"></i>

        New Site Survey

    </h3>


    <a
        href="index.php"
        class="btn btn-secondary"
    >

        <i class="fa fa-arrow-left"></i>

        Back

    </a>


</div>





<form
    action="save.php"
    method="POST"
    id="surveyForm"
>



<!-- ========================================
     CUSTOMER INFORMATION
======================================== -->

<div class="card mb-3">


    <div class="card-header">

        <strong>

            <i class="fa fa-user"></i>

            Customer Information

        </strong>

    </div>



    <div class="card-body">


        <div class="row align-items-end">


            <!-- ========================================
                 EXISTING CUSTOMER
            ======================================== -->

            <div class="col-md-8">


                <label class="form-label">

                    Customer

                </label>



                <div id="existingCustomerBox">


                    <select
                        name="customer_id"
                        id="customer_id"
                        class="form-select"
                    >


                        <option value="">

                            -- Select Customer --

                        </option>



                        <?php

                        while ($c = mysqli_fetch_assoc($customers)) {

                        ?>



                        <option
                            value="<?= htmlspecialchars($c['id']) ?>"
                        >

                            <?= htmlspecialchars($c['name']) ?>


                            <?php

                            if (!empty($c['phone'])) {

                            ?>

                                -
                                <?= htmlspecialchars($c['phone']) ?>

                            <?php

                            }

                            ?>

                        </option>


                        <?php

                        }

                        ?>


                    </select>


                </div>





                <!-- ========================================
                     NEW CUSTOMER
                ======================================== -->

                <div
                    id="newCustomerBox"
                    style="display:none;"
                >


                    <div class="mb-2">

                        <input
                            type="text"
                            name="new_name"
                            id="new_name"
                            class="form-control"
                            placeholder="Customer Name"
                        >

                    </div>



                    <div class="mb-2">

                        <input
                            type="text"
                            name="new_phone"
                            id="new_phone"
                            class="form-control"
                            placeholder="Phone"
                        >

                    </div>



                    <div>

                        <textarea
                            name="new_address"
                            id="new_address"
                            class="form-control"
                            rows="2"
                            placeholder="Address"
                        ></textarea>

                    </div>


                </div>


            </div>





            <!-- ========================================
                 CUSTOMER SWITCH BUTTON
            ======================================== -->

            <div class="col-md-4">


                <button
                    type="button"
                    class="btn btn-success"
                    id="newCustomerBtn"
                    onclick="showNewCustomer()"
                >

                    <i class="fa fa-plus"></i>

                    New Customer

                </button>



                <button
                    type="button"
                    class="btn btn-secondary"
                    id="existingCustomerBtn"
                    onclick="showExistingCustomer()"
                    style="display:none;"
                >

                    <i class="fa fa-users"></i>

                    Existing Customer

                </button>


            </div>


        </div>


    </div>


</div>





<!-- ========================================
     SURVEY INFORMATION
======================================== -->

<div class="card mb-3">


    <div class="card-header">


        <strong>

            <i class="fa fa-calendar"></i>

            Survey Information

        </strong>


    </div>



    <div class="card-body">


        <div class="row">


            <!-- DATE -->

            <div class="col-md-6 mb-3">


                <label class="form-label">

                    Survey Date

                </label>


                <input
                    type="date"
                    name="survey_date"
                    class="form-control"
                    value="<?= date('Y-m-d') ?>"
                    required
                >


            </div>





            <!-- STATUS -->

            <div class="col-md-6 mb-3">


                <label class="form-label">

                    Status

                </label>


                <select
                    name="status"
                    class="form-select"
                >


                    <option value="Pending">

                        Pending

                    </option>


                    <option value="Completed">

                        Completed

                    </option>


                    <option value="Approved">

                        Approved

                    </option>


                </select>


            </div>


        </div>





        <!-- NOTES -->

        <div class="mb-3">


            <label class="form-label">

                Notes

            </label>


            <textarea
                name="notes"
                class="form-control"
                rows="4"
                placeholder="Survey notes..."
            ></textarea>


        </div>


    </div>


</div>





<!-- ========================================
     SAVE BUTTON
======================================== -->

<div class="text-end">


    <button
        type="submit"
        class="btn btn-success"
        id="saveBtn"
    >

        <i class="fa fa-save"></i>

        Save Survey

    </button>


</div>



</form>


</div>

</div>





<script>


// ========================================
// SHOW NEW CUSTOMER
// ========================================

function showNewCustomer()
{

    document.getElementById(
        "existingCustomerBox"
    ).style.display = "none";


    document.getElementById(
        "newCustomerBox"
    ).style.display = "block";


    document.getElementById(
        "newCustomerBtn"
    ).style.display = "none";


    document.getElementById(
        "existingCustomerBtn"
    ).style.display = "inline-block";


    // Existing customer not required

    document.getElementById(
        "customer_id"
    ).required = false;


    // New customer name required

    document.getElementById(
        "new_name"
    ).required = true;

}





// ========================================
// SHOW EXISTING CUSTOMER
// ========================================

function showExistingCustomer()
{

    document.getElementById(
        "existingCustomerBox"
    ).style.display = "block";


    document.getElementById(
        "newCustomerBox"
    ).style.display = "none";


    document.getElementById(
        "newCustomerBtn"
    ).style.display = "inline-block";


    document.getElementById(
        "existingCustomerBtn"
    ).style.display = "none";


    // Existing customer required

    document.getElementById(
        "customer_id"
    ).required = true;


    // New customer name not required

    document.getElementById(
        "new_name"
    ).required = false;

}





// ========================================
// PREVENT DOUBLE SUBMIT
// ========================================

document.getElementById(
    "surveyForm"
).addEventListener(
    "submit",
    function()
    {


        let btn = document.getElementById(
            "saveBtn"
        );


        btn.disabled = true;


        btn.innerHTML =

            '<i class="fa fa-spinner fa-spin"></i> Saving...';


    }
);


</script>





<?php

include "../includes/footer.php";

?>
