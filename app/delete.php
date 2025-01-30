<?php
include '../config/config.php';
if(isset($_POST["delete_button"])){
    $id=$_POST["id"];
    $query=$connect->prepare("DELETE FROM researchers WHERE ResearcherID =?");
    $query->bind_param('i', $id);
    if($query->execute())
    {
        echo "<center>Record Deleted!</center><br>";
    }
}
?>