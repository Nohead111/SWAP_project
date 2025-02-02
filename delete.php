<?php
include '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_POST["delete_button"])){
    $id=$_POST["id"];
    $query=$connect->prepare("DELETE FROM researchers WHERE ResearcherID =?");
    $query->bind_param('i', $id);
    if($query->execute())
    {
        set_session_message('success', 'Record Deleted', $redirect = "index.php");
    }
}
?>