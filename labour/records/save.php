<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}


include "../../config/database.php";



if($_SERVER['REQUEST_METHOD']=="POST"){



    $record_date = $_POST['record_date'];

    $worker_ids = $_POST['worker_id'];

    $statuses = $_POST['status'];

    $days = $_POST['days'];

    $earned_amounts = $_POST['earned_amount'];

    $cash_amounts = $_POST['cash'];

    $saving_amounts = $_POST['saving'];





    foreach($worker_ids as $key=>$worker_id){



        $status = $statuses[$key];

        $day = $days[$key];

        $earned = $earned_amounts[$key];

        $cash = $cash_amounts[$key];

        $saving = $saving_amounts[$key];






        /*
        ==========================
        SAVE LABOUR RECORD
        ==========================
        */


        $check = $conn->prepare("

        SELECT id

        FROM labour_records

        WHERE worker_id=?

        AND work_date=?

        ");

        

        $check->bind_param(

            "is",

            $worker_id,

            $record_date

        );


        $check->execute();


        $result = $check->get_result();






        if($result->num_rows > 0){



            $update = $conn->prepare("

            UPDATE labour_records SET

            status=?,

            days=?,

            earned_amount=?

            WHERE worker_id=?

            AND work_date=?

            ");




            $update->bind_param(

                "sddis",

                $status,

                $day,

                $earned,

                $worker_id,

                $record_date

            );


            $update->execute();




        }else{



            $insert = $conn->prepare("

            INSERT INTO labour_records

            (
                worker_id,
                record_date,
                work_date,
                work_type,
                earned_amount,
                status,
                days
            )

            VALUES (?,?,?,?,?,?,?)

            ");




            $work_type="Daily Labour";




            $insert->bind_param(

                "isssssd",

                $worker_id,

                $record_date,

                $record_date,

                $work_type,

                $earned,

                $status,

                $day

            );



            $insert->execute();



        }







        /*
        ==========================
        REMOVE OLD TRANSACTIONS
        ==========================
        */

        $delete = $conn->prepare("

        DELETE FROM labour_transactions

        WHERE worker_id=?

        AND transaction_date=?

        ");

        

        $delete->bind_param(

            "is",

            $worker_id,

            $record_date

        );


        $delete->execute();







        /*
        ==========================
        SAVE CASH PAYMENT
        ==========================
        */


        if($cash > 0){


            $transaction = $conn->prepare("

            INSERT INTO labour_transactions

            (
                worker_id,
                transaction_date,
                type,
                amount,
                note
            )

            VALUES (?,?,?,?,?)

            ");




            $type="Cash Payment";

            $note="Daily labour payment";




            $transaction->bind_param(

                "issds",

                $worker_id,

                $record_date,

                $type,

                $cash,

                $note

            );



            $transaction->execute();



        }







        /*
        ==========================
        SAVE SAVING
        ==========================
        */


        if($saving > 0){



            $transaction = $conn->prepare("

            INSERT INTO labour_transactions

            (
                worker_id,
                transaction_date,
                type,
                amount,
                note
            )

            VALUES (?,?,?,?,?)

            ");




            $type="Saving Deposit";

            $note="Daily saving";




            $transaction->bind_param(

                "issds",

                $worker_id,

                $record_date,

                $type,

                $saving,

                $note

            );



            $transaction->execute();



        }



    }





    header(

        "Location:index.php?date=".$record_date

    );


    exit();



}else{

    header("Location:index.php");
    exit();

}

?>