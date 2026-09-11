<?php

session_start();

if(!isset($_SESSION['user'])){
    header("Location:../index.php");
    exit();
}

include "../config/database.php";
include "../includes/header.php";
include "../includes/sidebar.php";


// Load company settings
$settings = [];

$result = mysqli_query($conn,"
    SELECT setting_key, setting_value
    FROM settings
    WHERE setting_group='company'
");

while($row = mysqli_fetch_assoc($result)){
    $settings[$row['setting_key']] = $row['setting_value'];
}

?>

<div class="container-fluid">

    <h2 class="mb-4">
        <i class="fa fa-building"></i>
        Company Settings
    </h2>

    <div class="card">
        <div class="card-body">

            <form method="POST"
                  action="save.php"
                  enctype="multipart/form-data">

                <input type="hidden" name="company_save" value="1">

                <div class="row">

                    <div class="col-md-6">

                        <div class="mb-3">
                            <label>Company Name</label>
                            <input type="text"
                                   name="company_name"
                                   class="form-control"
                                   value="<?= $settings['company_name'] ?? ''; ?>"
                                   required>
                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="<?= $settings['phone'] ?? ''; ?>">
                        </div>

                    </div>

                </div>


                <div class="mb-3">
                    <label>Email</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="<?= $settings['email'] ?? ''; ?>">
                </div>


                <div class="mb-3">
                    <label>Address</label>
                    <textarea
                        name="address"
                        class="form-control"
                        rows="4"><?= $settings['address'] ?? ''; ?></textarea>
                </div>


                <div class="mb-3">

                    <label>Company Logo</label>

                    <input type="file"
                           name="logo"
                           class="form-control"
                           accept=".jpg,.jpeg,.png">

                </div>


                <?php
                if(!empty($settings['logo']) &&
                   file_exists("../uploads/logo/".$settings['logo'])){
                ?>

                    <div class="mb-3">

                        <label>Current Logo</label><br>

                        <img src="../uploads/logo/<?= $settings['logo']; ?>"
                             class="img-thumbnail"
                             style="max-width:180px;">

                    </div>

                <?php } ?>


                <button type="submit" class="btn btn-success">
                    <i class="fa fa-save"></i>
                    Save Settings
                </button>

                <a href="index.php"
                   class="btn btn-secondary">
                    Back
                </a>

            </form>

        </div>
    </div>

</div>

<?php include "../includes/footer.php"; ?>