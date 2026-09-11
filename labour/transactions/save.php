<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include '../../config/database.php';



if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("Location: index.php");
    exit();

}



// ========================================
// GET FORM DATA
// ========================================

$worker_id = intval($_POST['worker_id'] ?? 0);

$date = $_POST['transaction_date'] ?? date('Y-m-d');

$type = $_POST['type'] ?? '';

$amount = floatval($_POST['amount'] ?? 0);

$note = trim($_POST['note'] ?? '');





// ========================================
// VALIDATE
// ========================================

$allowed_types = [

    'Cash Payment',

    'Saving Deposit',

    'Saving Withdrawal',

    'Advance',

    'Advance Repayment'

];



if($worker_id <= 0){

    die("Invalid worker.");

}



if(!in_array($type, $allowed_types)){

    die("Invalid transaction type.");

}



if($amount <= 0){

    die("Amount must be greater than 0.");

}





// ========================================
// CHECK WORKER
// ========================================

$worker_sql = "

SELECT id, name

FROM workers

WHERE id=?

LIMIT 1

";



$stmt = $conn->prepare($worker_sql);

$stmt->bind_param("i",$worker_id);

$stmt->execute();

$worker_result = $stmt->get_result();

$worker = $worker_result->fetch_assoc();



if(!$worker){

    die("Worker not found.");

}





// ========================================
// SAVING WITHDRAWAL CHECK
// ========================================

if($type == 'Saving Withdrawal'){



    $sql = "

    SELECT COALESCE(SUM(

        CASE

            WHEN type='Saving Deposit'

            THEN amount

            WHEN type='Saving Withdrawal'

            THEN -amount

            ELSE 0

        END

    ),0) AS balance

    FROM labour_transactions

    WHERE worker_id=?

    ";



    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i",$worker_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();



    $saving_balance = floatval($row['balance']);



    if($amount > $saving_balance){

        die(

            "Cannot withdraw more than the worker's savings. "

            ."Current saving balance: "

            .number_format($saving_balance,2)

        );

    }

}





// ========================================
// ADVANCE REPAYMENT CHECK
// ========================================

if($type == 'Advance Repayment'){



    $sql = "

    SELECT COALESCE(SUM(

        CASE

            WHEN type='Advance'

            THEN amount

            WHEN type='Advance Repayment'

            THEN -amount

            ELSE 0

        END

    ),0) AS balance

    FROM labour_transactions

    WHERE worker_id=?

    ";



    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i",$worker_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();



    $advance_balance = floatval($row['balance']);



    if($amount > $advance_balance){

        die(

            "Cannot repay more than the worker's advance. "

            ."Current advance balance: "

            .number_format($advance_balance,2)

        );

    }

}





// ========================================
// INSERT TRANSACTION
// ========================================

$sql = "

INSERT INTO labour_transactions

(

    worker_id,

    transaction_date,

    type,

    amount,

    note

)

VALUES (?,?,?,?,?)

";



$stmt = $conn->prepare($sql);



if(!$stmt){

    die("Prepare failed: ".$conn->error);

}



$stmt->bind_param(

    "issds",

    $worker_id,

    $date,

    $type,

    $amount,

    $note

);



if(!$stmt->execute()){

    die("Insert failed: ".$stmt->error);

}





// ========================================
// SUCCESS
// ========================================

header(

    "Location: index.php?success="

    .urlencode("Transaction Saved")

);

exit;

?>